# Prompt - Écran de revue du dossier administratif (`dossier_status`)

## Contexte

`Student.dossier_status` (enum `DossierStatus` : `Incomplete → Complete → Submitted → Validated`, avec retour `Submitted → Incomplete` en cas de rejet) a déjà sa machine à états gardée : `App\Domain\Students\Services\DossierStatusService::transitionTo()` (même patron que `LifecycleService` pour `lifecycle_stage`, voir `docs/audit/business-workflow.md` WF-03). **Aucune route HTTP, aucun écran, n'utilise ce service aujourd'hui.** Seuls l'import legacy et la création initiale de l'élève positionnent la valeur par défaut (`Incomplete`).

La version PHP vanilla de référence expose et fait évoluer ce statut directement dans son formulaire d'inscription (`docs/audit/comparaison-vanilla-vs-laravel.md`, §1.4/§2.2). Ici, on choisit de l'exposer sur la **fiche élève**, pas seulement à la création, pour permettre un vrai suivi de dossier dans le temps (upload de pièces → dossier complet → soumis → validé/rejeté).

## Objectif

Donner à l'admin un moyen de faire progresser le `dossier_status` d'un élève depuis sa fiche, avec les mêmes garanties de contrôle de transition que pour `lifecycle_stage`.

## Périmètre exact

- Nouvelle route : `PATCH students/{student}/dossier-status`, groupe `role:admin` (le dossier reste une responsabilité admin, contrairement au `lifecycle_stage` qui peut être avancé par un moniteur - à confirmer selon l'usage réel, mais commencer restrictif).
- Nouveau `App\Domain\Students\Http\Requests\UpdateDossierStatusRequest` (valide `dossier_status` contre l'enum `DossierStatus`).
- `StudentController` (ou un contrôleur dédié `DossierStatusController`, à trancher selon la cohérence avec `advanceStage()` déjà présent dans `StudentController`) : nouvelle méthode qui appelle `DossierStatusService::transitionTo()`, capture `InvalidDossierTransition` et retourne une erreur utilisateur claire (pas une 500).
- `resources/views/students/show.blade.php` (ou équivalent) : afficher le statut courant + un contrôle (bouton/select) limité aux transitions autorisées par `DossierStatus::allowedNextStages()` - même pattern visuel que le bouton d'avancement du `lifecycle_stage` déjà existant.
- Policy : vérifier/étendre `StudentPolicy` si une action dédiée est nécessaire (`update` suffit probablement, mais à confirmer).

## Contraintes

- **Ne jamais modifier `dossier_status` autrement que via `DossierStatusService`** - c'est la règle déjà actée par WF-03/WF-02 (`Student::setDossierStatus()` reste `protected`/bypass `$fillable`, à ne pas retirer).
- Suivre le même pattern que `StudentController::advanceStage()` pour `lifecycle_stage` - lire ce code avant d'écrire le nouveau.
- Toute transition invalide doit produire un message d'erreur explicite côté UI (pas juste une exception non gérée).

## Étapes suggérées (TDD)

1. Lire `app/Domain/Students/Services/DossierStatusService.php`, `DossierStatus.php`, et `StudentController::advanceStage()` comme référence de pattern.
2. Écrire un test Feature (`DossierStatusTransitionTest` ou similaire) : transition valide acceptée, transition invalide rejetée (422/redirect avec erreur), isolation tenant (un admin d'une autre structure ne peut pas modifier ce dossier).
3. Implémenter la route + requête + contrôleur.
4. Ajouter l'UI sur la fiche élève.
5. `php artisan test --compact --filter=Dossier`.
6. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Un admin peut faire progresser le dossier d'un élève dans l'ordre `Incomplet → Complet → Soumis → Validé` (avec retour `Soumis → Incomplet` en cas de rejet), directement depuis la fiche élève.
- Toute tentative de saut d'étape est bloquée avec un message clair.
- Le changement est couvert par un test d'isolation tenant.
- Cohérent visuellement avec le contrôle existant pour `lifecycle_stage`.
