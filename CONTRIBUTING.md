# Guide de contribution

Merci de votre intérêt pour Auto-GestBoard ! Ce document décrit comment contribuer efficacement au projet.

## Sommaire

- [Code de conduite](#code-de-conduite)
- [Avant de commencer](#avant-de-commencer)
- [Mise en place de l'environnement](#mise-en-place-de-lenvironnement)
- [Convention Git et stratégie de branches](#convention-git-et-stratégie-de-branches)
- [Convention de commits](#convention-de-commits)
- [Style de code](#style-de-code)
- [Tests](#tests)
- [Documentation](#documentation)
- [Ouvrir une Issue](#ouvrir-une-issue)
- [Ouvrir une Pull Request](#ouvrir-une-pull-request)
- [Revue de code](#revue-de-code)

## Code de conduite

Ce projet adhère au [Contributor Covenant](CODE_OF_CONDUCT.md). En participant, vous vous engagez à le respecter.

## Avant de commencer

- Vérifiez qu'une [Issue](https://github.com/DESMOND-77/AutoGest/issues) ou une [Pull Request](https://github.com/DESMOND-77/AutoGest/pulls) similaire n'existe pas déjà.
- Pour un changement important (nouvelle fonctionnalité, refonte d'un domaine), ouvrez d'abord une Issue de discussion avant de coder - cela évite le travail perdu si l'approche doit être ajustée.
- Les corrections de bugs et petites améliorations peuvent être proposées directement via Pull Request.

## Mise en place de l'environnement

Prérequis : PHP 8.2+ (8.5 recommandé), Composer, Node.js 20+, MySQL 8, Redis.

```bash
git clone https://github.com/DESMOND-77/AutoGest.git
cd AutoGest
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
composer run dev
```

Voir [docs/installation.md](docs/installation.md) pour le détail complet.

## Convention Git et stratégie de branches

Le projet suit un modèle proche de **GitHub Flow** :

- `main` : branche stable, toujours déployable.
- `dev` : branche d'intégration pour les fonctionnalités en cours de finalisation.
- branches de travail : créées à partir de `dev`, nommées selon le type de changement :

| Préfixe      | Usage                                      | Exemple                          |
| ------------ | ------------------------------------------- | --------------------------------- |
| `feature/`   | Nouvelle fonctionnalité                     | `feature/instructors-domain`      |
| `fix/`       | Correction de bug                           | `fix/tenant-scope-vehicle-plate`  |
| `docs/`      | Documentation uniquement                    | `docs/contributing-guide`         |
| `refactor/`  | Refactorisation sans changement de comportement | `refactor/scheduling-conflict-rule` |
| `test/`      | Ajout ou correction de tests uniquement     | `test/quiz-grading-service`       |
| `chore/`     | Maintenance (dépendances, CI, config)       | `chore/update-pint-config`        |

1. Créez votre branche depuis `dev` : `git checkout -b feature/ma-fonctionnalite dev`
2. Committez vos changements par petits incréments logiques.
3. Poussez votre branche et ouvrez une Pull Request vers `dev`.
4. `dev` est fusionnée vers `main` lors des publications de version.

## Convention de commits

Ce projet suit la convention [Conventional Commits](https://www.conventionalcommits.org/fr/) :

```
<type>(<portée optionnelle>): <description courte à l'impératif>

<corps optionnel expliquant le "pourquoi">

<footer optionnel : BREAKING CHANGE, Closes #123>
```

Types autorisés :

| Type       | Usage                                                        |
| ---------- | ------------------------------------------------------------- |
| `feat`     | Nouvelle fonctionnalité visible pour l'utilisateur final       |
| `fix`      | Correction de bug                                              |
| `docs`     | Documentation uniquement                                       |
| `style`    | Formatage, style de code (sans changement de logique)          |
| `refactor` | Changement de code qui ne corrige pas un bug ni n'ajoute une fonctionnalité |
| `perf`     | Amélioration de performance                                    |
| `test`     | Ajout ou correction de tests                                   |
| `chore`    | Maintenance, dépendances, configuration, CI                    |

Exemples :

```
feat(instructors): ajoute le domaine Instructeurs avec disponibilités
fix(scheduling): détecte les conflits de véhicule en plus de l'instructeur
docs(readme): met à jour la section installation
test(training): couvre la notation serveur du quiz de code
```

## Style de code

- **PHP** : [Laravel Pint](https://laravel.com/docs/pint) est la seule source de vérité pour le style. Avant chaque commit :

  ```bash
  vendor/bin/pint --dirty
  ```

- Respectez les conventions déjà en place dans le fichier voisin le plus proche avant d'en inventer une nouvelle (voir [CLAUDE.md](CLAUDE.md) / [AGENTS.md](AGENTS.md) pour les règles détaillées du projet).
- Types de retour et types de paramètres explicites obligatoires sur toutes les méthodes PHP.
- Utilisez la promotion de propriétés du constructeur PHP 8 (`public function __construct(public GitHub $github) {}`).
- Pas de logique métier dans les Contrôleurs ni dans les vues Blade - elle doit vivre dans un `Service` du domaine concerné.
- **JavaScript/Blade** : suivez le style existant (Tailwind CSS utilitaire, Alpine.js pour l'interactivité légère).

## Tests

Ce projet utilise [Pest](https://pestphp.com/) (v3) avec trois suites : `Unit`, `Feature`, `Architecture`.

```bash
# Suite complète
php artisan test --compact

# Un fichier ou un filtre précis
php artisan test --compact --filter=InstructorPolicyTest

# Vérifier les frontières entre domaines (Pest Arch)
php artisan test --compact --filter=DomainBoundariesTest
```

**Toute contribution de code doit être accompagnée d'un test** (nouveau test ou mise à jour d'un test existant) qui échoue avant le correctif et passe après. Les Pull Requests sans couverture de test adaptée ne seront pas fusionnées.

Avant d'ouvrir une Pull Request :

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## Documentation

- Toute nouvelle fonctionnalité utilisateur doit être reflétée dans le [README.md](README.md) (section Fonctionnalités ou Roadmap selon le cas).
- Toute décision d'architecture doit être documentée dans [docs/architecture.md](docs/architecture.md).
- Ne créez pas de fichiers de documentation superflus - préférez enrichir un document existant.
- Le [CHANGELOG.md](CHANGELOG.md) est mis à jour par les mainteneurs au moment de la publication d'une version ; les contributeurs n'ont pas besoin d'y ajouter d'entrée dans leur Pull Request, sauf si explicitement demandé.

## Ouvrir une Issue

Utilisez le modèle approprié :

- **[Bug report](.github/ISSUE_TEMPLATE/bug_report.md)** pour un comportement anormal
- **[Feature request](.github/ISSUE_TEMPLATE/feature_request.md)** pour une proposition de fonctionnalité
- **[Question](.github/ISSUE_TEMPLATE/question.md)** pour une question d'utilisation

Fournissez un maximum de contexte : version, rôle utilisateur concerné (Admin / Moniteur / Élève / Super-Admin), étapes de reproduction, logs pertinents (`storage/logs/laravel.log`).

## Ouvrir une Pull Request

1. Assurez-vous que votre branche est à jour avec `dev` (`git rebase dev` de préférence à un merge).
2. Remplissez entièrement le [modèle de Pull Request](.github/PULL_REQUEST_TEMPLATE.md).
3. Vérifiez que la CI (tests, Pint, CodeQL) passe au vert.
4. Une Pull Request doit rester focalisée sur un seul sujet - évitez de mélanger une fonctionnalité et un refactoring sans rapport.
5. Liez l'Issue correspondante avec `Closes #123` dans la description si applicable.

## Revue de code

- Toute Pull Request nécessite l'approbation d'au moins un [CODEOWNER](.github/CODEOWNERS) avant fusion.
- Les retours de revue portent sur : correction fonctionnelle, respect de l'architecture par domaine (voir [docs/architecture.md](docs/architecture.md)), couverture de tests, isolation multi-tenant (aucune requête sur un modèle scopé ne doit contourner `BelongsToTenant`), et lisibilité.
- Répondez à chaque commentaire de revue (par un changement ou une explication) avant de redemander une revue.
- Les mainteneurs peuvent fusionner directement une petite correction évidente sans revue formelle si la CI passe.

Merci de contribuer à Auto-GestBoard !
