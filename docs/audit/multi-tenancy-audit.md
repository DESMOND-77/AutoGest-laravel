# Audit multi-tenancy — AutoGest-Laravel

Date : 2026-08-12
Priorité : CRITIQUE (cf. §5 CLAUDE.md — aucune donnée d'un établissement A ne doit être accessible par un établissement B).
Méthode : lecture statique du mécanisme de scoping, inventaire modèle par modèle, revue des Policies et du routing.

---

## 1. Mécanisme

**Trait `BelongsToTenant`** (`app/Support/BelongsToTenant.php:26-50`) :
- Ajoute un **Global Scope** Eloquent filtrant `<table>.structure_id = TenantContext::id()` dès qu'un tenant est actif.
- Auto-stamp `structure_id` à la création (`creating` hook).
- Expose `scopeWithoutTenantScope()` pour un accès explicitement non scopé (à auditer à chaque usage).

**`TenantContext`** (`app/Support/TenantContext.php`) : porteur statique en mémoire, durée de vie = la requête. `current()` **lève une exception** si aucun tenant n'est défini ("fail loudly" plutôt que fallback silencieux vers "tout voir") — bon choix de sécurité par défaut.

**Résolution** : middleware `ResolveTenant` (`app/Domain/Tenancy/Http/Middleware/ResolveTenant.php:16-32`), ajouté au groupe `web` (`bootstrap/app.php:16`). Lit `$request->user()->structure`, appelle `TenantContext::set()`, et **nettoie dans un bloc `finally`** après la réponse (évite la fuite de contexte entre requêtes dans un worker persistant type Octane — bonne pratique).

**[CRITICAL] MT-01 — Le scope tenant n'est pas actif au moment du route-model binding implicite**
- Domaine : Global (tout contrôleur utilisant `{student}`, `{invoice}`, `{vehicle}`, etc. en binding implicite)
- Description : le binding de route implicite de Laravel résout le modèle **avant** l'exécution du middleware `ResolveTenant` dans la pile. Le scope global du modèle n'est donc pas encore actif au moment de la résolution `{student}` → `Student::findOrFail($id)`. Ce point est **documenté dans le code source lui-même** (`BelongsToTenant.php:16-24`), signe que l'équipe précédente en avait conscience et a mis en place une compensation : chaque contrôleur doit appeler explicitement la Policy (`$this->authorize('view', $student)`), qui re-vérifie `$model->structure_id === $user->structure_id`.
- Impact : **la protection n'est donc pas portée par le scope tenant sur les accès single-model par URL, mais entièrement par la discipline "chaque contrôleur appelle sa policy".** Un seul contrôleur oubliant l'appel `authorize()` sur une route avec binding implicite = fuite cross-tenant totale (IDOR) pour cette ressource.
- Preuve : `app/Support/BelongsToTenant.php:16-24` (commentaire), pattern confirmé dans `StudentPolicy`, `InvoicePolicy`, etc. qui re-checkent `structure_id`.
- Comportement observé vs cible CLAUDE.md §5 : une ressource d'un autre tenant devrait idéalement renvoyer **404** ; ce mécanisme renvoie **403** (la ressource est trouvée puis l'accès refusé par la policy) — ce qui est explicitement noté comme moins bon dans la mission ("objectif recommandé : 404, pas seulement 403").
- Solution recommandée :
  1. **Vérifier exhaustivement** (grep systématique) que **chaque** contrôleur avec binding implicite appelle bien `$this->authorize(...)` avant tout accès aux données du modèle — produire une checklist par domaine.
  2. Envisager de faire échouer le binding lui-même en 404 plutôt qu'en 403, par exemple via `Route::bind()` personnalisé qui applique `TenantContext` ou via un scope de binding explicite (`Route::model()` avec une closure qui filtre par tenant), pour que la ressource d'un autre tenant n'existe tout simplement pas du point de vue de la requête.
  3. Ajouter des tests `*TenantIsolationTest` pour **tous** les domaines exposant un binding implicite (voir §3).
- Statut : **À corriger en priorité absolue avant toute campagne commerciale.**

**[HIGH] MT-02 — Middleware de résolution tenant non testable indépendamment sur les commandes Artisan**
- Domaine : Console
- Description : les commandes Artisan (`CheckFleetAlerts`, `ImportLegacyStudents`) n'ont pas de middleware HTTP ; elles appellent manuellement `TenantContext::set()/clear()` par itération sur les `Structure`. C'est correct par construction mais **repose entièrement sur la discipline du développeur de la commande** — aucun garde-fou automatique n'empêche une future commande Artisan d'oublier ce `set()` et d'agir hors contexte tenant (donc potentiellement sur toutes les données, tous tenants confondus).
- Preuve : `app/Console/Commands/CheckFleetAlerts.php:25-49`, `app/Console/Commands/ImportLegacyStudents.php:57-63`.
- Solution recommandée : documenter cette convention dans `docs/architecture.md` et envisager un test d'architecture Pest (`arch()`) qui vérifie que toute nouvelle commande Artisan manipulant un modèle `BelongsToTenant` appelle `TenantContext::set()` — ou au minimum une revue de code systématique sur ce point.
- Statut : À documenter / renforcer

**[HIGH] MT-03 — Validation `instructor_id` non filtrée par tenant (rappel SEC-07/TECH-04)**
- Domaine : Students
- Description : `StoreStudentRequest::rules()` valide `instructor_id` par `exists:users,id`, sans condition `structure_id`. Si aucun autre contrôle applicatif ne complète cette validation, un élève de l'établissement A pourrait être assigné à un instructeur de l'établissement B via manipulation du formulaire.
- Preuve : `app/Domain/Students/Http/Requests/StoreStudentRequest.php:32`.
- Solution recommandée : `Rule::exists('users', 'id')->where('structure_id', TenantContext::id())`.
- Statut : À corriger — priorité haute

## 2. Inventaire des modèles tenant-scopés

**23 modèles utilisent `BelongsToTenant`** : `User, Student, Invoice, Payment, LedgerEntry, TrainingPackage, LessonSession, Vehicle, FuelLog, MaintenanceLog, Instructor, InstructorAvailability, Lead, Document, Setting, Order, Product, Supplier, Skill, Exam, QuizQuestion, QuizAttempt, SkillProgress`.

**Exceptions documentées et jugées correctes :**
- `AuditLog` — `structure_id` nullable, volontairement **non scopé** (les logs d'audit doivent rester consultables largement, y compris pour des actions de niveau plateforme). Documenté explicitement dans le code (`app/Domain/Audit/Models/AuditLog.php:10-16`).
- `OrderItem`, `QuizOption`, `QuizAttemptAnswer` — pas de `structure_id` propre, scopés **par transitivité** via leur parent (`Order`, `QuizQuestion`, `QuizAttempt`), pattern cohérent et documenté.
- `Structure` — c'est le tenant lui-même, non applicable.

**[INFO] MT-04 — Aucun modèle "orphelin" détecté**
- Description : aucun modèle stockant manifestement des données propres à un établissement n'a été trouvé sans `structure_id` ni justification de scoping transitif documentée.
- Statut : OK — bon niveau de rigueur sur ce point précis.

## 3. Couverture de tests d'isolation tenant — écart majeur

**[CRITICAL] MT-05 — Tests d'isolation tenant absents sur la majorité des domaines**
- Domaine : Global (tests)
- Description : seuls **5 fichiers** de test contiennent le mot "tenant" : `TenantGatedLoginTest`, `InvoiceTenantIsolationTest`, `VehicleTenantIsolationTest`, `StudentTenantIsolationTest`, `StructureRegistrationTest`. La mission CLAUDE.md §5 exige explicitement des tests d'isolation pour Students, Invoice, Vehicle, Instructor, Document, Lesson (Scheduling).
- **Domaines tenant-scopés sans aucun test d'isolation dédié** : Scheduling/LessonSession, CRM/Lead, Documents, Store/Order, Instructors, Payment (seul Invoice est couvert, pas Payment directement), Training/Quiz, Settings, Reports, LedgerEntry, TrainingPackage.
- Impact : le risque décrit en MT-01 (oubli d'un `authorize()` dans un contrôleur) ne serait détecté par **aucun test automatisé** dans la plupart des domaines si une régression était introduite.
- Solution recommandée : créer, dans cet ordre de priorité (aligné sur la sensibilité des données) :
  1. `SchedulingTenantIsolationTest` (LessonSession — données de planning + accès élève/moniteur)
  2. `PaymentTenantIsolationTest` (distinct d'Invoice — vérifier l'accès direct à `payments/{id}` si une telle route existe)
  3. `DocumentTenantIsolationTest` (documents sensibles : permis, certificats)
  4. `InstructorTenantIsolationTest`
  5. `LeadTenantIsolationTest` (CRM), `OrderTenantIsolationTest` (Store), `QuizTenantIsolationTest` (Training)
  Chaque test doit suivre le pattern déjà en place dans `StudentTenantIsolationTest` : Tenant A crée une ressource, un utilisateur du Tenant B tente `GET`/`PATCH`/`DELETE` dessus et doit recevoir un refus (403 aujourd'hui, 404 recommandé après correction de MT-01).
- Statut : **Bloquant avant commercialisation.**

## 4. Policies — cohérence tenant

Toutes les Policies inspectées (`StudentPolicy`, `InvoicePolicy`, `LessonSessionPolicy`, `VehiclePolicy`, `InstructorPolicy`, etc.) re-vérifient explicitement `structure_id` en plus du scope global — bonne pratique de défense en profondeur. Deux ressources routées n'ont **pas** de Policy (`Supplier`, `Structure`) — voir TECH-02 ; leur protection actuelle repose uniquement sur le rôle, pas sur une vérification tenant par ressource (moins critique pour `Structure`, qui est justement la frontière tenant elle-même et gérée par le superadmin).

## 5. Uploads / exports / recherche

- **Documents** : stockage privé + policy-gated download (voir SEC-09) — protection tenant correcte car la policy re-vérifie `structure_id`.
- **Exports CSV** (`CsvExporter`, `ReportsController`) : routes sous `role:admin`, données issues de `ReportService` qui interroge via les modèles scopés (`Invoice`, `Exam`, `Student`) — donc scoping hérité automatiquement du Global Scope tant que `TenantContext` est actif au moment de l'export. Non testé spécifiquement par un `*TenantIsolationTest` — à ajouter.
- **Recherche** : aucun moteur de recherche dédié identifié (pas d'Algolia/Scout/Meilisearch dans composer.json) — recherche probablement faite via requêtes Eloquent standard, donc héritant du scope global. Pas de risque spécifique identifié, mais non vérifié activement.

## 6. Synthèse et checklist de correction

| ID | Gravité | Constat |
|---|---|---|
| MT-01 | CRITICAL | Route-model binding implicite résolu avant activation du scope tenant — protection repose à 100% sur l'appel policy dans chaque contrôleur |
| MT-05 | CRITICAL | Tests d'isolation tenant absents sur la majorité des domaines (10+ domaines non couverts) |
| MT-03 | HIGH | `instructor_id` non contraint au tenant dans la validation Students |
| MT-02 | HIGH | Commandes Artisan tenant-dépendantes sans garde-fou automatique |
| MT-04 | INFO | Aucun modèle orphelin — bon niveau de base |

**Conclusion** : le socle multi-tenant est **conceptuellement solide** (scope global + TenantContext fail-loud + policies en défense en profondeur), mais sa **garantie réelle repose sur une discipline manuelle** (appeler la policy dans chaque contrôleur) qui n'est vérifiée par aucun test automatisé dans la majorité des domaines. C'est l'écart le plus critique de tout l'audit vis-à-vis de l'objectif §5 de la mission — à traiter avant toute autre priorité de la roadmap (interdiction explicite de sauter cette étape, §42 CLAUDE.md).
