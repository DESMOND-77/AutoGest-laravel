# Guide de développement

Ce document complète [CONTRIBUTING.md](../CONTRIBUTING.md) avec des conventions spécifiques au code de ce projet. Voir aussi [docs/architecture.md](architecture.md) pour la structure par domaine.

## Ajouter une fonctionnalité dans un domaine existant

1. Identifiez le domaine concerné sous `app/Domain/<Domaine>/`.
2. Repérez un fichier voisin similaire (Model, Service, Controller, Policy) et reproduisez exactement sa structure - la cohérence entre fichiers d'un même domaine est une convention forte de ce projet.
3. Toute règle métier va dans un `Service`, jamais dans un `Controller` ni une vue Blade.
4. Toute autorisation passe par une `Policy` vérifiant systématiquement :
   - l'appartenance au tenant (`$model->structure_id === $user->structure_id`) ;
   - la relation métier le cas échéant (ex. un moniteur ne peut agir que sur ses élèves assignés).
5. Tout modèle scopé par tenant utilise le trait `App\Support\BelongsToTenant`.
6. Ajoutez un test (voir [Tests](#tests) ci-dessous) avant de considérer la fonctionnalité terminée.

## Créer un nouveau domaine

```
app/Domain/NouveauDomaine/
├── Models/
├── Services/
├── Repositories/       (si l'agrégat principal bénéficie d'une abstraction de requête)
├── Policies/
├── Http/Controllers/
├── Http/Requests/
├── Enums/
└── Database/Factories/
```

Puis :

1. Enregistrez les bindings de repository et les `Gate::policy(...)` dans `app/Providers/AppServiceProvider.php`.
2. Déclarez les routes dans `routes/web.php`, groupées par préfixe et middleware de rôle.
3. **Ajoutez une règle dans `tests/Architecture/DomainBoundariesTest.php`** définissant les dépendances autorisées et interdites du nouveau domaine - c'est ce test qui empêche la réapparition d'un couplage non désiré entre domaines.

## Style de code PHP

- Types de retour et de paramètres explicites obligatoires.
- Promotion de propriétés du constructeur PHP 8 : `public function __construct(private readonly FooService $foo) {}`.
- Accolades obligatoires même pour les structures de contrôle à une ligne.
- PHPDoc pour les formes de tableaux (`@param array{id: int, name: string} $data`) plutôt que des commentaires en ligne.
- Nommage explicite : `isRegisteredForDiscounts()`, pas `discount()`.

Formatage automatique avant chaque commit :

```bash
vendor/bin/pint --dirty --format agent
```

## Tests

Le projet utilise [Pest v3](https://pestphp.com/) avec trois suites, définies dans `phpunit.xml` :

| Suite | Dossier | Objet |
| ----- | -------- | ----- |
| Unit | `tests/Unit/` | Logique pure, sans base de données (ex. règles de calcul) |
| Feature | `tests/Feature/` | Comportement HTTP, autorisations, isolation multi-tenant, bout en bout |
| Architecture | `tests/Architecture/` | Vérification automatique du graphe de dépendances entre domaines |

Conventions observées dans les tests existants :

- `beforeEach` sème les rôles (`$this->seed(RoleSeeder::class)`) et crée deux `Structure` (tenants) distinctes pour vérifier l'isolation.
- Chaque test d'autorisation vérifie explicitement qu'un utilisateur d'un tenant B ne peut ni lire ni modifier une ressource du tenant A (`assertForbidden()` + `Model::withoutGlobalScopes()->find(...)` pour prouver que la donnée n'a pas été altérée).
- Les factories vivent dans `app/Domain/<Domaine>/Database/Factories/` et sont préférées à une création manuelle de modèle.

Commandes utiles :

```bash
# Suite complète
php artisan test --compact

# Filtrer sur un nom de test ou de classe
php artisan test --compact --filter=InstructorPolicyTest

# Vérifier uniquement les frontières entre domaines
php artisan test --compact --filter=DomainBoundariesTest
```

## Base de données

- Toute modification de colonne dans une migration doit **répéter tous les attributs déjà définis** sur cette colonne (Laravel 12 supprime sinon les attributs non repris).
- Les casts d'énumération sont déclarés via la méthode `casts()` du modèle plutôt que la propriété `$casts`, en cohérence avec les modèles existants.
- Toute nouvelle table métier porte une colonne `structure_id` en première position après `id()`, avec une contrainte de clé étrangère vers `structures`.

Voir [docs/database.md](database.md) pour le détail du schéma.

## Outils Laravel Boost (MCP)

Ce projet est configuré avec [Laravel Boost](https://github.com/laravel/boost) (voir `boost.json` et `.mcp.json`), qui expose des outils MCP pour Claude Code : recherche de documentation Laravel versionnée, inspection du schéma de base de données, requêtes en lecture seule, lecture des logs applicatifs. Les agents IA travaillant sur ce dépôt doivent privilégier ces outils aux commandes shell manuelles équivalentes.

## Débogage

- Logs applicatifs : `storage/logs/laravel.log`, ou en direct via `php artisan pail`.
- `php artisan tinker` pour explorer l'état de l'application en console (ne pas l'utiliser pour valider une fonctionnalité - préférez un test Pest).
- `php artisan route:list` pour inspecter les routes enregistrées, filtrable par méthode, nom ou chemin.
