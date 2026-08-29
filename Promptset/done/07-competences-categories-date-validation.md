# Prompt - Regroupement des compétences par catégorie + date de validation

## Contexte

`App\Domain\Training\Models\Skill` a déjà une colonne `category`. L'écran d'évaluation (`resources/views/training/evaluation/show.blade.php`) affiche la catégorie en texte simple sous chaque compétence, mais **sans regroupement visuel ni sous-total par catégorie**, et **sans date de validation** - `SkillProgress` ne trace que le niveau courant (`not_started`/`in_progress`/`acquired`), pas la date à laquelle il est passé à `acquired`.

Écart confirmé par navigation réelle (`docs/audit/comparaison-vanilla-vs-laravel.md` §3.2) : la version PHP vanilla de référence affiche « Circulation 1/3 », « Maniabilité 3/3 » etc. avec une date « ✓ Validé le 21/07/2026 » par compétence acquise.

## Objectif

1. Regrouper visuellement les compétences par `category` dans l'écran d'évaluation, avec un sous-total `x/y acquises` par catégorie.
2. Tracer la date à laquelle une compétence passe au niveau `acquired`.

## Périmètre exact

- Migration : ajouter une colonne `acquired_at` (nullable, date) sur `skill_progress` (vérifier le nom exact de la table via `database-schema` ou `php artisan model:show SkillProgress`).
- `app/Domain/Training/Models/SkillProgress.php` : ajouter `acquired_at` au `$casts` (`date`) et au fillable si pertinent (probablement positionné par le service, pas par mass-assignment direct - suivre le pattern de garde déjà utilisé pour `lifecycle_stage`/`dossier_status` si `SkillProgress` a un service dédié, sinon vérifier comment `EvaluationController::store()` écrit aujourd'hui ces lignes).
- `app/Domain/Training/Http/Controllers/EvaluationController.php` (ou le service sous-jacent) : quand un niveau passe à `acquired` **pour la première fois**, poser `acquired_at = now()`. Ne pas écraser une date déjà posée si le niveau reste `acquired` lors d'une resoumission.
- `resources/views/training/evaluation/show.blade.php` : grouper `$skills` par `category` (`$skills->groupBy('category')` côté contrôleur ou vue), afficher un en-tête de section par catégorie avec le sous-total, et la date de validation à côté de chaque compétence acquise.
- Vérifier si l'écran « Ma Progression » élève à créer (voir `04-espace-eleve-progression-paiements-dossier.md`) doit réutiliser ce même regroupement - probable, à concevoir en cohérence.

## Contraintes

- Ne pas casser les tests existants sur `EvaluationController`/`SkillProgress`.
- La migration doit être réversible (`down()` qui retire la colonne).
- Si `SkillProgress` n'a pas de service dédié aujourd'hui (écriture directe dans le contrôleur), ne pas sur-ingénierer : ajouter la logique `acquired_at` au plus près de l'écriture existante plutôt que de créer un nouveau service pour une seule règle, sauf si le contrôleur devient difficile à tester en l'état.

## Étapes suggérées (TDD)

1. Lire `app/Domain/Training/Models/SkillProgress.php`, `EvaluationController.php`, et les tests existants (`grep -rl SkillProgress tests/`).
2. Écrire un test qui vérifie : passage à `acquired` pose `acquired_at` ; resoumission avec `acquired` déjà acquis ne change pas `acquired_at` ; passage de `acquired` à un niveau inférieur (si autorisé) - décider si `acquired_at` doit être remis à `null` (probable, pour rester cohérent).
3. Créer la migration.
4. Modifier la logique d'écriture.
5. Grouper l'affichage par catégorie avec sous-totaux dans la vue.
6. `php artisan test --compact --filter=Evaluation`.
7. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- L'écran d'évaluation affiche les compétences groupées par catégorie avec un compteur `x/y acquises` par groupe.
- Chaque compétence acquise affiche sa date de validation.
- Un retour en arrière de niveau (`acquired` → `in_progress`/`not_started`) efface `acquired_at`.
- Migration testée dans les deux sens (`up`/`down`).
