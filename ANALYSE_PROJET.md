# Analyse du projet Auto-GestBoard

Analyse générée le 2026-10-22 à partir du code source (composer.json, package.json, routes, config, `app/Domain/*`, `resources/*`, tests, CI). Les secrets (mots de passe, clés) sont volontairement omis.

---

## 1. Vue d'ensemble

SaaS **multi-établissements** pour auto-écoles (Gabon, fuseau `Africa/Libreville`, langue `fr`) : élèves, moniteurs, planning, facturation, flotte, examens, boutique, CRM, documents, audit. Il remplace l'application PHP vanille `autoecole_jh` via une migration *strangler fig* module par module.

Monolithe **Laravel 12** en rendu serveur (Blade + Alpine + Tailwind), organisé en **domaines métier** (`app/Domain/<Domaine>`), pas en couches techniques.

---

## 2. Stack technique

### Backend
| Élément | Version / choix |
|---|---|
| PHP | 8.5 (composer exige `^8.2`) |
| Framework | Laravel 12.x (installé : 12.64) |
| Auth | Laravel Breeze 2 (sessions, pas d'API/Sanctum) |
| RBAC | `spatie/laravel-permission` 8 — 4 rôles : `superadmin`, `admin`, `moniteur`, `eleve` |
| PDF | `barryvdh/laravel-dompdf` 3 |
| DB tooling | `doctrine/dbal` 4 |
| Observabilité | `laravel/telescope` 5 (+ `laravel/pail` en dev) |
| Redis client | `predis/predis` 3 |
| REPL | `laravel/tinker` |

### Dev / qualité
- **Tests** : Pest 3 + `pest-plugin-laravel` + `pest-plugin-arch` (PHPUnit 11), Mockery, Faker (`fr_FR`)
- **Style** : Laravel Pint
- **IA / outillage** : Laravel Boost 2 (serveur MCP `php artisan boost:mcp`, `.mcp.json`, `boost.json`, guidelines dans `CLAUDE.md`/`AGENTS.md`), Laravel Sail (dispo, non utilisé : `sail: false`)
- **Collision** pour l'affichage des erreurs CLI

### Frontend
| Élément | Version |
|---|---|
| Vite | 7 + `laravel-vite-plugin` 2 |
| Tailwind CSS | 3 + `@tailwindcss/forms` + PostCSS/Autoprefixer |
| Alpine.js | 3 (démarré dans `resources/js/app.js`) |
| Axios | 1.x (via `bootstrap.js`) |
| Polices | Inter (bunny.net), Barlow Condensed + IBM Plex Mono (landing) |
| Orchestration dev | `concurrently` 9 |

Pas de SPA, pas de Livewire/Inertia : Blade pur + Alpine pour l'interactivité.

### Infrastructure (config effective de `.env`, local)
| Service | Valeur |
|---|---|
| Base | MySQL 8 — `autoecole_jh_laravel` (tests : `autoecole_jh_laravel_test`) |
| Sessions | Redis (`SESSION_DRIVER=redis`, 120 min) |
| Cache | Redis, préfixe `aeg` |
| Queue | Redis |
| Client Redis | predis, `127.0.0.1:6379` |
| Mail | SMTP (Gmail, port 465) ; expéditeur `no-reply@auto-gestboard.com` |
| SMS | `SMS_DRIVER=log` (aucun fournisseur gabonais branché ; interface `SmsGateway`) |
| Stockage | disque `local` (S3 configurable mais vide) |
| Logs | stack `single`, niveau `debug` |
| Broadcast | `log` |
| Serveur dev | `php artisan serve` avec `PHP_CLI_SERVER_WORKERS=4` |

> **Redis est requis en dev** (sessions/cache/queue). S'il est arrêté, l'application ne démarre pas correctement.

---

## 3. Architecture applicative

### 3.1 Domaines (`app/Domain/`)

`Audit`, `CRM`, `Documents`, `Finance`, `Fleet`, `Instructors`, `Notifications`, `Recyclage`, `Reports`, `Scheduling`, `Settings`, `Store`, `Students`, `Tenancy`, `Training`, `Users` (+ `Core/`, dossier vide).

Structure type d'un domaine : `Models/` · `Services/` (seul endroit des règles métier) · `Repositories/` (interface + Eloquent) · `Policies/` · `Http/{Controllers,Requests,Resources,Middleware}` · `Enums/` · `Events/` · `Listeners/` · `Exceptions/` · `Database/Factories/`.

| Domaine | Contenu clé |
|---|---|
| Tenancy | `Structure` (tenant), onboarding, statuts `en_attente → actif → suspendu/désactivé`, middleware `ResolveTenant` |
| Users | gestion comptes, activation/désactivation, reset mot de passe, middleware `EnsureUserIsActive` |
| Students | cycle de vie 15 étapes, inscription publique par lien, OTP e-mail, dossier, documents requis, dossier ZIP |
| Instructors | profils, disponibilités hebdomadaires |
| Training | compétences, évaluations, examens, quiz de code (questions/options/tentatives) |
| Scheduling | séances, `ConflictRule` (chevauchement moniteur **et** véhicule), présence, agenda, feuille de route |
| Fleet | véhicules, entretiens, carburant, alertes CT/assurance (30 j) |
| Finance | forfaits, factures, paiements (annulables), journal comptable (`LedgerEntry`) |
| Store | produits, stock (mouvements), fournisseurs, commandes, bons de commande/réception, rapports |
| CRM | leads, statuts, conversion en élève |
| Documents | GED polymorphe (élève/véhicule), revue approuver/rejeter, téléchargement/stream |
| Notifications | notifications DB, `AlertNotification`, canal SMS (`SmsChannel`, `LogSmsGateway`) |
| Audit | `AuditService`, journal consultable (admin/superadmin) |
| Recyclage | entrées de recyclage (motifs) reportées au journal comptable |
| Reports | tableau de bord admin, exports CSV |
| Settings | paramètres par établissement |

Hors domaines : `App\Models\User`, `DashboardController`, `ProfileController`, contrôleurs Auth Breeze, `App\Support\{BelongsToTenant, TenantContext, CsvExporter}`.

### 3.2 Frontières entre domaines
Dépendances **unidirectionnelles**, vérifiées par `tests/Architecture/DomainBoundariesTest.php` (Pest Arch). Graphe documenté dans `docs/architecture.md` (ex. Finance → Students, jamais l'inverse).

### 3.3 Multi-tenance
- **Row-level** : colonne `structure_id` sur chaque table métier.
- Trait `BelongsToTenant` : scope global Eloquent + auto-remplissage à la création.
- `ResolveTenant` (ajouté au groupe `web`) pose `TenantContext` depuis `$user->structure`, vidé en `finally`. Il est mis **avant** `SubstituteBindings` dans la priorité des middlewares (`bootstrap/app.php`), donc le scope est actif au moment du route-model binding.
- Superadmin : `structure_id = null`, hors tenant.
- Unicité scopée par tenant `unique(structure_id, col)`.
- Chaque mutation passe par une **Policy** (tenant + relation métier). 22 policies enregistrées dans `AppServiceProvider`.

### 3.4 Événements / listeners
- `StudentStageChanged` → `LogStageChange` (log + audit) ; `NotifyInstructorOnStageChange`
- `StudentEmailVerified` → `ActivateStudentAfterEmailVerification`
- `PaymentRecorded` → `NotifyAdminsOnPaymentReceived`, `NotifyStudentSmsOnPaymentReceived`
- `VehicleExpenseRecorded` → `RecordVehicleExpenseInLedger` (auto-découvert)
- `RecyclageEntryRecorded` → `RecordRecyclageEntryInLedger`
- `StudentPublicRegistrationCompleted` → `NotifyAdminsOnStudentPublicRegistration`
- `StructureRegistered` → `NotifyPlatformAdminsOnStructureRegistration`

### 3.5 Bindings (`AppServiceProvider`)
Repositories : Student, Invoice, LessonSession, Instructor. `SmsGateway` → `LogSmsGateway` (via `match` sur `services.sms.driver`). Composant Blade `brand-logo`.

### 3.6 Rate limiting
`public-registration-lookup` : 30/min par IP · `public-registration-submit` : 6/min par IP (clé IP explicite car l'inscription connecte l'utilisateur). OTP renvoi : `throttle:1,1`.

### 3.7 Tâches planifiées / commandes
- `fleet:check-alerts` — chaque jour à 07:00 (`routes/console.php`)
- `import:legacy-students {structure} {path}` et `ImportLegacyPlanning` — import de l'ancien système

---

## 4. Flows fonctionnels

### 4.1 Cycle de vie élève (`LifecycleStage`)
Piloté uniquement par `LifecycleService::transitionTo()` (exception `InvalidStageTransition` sinon). Chaque transition écrit log + audit, **sans automatiser** facture/planning (repère de statut manuel).

```
Prospect → Pré-inscription → Constitution du dossier → Validation → Inscription → Paiement
  → Cours théorique → Examens blancs → Code obtenu → Cours pratique → Évaluation continue
  → Prêt examen → Examen pratique → Permis obtenu → Ancien élève

Retours autorisés : Validation → Constitution du dossier (document rejeté)
                    Examen pratique → Évaluation continue (échec)
```

### 4.2 Inscription d'un établissement (SaaS)
`GET/POST /register` (`StructureRegistrationController`) → `StructureOnboardingService` → événement `StructureRegistered` → notification aux admins plateforme → statut `en_attente` → superadmin active/suspend/supprime (`/superadmin/structures`).

### 4.3 Inscription publique d'un élève
Admin génère un lien tokenisé (`/settings/student-registration`, TTL `STUDENT_REGISTRATION_LINK_TTL_DAYS`, régénérer/révoquer) → candidat remplit `/register/student` (public, throttlé, tenant déduit du token) → compte créé et connecté → **OTP e-mail** (`/eleve/verification-otp`, expiration `EMAIL_OTP_EXPIRY_MINUTES`, tentatives `EMAIL_OTP_MAX_ATTEMPTS`) → `StudentEmailVerified` active l'élève → accès aux écrans élève (`otp.verified`).

### 4.4 Dossier élève
Types de documents requis configurables (`/settings/documents-requis`) → l'élève téléverse (`/eleve/dossier/{type}`) puis soumet → l'admin relit (`/dossiers`) : approuver / rejeter (motif) → `DossierStatusService` → téléchargement du dossier complet (`DocumentBundleService`).

### 4.5 Finance
Forfait → facture (par élève, `student_id` nullable depuis la migration du 27/08) → paiements (espèces, Airtel Money, Moov Money, virement, chèque ; refus si > solde) → **une seule transaction** : Payment + Invoice (`amount_paid`, statut) + LedgerEntry → événement `PaymentRecorded`. Annulation de paiement possible (`PaymentAlreadyCancelled`). Journal manuel `/finance/ledger`.

### 4.6 Planning
Admin crée/modifie/duplique/supprime des séances ; conflit moniteur ou véhicule → `SchedulingConflict`. Moniteur/admin marquent la présence. Export CSV. Élève : `/eleve/planning`. Moniteur : `/moniteur/agenda` + feuille de route élève.

### 4.7 Formation
Compétences → évaluation par élève (admin/moniteur) → progression (`SkillProgress`, `SkillLevel`) ; examens (`ExamType`/`ExamResult`) ; quiz de code côté élève (`/quiz/*`) avec correction par `QuizGradingService`.

### 4.8 Flotte
Véhicules, entretiens et carburant → `VehicleExpenseRecorded` → écriture automatique au journal comptable ; alertes CT/assurance (≤ 30 j) via `AlertService` (dashboard + commande planifiée).

### 4.9 Boutique
Produits/fournisseurs → commandes (`InsufficientStock` si stock insuffisant, annulation) → bons de commande et réception (mouvements de stock) → rapports (top produits CSV, PDF).

### 4.10 CRM
Lead (statuts `LeadStatus`) → changement de statut → conversion en élève (étape Prospect).

---

## 5. Routes et contrôle d'accès

| Zone | Middleware | Préfixe |
|---|---|---|
| Public | — | `/`, `/register/student` (throttle), `/login`, `/register`, reset mot de passe |
| Tout utilisateur connecté | `auth` | `/dashboard`, `/profile`, `/notifications`, `/documents/{doc}` (show/stream/download) |
| Superadmin | `role:superadmin` | `/superadmin/structures` |
| Admin | `role:admin` | `/admin`, `/reports`, `/finance`, `/planning`, `/training` (skills, exams), `/recyclage`, `/settings`, `/store`, `/crm`, `/dossiers`, dossier ZIP, vehicles (create/delete), instructors (CUD) |
| Admin + Moniteur | `role:admin\|moniteur` | `/students`, `/sessions/{s}/presence`, `/training/students/{s}/evaluation`, `/quiz/students/{s}/results`, `/fleet` (lecture, entretien, carburant), `/instructors` (lecture, disponibilités) |
| Admin + Superadmin | `role:admin\|superadmin` | `/audit` |
| Moniteur | `role:moniteur` | `/moniteur/*` |
| Élève | `role:eleve` (+ `otp.verified`) | `/eleve/*`, `/quiz/*` |

Middlewares globaux du groupe `web` : `ResolveTenant`, `EnsureUserIsActive`. Alias : `role` (Spatie), `otp.verified`. Santé : `/up`. Routes API : aucune (`docs/api.md` existe mais pas de route `api`).

---

## 6. Base de données

49 migrations (du 21/07 au 27/08/2026). Tables principales : `structures`, `users` (+ `is_active`), `roles/permissions` (Spatie), `students` (+ champs de soumission de dossier), `student_registration_links`, `email_otps`, `required_document_types`, `documents` (+ champs de revue), `training_packages`, `invoices`, `payments` (+ annulation), `ledger_entries`, `lesson_sessions` (+ `vehicle_id`), `skills`, `skill_progress`, `exams`, `quiz_questions/options/attempts/attempt_answers`, `vehicles`, `maintenance_logs`, `fuel_logs`, `suppliers`, `products` (+ catalogue), `stock_movements`, `orders`, `order_items`, `purchase_orders`, `purchase_order_items`, `leads`, `notifications`, `audit_logs`, `instructors`, `instructor_availabilities`, `settings`, `recyclage_entries`, `telescope_entries`, `cache`, `jobs`.

Règles : `structure_id` en première colonne après `id()` avec FK vers `structures` ; modification de colonne = restater tous les attributs ; casts d'enums dans `casts()`.

Seeders : `RoleSeeder` (4 rôles uniquement ; les permissions sont ajoutées domaine par domaine), `DatabaseSeeder`. Factories par domaine dans `Database/Factories/`.

---

## 7. Design frontend

### 7.1 Deux systèmes visuels
1. **Landing (`welcome.blade.php`, `components/landing/*`)** — palette « route » : `ink #0B1220`, `route #1E40AF`, `signal #F2790A` (orange ; 600/700 pour texte blanc sur fond plein, conformité AA), `cream #FAF8F4`, `asphalt #0E1526`, `line #E6E2D8`. Typo display *Barlow Condensed* (esprit panneau routier), mono *IBM Plex Mono*. Animations `dash-drift`, `fade-up`. Composants : `feature-card`, `pricing-card`, `route-step`, `why-card`, `faq-item`.
2. **App authentifiée — « Soft Neumorphism »** défini par variables CSS dans `resources/css/app.css` (triplets RGB pour `rgb(var(--x) / <alpha-value>)`).

### 7.2 Tokens de l'app
- **Marque** : `brand-navy` 950/900/800/700 (`#061C30 → #16466D`), `brand-green` 100/400/500/600/700 (CTA primaire = vert 700 `#08785C`, contraste AA).
- **Sémantiques** (volontairement ≠ vert de marque) : success `#159A6C`, warning `#D98B18`, danger `#D64545`, info `#2388B5`.
- **Surfaces** : `background`, `surface`, `surface-elevated`, `surface-inset`, `border`, `content` (+ `secondary`/`muted`), `primary`, `secondary`.
- **Rayons** : `ui-xs 6px · sm 10 · md 14 · lg 18 · xl 24 · pill`.
- **Ombres** : `soft-sm`, `soft`, `soft-hover`, `inset`, `inset-focus` (anneau vert 3 px).
- **Dark mode** : stratégie `class` sur `<html>` (`:root.dark`), fond navy `#061522`, accent vert ; basculé par `components/theme-toggle`, initialisé sans flash par `theme-init-script`.
- Police de l'app : Inter. Icônes via `<x-icon name="…">` (jamais de glyphes ← →, voir mémoire projet).

### 7.3 Layouts
- `layouts/app.blade.php` : sidebar repliable (état dans `localStorage` `sidebarCollapsed`, `lg:pl-72` / `lg:pl-28`), `mobile-drawer`, `topbar`, slot principal. Partials : `layouts/partials/{sidebar,mobile-drawer,topbar}`.
- `layouts/guest.blade.php` pour l'authentification.
- Pages d'erreur dédiées : 403, 404, 419, 422, 429, 500, 503.

### 7.4 Bibliothèque de composants Blade (`resources/views/components`)
`alert`, `avatar`, `badge`, `breadcrumb`, `button` (+ `primary/secondary/danger/icon-button`), `card`, `dialog`, `modal`, `dropdown`, `empty-state`, `empty-table-row`, `error-state`, `loading-state`, `filter-bar`, `icon`, `kpi-card`, `stat-card`, `page-header`, `pagination`, `planning-grid`, `planning-session-card`, `select`, `table`, `tabs`, `text-input`, `textarea`, `input-label`, `input-error`, `tooltip`, `nav-link`, `sidebar-link`, `brand/logo`, `theme-toggle`.

Un test `tests/Feature/DesignSystem` sert de garde-fou (grep guard) pour le respect du design system.

### 7.5 Vues par rôle
`dashboard`, `admin/`, `moniteur/` (dashboard, agenda, feuille de route), `eleve/` (dashboard, planning, progression, paiements, dossier, OTP), plus un dossier de vues par domaine (`students`, `finance`, `scheduling`, `training`, `fleet`, `store`, `crm`, `documents`, `instructors`, `recyclage`, `settings`, `audit`, `tenancy`, `users`, `register`, `emails`, `profile`).

### 7.6 Interactivité
Alpine.js uniquement (menus, modales, sidebar, thème). Pas de bundle JS lourd : `app.js` = Alpine + axios.

---

## 8. Configuration

| Fichier | Rôle |
|---|---|
| `bootstrap/app.php` | routage web/console, `/up`, middlewares (`ResolveTenant`, `EnsureUserIsActive`, alias `role`, `otp.verified`), priorité tenant avant bindings |
| `bootstrap/providers.php` | `AppServiceProvider`, `TelescopeServiceProvider` |
| `config/` | `app, auth, cache, database, filesystems, logging, mail, permission, queue, services, session, telescope` |
| `vite.config.js` | entrées `resources/css/app.css` + `resources/js/app.js`, `refresh: true` |
| `tailwind.config.js` | `darkMode: 'class'`, thème étendu décrit en §7, plugin forms |
| `postcss.config.js` | tailwind + autoprefixer |
| `phpunit.xml` | suites Unit / Feature / Architecture ; env de test : MySQL `autoecole_jh_laravel_test`, cache `array`, session `array`, queue `sync`, mail `array`, Telescope désactivé |
| `.github/workflows` | CI : MySQL 8, PHP 8.5, Node 20, `composer install`, `npm ci`, `npm run build`, tests |
| `boost.json` / `.mcp.json` | Boost : agents `claude_code` + `copilot`, skills laravel-best-practices / pest-testing / tailwindcss-development |
| `composer.json` scripts | `setup`, `dev` (serve + queue:listen + pail + vite via concurrently), `test` |

Variables d'application spécifiques : `SMS_DRIVER`, `STUDENT_REGISTRATION_LINK_TTL_DAYS`, `EMAIL_OTP_EXPIRY_MINUTES`, `EMAIL_OTP_MAX_ATTEMPTS`.

### Commandes utiles
```bash
composer run dev                  # serveur + queue + logs + vite
php artisan test --compact        # suite complète
php artisan test --compact --filter=DomainBoundariesTest
vendor/bin/pint --dirty --format agent
php artisan route:list
php artisan fleet:check-alerts
php artisan import:legacy-students {structure} {path}
```

---

## 9. Tests

~6 100 lignes de tests Feature répartis par domaine (Audit, Auth, CRM, Console, DesignSystem, Documents, Finance, Fleet, Instructors, Notifications, Recyclage, Reports, Scheduling, Settings, Store, Students, Tenancy, Training, Users, legacy-import, legacy-planning) + Unit (Recyclage, Scheduling, Store, Students, Training, Users) + Architecture (`DomainBoundariesTest`, `ErrorPagesTest`). Base de test MySQL séparée ; règle projet : ne jamais `migrate:fresh/refresh/wipe` la base principale, migrations additives uniquement.

---

## 10. Documentation existante

`README.md`, `CLAUDE.md`, `AGENTS.md`, `CHANGELOG.md`, `CONTRIBUTING.md`, `SECURITY.md`, `fixs.md` (bugs hérités de l'ancienne app), `docs/` (`architecture`, `database`, `deployment`, `development`, `installation`, `api`, `guide-utilisation-client`, `parcours-eleve-etapes`), `docs/superpowers/` (specs/plans), `Promptset/`, `prompts/`.

---

## 11. Points d'attention relevés

1. **Secrets en clair dans le dépôt** : le mot de passe MySQL root figure dans `phpunit.xml` et dans le workflow CI. Acceptable pour une base de CI jetable, mais à ne pas réutiliser ailleurs. Vérifier que `.env` (identifiants SMTP, Redis) reste hors du dépôt.
2. **Telescope enregistré dans `bootstrap/providers.php`** sans condition d'environnement apparente : s'assurer qu'il est désactivé / protégé en production.
3. **Redis obligatoire en local** (session, cache, queue) alors que `phpunit.xml` utilise `array`/`sync` : un Redis arrêté bloque le dev mais pas les tests.
4. **`--no-reload` dans `composer.json`** (modification non commitée) est placé sur `concurrently` au lieu de `php artisan serve` ; il ne fait rien là. Il doit être sur la commande `serve` pour activer `PHP_CLI_SERVER_WORKERS`.
5. **Avertissement Xdebug** (`Could not connect to debugging client`) sur chaque processus PHP : `XDEBUG_MODE=off` en dev supprime le bruit.
6. **SMS non branché** : seul `LogSmsGateway` existe ; l'envoi réel nécessite un fournisseur (Airtel/Moov/agrégateur).
7. **Pas d'API publique** malgré `docs/api.md` ; Sanctum serait l'extension prévue.
8. **Permissions Spatie** : seuls les rôles sont seedés ; l'autorisation repose sur rôles + Policies, pas sur des permissions fines.
9. **Roadmap** : Phase 6 (Reports + bascule, dépréciation de l'ancien dépôt) en cours.
10. **Dossier `app/Domain/Core/` vide** alors que la doc place le noyau dans `Tenancy`.
