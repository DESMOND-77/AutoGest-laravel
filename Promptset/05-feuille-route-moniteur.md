# Prompt — Feuille de route moniteur (vue consolidée par élève)

## Contexte

Le moniteur dispose aujourd'hui de `/moniteur/agenda` (planning brut, séance par séance) et d'un accès à `/students` (liste). **Aucune vue ne consolide, par élève, les séances totales, présences/absences et heures de conduite effectuées**, ni ne résume la progression de ses compétences avec un lien direct vers l'écran d'évaluation.

Écart confirmé par navigation réelle (`docs/audit/comparaison-vanilla-vs-laravel.md` §1.3) : la version PHP vanilla de référence a un écran « Feuille de route » par élève avec ces agrégats + historique détaillé des séances (date/type/horaire/lieu/présence/note) + résumé compétences (« 4/10 acquises — 40% »).

C'était aussi une fonctionnalité legacy identifiée comme « à trancher avec le métier » dans `docs/audit/legacy-feature-parity.md` — la navigation réelle confirme qu'elle est **activement utilisée** dans la version de référence, ce qui renforce l'hypothèse d'un besoin réel plutôt qu'une fonctionnalité obsolète.

## Objectif

Ajouter un écran par élève, accessible depuis la liste des élèves du moniteur, affichant :
- Séances totales / présences / absences (comptage sur `LessonSession.presence_status`).
- Heures de conduite effectuées (somme des durées des séances `Practical` avec `presence_status = Present`).
- Historique détaillé des séances de cet élève avec ce moniteur.
- Résumé de la progression des compétences (X/Y acquises, %) avec lien vers `training.evaluation.show`.

## Périmètre exact

- `app/Domain/Scheduling/Services/` : ajouter une méthode d'agrégation (ex. `StudentSessionSummary` ou méthode sur un service existant) qui calcule ces chiffres pour un `Student` + `Instructor` donné. Vérifier d'abord si `LessonSession` a une colonne de durée exploitable (`starts_at`/`ends_at` — calculer la durée à la volée plutôt que stocker une colonne dupliquée).
- `app/Domain/Instructors/Http/Controllers/InstructorController.php` ou nouveau contrôleur dédié (`app/Domain/Scheduling/Http/Controllers/StudentRouteSheetController.php`, nom à ajuster) : action `show(Student $student)` scopée au moniteur connecté (vérifier que l'élève est bien suivi par ce moniteur avant d'autoriser l'accès — policy dédiée ou vérification explicite).
- Route `GET moniteur/eleves/{student}/feuille-route` (ou nom cohérent avec les routes `moniteur.*` existantes), groupe `role:moniteur`.
- Vue `resources/views/moniteur/feuille-route.blade.php` (ou équivalent).
- Lien depuis la liste `/students` (déjà accessible au moniteur) vers cette nouvelle vue.

## Contraintes

- **Un moniteur ne doit voir que les élèves qu'il encadre réellement** (via `instructor_id` sur `Student` et/ou `LessonSession.instructor_id`) — pas tous les élèves du tenant. Vérifier comment `StudentPolicy`/`StudentController::index()` filtre déjà pour le rôle `moniteur` aujourd'hui et rester cohérent.
- Pas de nouvelle colonne dupliquée si la donnée est calculable à la volée (durée de séance = `ends_at - starts_at`) — éviter la désynchronisation.
- Réutiliser le calcul de progression de compétences déjà utilisé par `training.evaluation.show` plutôt que de le dupliquer (extraire en méthode partagée si nécessaire).

## Étapes suggérées (TDD)

1. Lire `app/Domain/Scheduling/Models/LessonSession.php`, `StudentController::index()` (pour comprendre le filtrage moniteur existant), `training/evaluation/show.blade.php`/son contrôleur (pour le calcul de progression).
2. Écrire un test Feature : un moniteur voit la feuille de route d'un élève qu'il encadre, avec les bons agrégats sur un jeu de séances connu (présences/absences/durées) ; un moniteur ne peut pas accéder à la feuille de route d'un élève qu'il n'encadre pas (403) ; isolation tenant.
3. Implémenter le service d'agrégation.
4. Implémenter contrôleur + route + policy/vérification d'accès.
5. Implémenter la vue.
6. `php artisan test --compact --filter=RouteSheet` (ou nom choisi).
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Les agrégats (séances totales, présences, absences, heures effectuées) correspondent exactement aux données de `lesson_sessions` pour ce couple élève/moniteur.
- Le résumé de compétences est identique à celui affiché sur l'écran d'évaluation (pas de calcul divergent).
- Un moniteur ne peut pas consulter la feuille de route d'un élève qu'il n'encadre pas.
