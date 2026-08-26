# Audit technique - AutoGest-Laravel

Date : 2026-08-12
Périmètre : `app/`, `bootstrap/`, `config/`, `routes/`, `database/`, `tests/`, `composer.json`, `package.json`.
Méthode : lecture statique complète du dépôt (aucune exécution de code applicatif ; la suite de tests n'a pas pu être exécutée dans cet environnement d'audit - voir TECH-05).

Format de chaque constat : `ID | Gravité | Domaine | Description | Impact | Preuve | Solution recommandée | Statut`.

---

## 1. Vue d'ensemble de l'architecture

Le projet **n'utilise pas** la structure Laravel « standard » (`app/Models`, `app/Http/Controllers` plats). Il utilise une architecture **DDD par domaine** :

```
app/Domain/<Domaine>/
├── Models/
├── Http/Controllers/
├── Http/Requests/
├── Http/Resources/
├── Policies/
├── Services/
├── Repositories/
├── Events/
├── Listeners/ (rares - la plupart vivent dans app/Listeners/)
├── Enums/
├── Exceptions/
└── Database/Factories/
```

Domaines présents : `Audit, CRM, Documents, Finance, Fleet, Instructors, Notifications, Reports, Scheduling, Settings, Store, Students, Tenancy, Training, Users`.

Un test d'architecture Pest (`tests/Architecture/DomainBoundariesTest.php`) **fait respecter** les dépendances autorisées entre domaines (ex. Fleet ne doit pas dépendre de Finance/Students ; seuls Scheduling et Training peuvent dépendre d'Instructors). C'est un signal de maturité technique fort, rare dans un projet de cette taille - à conserver et à étendre plutôt qu'à casser.

**[INFO] TECH-00 - Architecture DDD cohérente et testée**
- Domaine : Global
- Description : la séparation par domaine est appliquée uniformément et vérifiée automatiquement par un test d'architecture.
- Impact : positif - réduit le risque de couplage accidentel lors des évolutions futures.
- Preuve : `tests/Architecture/DomainBoundariesTest.php`
- Solution recommandée : conserver ce pattern pour toute nouvelle fonctionnalité (quiz UI, imports CSV, mobile money) plutôt que d'introduire un style différent.
- Statut : OK

---

## 2. Constats techniques

**[MEDIUM] TECH-01 - Domaine Users/RBAC vide (scaffold uniquement)**
- Domaine : Users
- Description : `app/Domain/Users/{Models,Services,Policies,Http/Controllers}` ne contiennent que des `.gitkeep`. Toute la logique RBAC réelle vit dans `User` (trait Spatie `HasRoles`) + `database/seeders/RoleSeeder.php`, avec seulement 4 rôles (`superadmin, admin, moniteur, eleve`) et aucune permission granulaire (Spatie est utilisé en mode "rôle" seulement, pas "permission").
- Impact : pas de UI d'administration des utilisateurs/rôles, pas de granularité au-delà des 4 rôles fixes - limite l'extensibilité future (ex. rôle "comptable" mentionné dans la mission CLAUDE.md n'existe pas).
- Preuve : `app/Domain/Users/**/.gitkeep`, `database/seeders/RoleSeeder.php:17-19`
- Solution recommandée : soit renommer/assumer que Users est un domaine "non implémenté" et retirer le scaffold vide, soit l'implémenter réellement lors de l'étape RBAC (section 23 roadmap). Ne pas ajouter de rôle sans besoin confirmé (cf. règle CLAUDE.md §6 : "ne pas créer de rôle inutile").
- Statut : **Décision différée** - construire un vrai domaine Users/RBAC (UI d'administration des rôles/permissions) est une fonctionnalité à part entière, pas un correctif ponctuel. Reste planifié à l'étape 12 (complétion des domaines, RBAC) de `docs/audit/roadmap.md`, non traité dans cette passe MEDIUM pour éviter le scope creep sur un audit de correctifs.

**[LOW] TECH-02 - Policies manquantes pour deux ressources routées**
- Domaine : Store, Tenancy
- Description : `SupplierController` et `StructureManagementController` ont des routes actives mais aucune `SupplierPolicy` / `StructurePolicy` n'existe. L'autorisation repose uniquement sur le middleware `role:admin` / `role:superadmin`, pas sur une policy par ressource.
- Impact : cohérence - tous les autres domaines utilisent des Policies explicites ; ces deux exceptions cassent le pattern et rendent l'audit d'autorisation moins lisible. Pas de faille de sécurité immédiate tant que le rôle suffit à porter l'autorisation métier.
- Preuve : `routes/web.php` (`suppliers.store`, `superadmin/structures*`), absence de fichier dans `app/Domain/Store/Policies/` et `app/Domain/Tenancy/Policies/`.
- Solution recommandée : créer `SupplierPolicy` et `StructurePolicy` pour homogénéiser, même si le comportement actuel (role-only) est fonctionnellement correct.
- Statut : **CORRIGÉ (2026-08-12)** - `SupplierPolicy` (`create`) et `StructurePolicy` (`viewAny`/`update`/`delete`) créées et enregistrées ; `SupplierController` n'emprunte plus `ProductPolicy` par contournement, `StructureManagementController` appelle désormais `authorize()` sur chaque action comme tous les autres contrôleurs. Tests ajoutés dans `SupplierControllerTest`.

**[LOW] TECH-03 - Mixage de versions Tailwind (core v3 + plugin Vite v4)**
- Domaine : Frontend build
- Description : `package.json` déclare `tailwindcss: ^3.1.0` en même temps que `@tailwindcss/vite: ^4.0.0`. Ce sont deux générations différentes du plugin Tailwind (v3 utilise PostCSS/CLI classique, v4 a un tout autre pipeline).
- Impact : risque de build cassé ou de configuration `tailwind.config.js` ignorée selon la résolution effective par Vite ; à vérifier avec `npm run build` avant toute campagne de reconstruction UX.
- Preuve : `package.json`
- Solution recommandée : fixer la paire de versions (soit tout v3 + `@tailwindcss/postcss`, soit migration complète v4), puis lancer `npm run build` pour confirmer qu'aucune classe `dark:`/utilitaire n'est perdue silencieusement.
- Statut : **CORRIGÉ (2026-08-12)** - investigation : `@tailwindcss/vite` (v4) était présent dans `package.json` mais **jamais importé** dans `vite.config.js` (qui n'utilise que `laravel-vite-plugin`) ni dans `postcss.config.js` (qui utilise la chaîne classique `tailwindcss`/`autoprefixer`, cohérente avec `tailwindcss: ^3.1.0`). Ce n'était donc pas un conflit actif, juste une dépendance morte. Package retiré de `package.json`, `npm run build` reconfirmé identique (même hash de sortie CSS).

**[HIGH] TECH-04 - `instructor_id` non contraint au tenant dans la validation**
- Domaine : Students
- Description : `StoreStudentRequest::rules()` valide `instructor_id` avec `exists:users,id` seulement - sans filtrage par `structure_id`. La règle de validation seule ne garantit pas que l'instructeur assigné appartient au même établissement que l'élève créé.
- Impact : dépend de ce que fait le contrôleur en aval ; si aucune vérification supplémentaire n'existe, un admin pourrait (via manipulation de formulaire) assigner un élève à un instructeur d'un autre tenant. Voir `docs/audit/multi-tenancy-audit.md` MT-03 pour le détail et la vérification recommandée.
- Preuve : `app/Domain/Students/Http/Requests/StoreStudentRequest.php:32`
- Solution recommandée : `Rule::exists('users','id')->where('structure_id', $tenantId)` ou validation applicative dans `EnrollmentService`.
- Statut : À corriger - voir MT-03

**[HIGH] TECH-05 - Suite de tests non exécutable dans l'environnement d'audit**
- Domaine : Global / CI
- Description : `vendor/` absent (dépendances Composer non installées) et `phpunit.xml` exige `DB_CONNECTION=mysql` sans base provisionnée. `php artisan test` échoue immédiatement (`Failed opening required '.../vendor/autoload.php'`).
- Impact : impossible de confirmer dans cette passe d'audit que les 96+ cas de test recensés passent réellement. Le rapport de mission (§43) impose de ne jamais prétendre qu'un test passe sans l'avoir exécuté - donc **aucune affirmation de succès des tests n'est faite ici**.
- Preuve : tentative d'exécution documentée par l'agent d'audit (échec `vendor/autoload.php`).
- Solution recommandée : dans un environnement disposant de `composer install` + MySQL configuré, exécuter `php artisan test --compact` et consigner le résultat réel avant de considérer une quelconque fonctionnalité comme validée. C'est un prérequis bloquant pour l'étape 9 (Tests) du plan d'exécution.
- Statut : **CORRIGÉ (2026-08-12)** - environnement complet provisionné (`composer install`, `.env` + clé d'application, `npm install && npm run build`, MySQL de test déjà accessible avec les identifiants de `phpunit.xml`). Suite complète exécutée et **réellement vérifiée verte** : 130/130 tests passants après l'ensemble des corrections de cette session (114/114 sur la baseline avant modification).

**[MEDIUM] TECH-06 - Requêtes de reporting couplées à la syntaxe MySQL**
- Domaine : Reports
- Description : `ReportService::revenueByMonth()` utilise `selectRaw("DATE_FORMAT(occurred_on, '%Y-%m')...")`, une fonction SQL spécifique à MySQL/MariaDB.
- Impact : acceptable si MySQL est la base de données définitive du produit (cohérent avec `phpunit.xml` qui l'exige déjà) - mais bloque toute portabilité vers PostgreSQL/SQLite sans réécriture.
- Preuve : `app/Domain/Reports/Services/ReportService.php`
- Solution recommandée : documenter MySQL comme dépendance actée du produit (`docs/architecture.md`) plutôt que de généraliser inutilement ; pas d'action corrective nécessaire si ce choix est confirmé.
- Statut : Accepté (à documenter)

**[INFO] TECH-07 - Auto-discovery des listeners d'événements**
- Domaine : Students, Finance, Notifications
- Description : `NotifyInstructorOnStageChange` et `NotifyAdminsOnPaymentReceived` (dans `app/Listeners/`) ne sont enregistrés nulle part explicitement (pas de `EventServiceProvider`, pas de `Event::listen()`) - ils fonctionnent via l'auto-discovery native de Laravel 11/12 (scan de `app/Listeners/*` par signature de `handle()`), confirmée active ici (pas de `shouldDiscoverEvents()` désactivé).
- Impact : fonctionnellement correct, mais aucun test ne vérifie que ces notifications sont réellement déclenchées (`PaymentRecordingTest` vérifie le ledger, pas la notification admin ; aucun test ne couvre `NotifyInstructorOnStageChange`).
- Preuve : `app/Listeners/NotifyInstructorOnStageChange.php`, `app/Listeners/NotifyAdminsOnPaymentReceived.php`, absence de `app/Providers/EventServiceProvider.php`.
- Solution recommandée : ajouter un test Feature qui déclenche un paiement / un changement d'étape et vérifie `Notification::assertSentTo(...)`, pour verrouiller ce comportement contre une régression future (ex. si l'auto-discovery est un jour désactivée).
- Statut : À améliorer (couverture de test)

**[MEDIUM] TECH-08 - Absence de verrouillage de concurrence sur la vérification de conflit planning**
- Domaine : Scheduling
- Description : `SchedulingService::schedule()`/`reschedule()` exécutent `hasConflict()`/`hasVehicleConflict()` puis créent la séance, sans `DB::transaction()` ni verrou de ligne (`lockForUpdate`). Contrairement à `OrderService::place()` (Store) qui utilise `lockForUpdate()` pour éviter la survente.
- Impact : deux requêtes concurrentes (ex. deux secrétaires planifiant en même temps) peuvent toutes deux passer le contrôle de conflit avant que l'une des deux insertions ne soit visible à l'autre, créant un double-booking en race condition. Fenêtre étroite mais réelle en usage multi-poste.
- Preuve : `app/Domain/Scheduling/Services/SchedulingService.php`, comparer avec `app/Domain/Store/Services/OrderService.php:34-85`.
- Solution recommandée : envelopper `guard()` + création dans `DB::transaction()` avec un verrou approprié (ex. verrou pessimiste sur les séances existantes de l'instructeur/véhicule pour la plage horaire), ou une contrainte d'exclusion au niveau DB si le SGBD le permet.
- Statut : **CORRIGÉ (2026-08-12)** - voir `business-workflow.md` SCHED-03.

---

## 3. Stack confirmée

- PHP `^8.2` (composer.json - CLAUDE.md mentionne 8.5, à revérifier/aligner), Laravel `^12.0`, Breeze `^2.4`.
- `spatie/laravel-permission ^8.3`, `predis/predis ^3.5` (Redis prêt mais pas nécessairement utilisé - cf. §31 CLAUDE.md, ne pas généraliser sans mesure).
- Frontend : Tailwind (voir TECH-03), Alpine.js `^3.4`, Vite `^7`, pas de framework JS (pas de Vue/React) - conforme à la règle CLAUDE.md interdisant une migration Inertia/SPA.
- Pest `^3.8` + `pest-plugin-arch` + `pest-plugin-laravel`.
- Pas de `routes/api.php` - cohérent avec la roadmap (API Sanctum non encore livrée, §28 CLAUDE.md).

**[INFO] TECH-09 - Écart de version PHP entre CLAUDE.md et composer.json**
- Domaine : Global
- Description : CLAUDE.md annonce PHP 8.5, `composer.json` requiert `^8.2`.
- Impact : mineur, à clarifier - n'affecte pas le fonctionnement mais peut induire en erreur sur l'environnement cible de production.
- Preuve : `composer.json` (`"php": "^8.2"`) vs CLAUDE.md "php - 8.5".
- Solution recommandée : aligner la contrainte composer sur la version réellement ciblée en production.
- Statut : À clarifier
