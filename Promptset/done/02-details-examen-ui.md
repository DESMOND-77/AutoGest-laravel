# Prompt - Exposer les détails d'examen dans l'écran Examens

## Contexte

Le modèle `App\Domain\Training\Models\Exam` a déjà les colonnes `location`, `inspector`, `fault_count`, `comment` en base et dans `$fillable`. `StoreExamRequest` valide déjà `location`/`inspector` (nullable). Mais la vue `resources/views/training/exams/index.blade.php` n'affiche/ne saisit que : élève, type, date, résultat. Aucun champ pour le lieu de l'examen, le nom de l'inspecteur, le nombre de fautes commises, ni un commentaire libre.

C'est un écart confirmé par navigation réelle face à la version PHP vanilla de référence (`docs/audit/comparaison-vanilla-vs-laravel.md`, §2.1), qui expose ces quatre champs dans son écran « Attribuer un examen ». C'est un pur travail d'UI : **aucune nouvelle logique métier, aucune migration** - les colonnes existent déjà.

## Objectif

Permettre à l'admin de saisir `location`, `inspector`, `fault_count`, `comment` à la création d'un examen, et de les afficher/éditer ensuite.

## Périmètre exact

- `app/Domain/Training/Http/Requests/StoreExamRequest.php` - ajouter la validation de `fault_count` (`nullable|integer|min:0`) et `comment` (`nullable|string|max:1000`) ; `location`/`inspector` sont déjà validés.
- `app/Domain/Training/Http/Requests/UpdateExamResultRequest.php` - vérifier si ces champs doivent aussi être modifiables lors de la mise à jour du résultat (probable : au moment du résultat, on connaît souvent le nombre de fautes réel).
- `app/Domain/Training/Http/Controllers/ExamController.php` - passer les nouveaux champs au service de création s'il existe, ou directement au modèle.
- `resources/views/training/exams/index.blade.php` - étendre le formulaire de création (lieu, inspecteur) et la ligne de tableau (ajouter les colonnes fautes/commentaire, au moins en affichage ; envisager une modale ou une ligne de détail dépliable pour ne pas surcharger le tableau).

## Contraintes

- Respecter les conventions PHP du projet : type hints explicites, `curly braces` partout.
- Utiliser les composants Blade existants (`x-text-input`, `x-input-label`) pour rester cohérent avec le reste de l'app.
- Ne pas casser les tests existants sur `ExamController`/`StoreExamRequest` (`tests/Feature/...Exam...`).

## Étapes suggérées (TDD)

1. Lire `app/Domain/Training/Models/Exam.php` et les tests existants (`grep -r Exam tests/`) pour comprendre le comportement actuel.
2. Écrire/étendre un test Feature qui crée un examen avec `location`, `inspector`, `fault_count`, `comment` et vérifie leur persistance.
3. Étendre `StoreExamRequest` (et `UpdateExamResultRequest` si pertinent).
4. Étendre la vue Blade : champs supplémentaires dans le formulaire de création, colonnes/détail dans le tableau.
5. Lancer `php artisan test --compact --filter=Exam`.
6. `vendor/bin/pint --dirty --format agent`.

## Critères d'acceptation

- Un admin peut saisir lieu/inspecteur à la création d'un examen, et fautes/commentaire au moment de renseigner le résultat (ou dès la création, à trancher selon ce qui est le plus naturel dans le flux UI).
- Ces informations sont visibles sur l'écran Examens sans navigation supplémentaire.
- Tests verts, `pint` propre.
