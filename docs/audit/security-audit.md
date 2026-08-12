# Audit sécurité — AutoGest-Laravel

Date : 2026-08-12
Périmètre : authentification, CSRF/XSS/validation, uploads, données financières.
Méthode : lecture statique + revue ciblée du code d'auth, de validation et d'upload. Pas de test d'intrusion dynamique (application non démarrée dans cet environnement).

---

## 1. Authentification

**[INFO] SEC-01 — Authentification Breeze standard, correctement configurée**
- Domaine : Auth
- Description : Laravel Breeze (stack Blade) fournit login/register/logout/reset password/email verification. Rate limiting sur le login : 5 tentatives, clé `email|ip` (`LoginRequest::throttleKey()`). Email verification et reset password protégés par `throttle:6,1` + lien signé (`signed` middleware).
- Impact : positif, conforme aux bonnes pratiques Laravel.
- Preuve : `app/Http/Requests/Auth/LoginRequest.php:61-85`, `routes/auth.php:44-50`.
- Statut : OK

**[INFO] SEC-02 — Gate tenant sur le login**
- Domaine : Auth / Multi-tenancy
- Description : `AuthenticatedSessionController::store()` rejette la connexion si le `Structure` (tenant) de l'utilisateur n'est pas `Active`, avec message spécifique par statut. Les super-admins (`structure_id = null`) ne sont pas concernés.
- Preuve : `app/Http/Controllers/Auth/AuthenticatedSessionController.php:33-53`, testé par `tests/Feature/Auth/TenantGatedLoginTest.php`.
- Statut : OK

**[LOW] SEC-03 — Pas de rate limiting global sur le groupe `web`**
- Domaine : Auth / Infrastructure
- Description : seuls certains endpoints (login, email verification, password confirm) ont un throttle explicite. Aucun throttle générique n'est appliqué à l'ensemble du groupe `web` dans `bootstrap/app.php`.
- Impact : les endpoints métier (création de facture, envoi de formulaires) restent exposés à un flood applicatif basique sans limite de débit — risque faible sur une app B2B interne mais à surveiller si l'inscription publique (`StructureRegistrationController`) est exposée publiquement sans captcha.
- Preuve : `bootstrap/app.php` (aucun throttle générique déclaré).
- Solution recommandée : ajouter un throttle raisonnable sur les formulaires publics (inscription structure), et envisager un captcha si des abus sont constatés en production.
- Statut : **CORRIGÉ (2026-08-12)** — `throttle:6,1` ajouté sur `POST /register` (inscription établissement) et `POST /forgot-password` (envoi d'email non throttlé auparavant — vecteur de mail-bombing). Le reste des endpoints métier authentifiés reste sans throttle générique (risque jugé faible pour une app B2B interne) ; un captcha sur l'inscription publique reste une amélioration future si des abus sont constatés en production.

## 2. Autorisation (RBAC / Policies)

Voir `docs/audit/multi-tenancy-audit.md` pour le détail complet des policies et de leur cohérence tenant. Résumé sécurité :

**[INFO] SEC-04 — Défense en profondeur dans les Policies**
- Description : la plupart des Policies re-vérifient explicitement `$model->structure_id === $user->structure_id` en plus du scope global Eloquent — documenté dans le code comme fermeture volontaire d'anciens bugs IDOR de l'application legacy.
- Preuve : `app/Domain/Students/Policies/StudentPolicy.php:8-15` (commentaire explicite).
- Statut : OK — bonne pratique à répliquer partout (voir TECH-02 pour les policies manquantes).

## 3. CSRF / XSS

**[INFO] SEC-05 — Aucune sortie Blade non échappée dangereuse détectée**
- Domaine : Global
- Description : seules deux occurrences de `{!! !!}` existent, dans `resources/views/welcome.blade.php:342,361`, et rendent des chaînes SVG **statiques codées en dur dans le même fichier** (icônes de la landing page), pas des données utilisateur. Aucun `Html::raw` ni `innerHTML` non contrôlé trouvé dans `app/` ou `resources/views/`.
- Impact : pas de vecteur XSS stocké/réfléchi identifié via ce pattern.
- Preuve : recherche exhaustive de `{!! !!}`, `Html::raw`, `innerHTML` sur `app/` et `resources/views/`.
- Statut : OK

**[INFO] SEC-06 — CSRF standard Laravel/Breeze**
- Description : pas d'exception `VerifyCsrfToken::$except` ni de désactivation trouvée dans `bootstrap/app.php`. Le `@csrf` de Breeze est présumé présent sur tous les formulaires scaffoldés (non vérifié formulaire par formulaire dans cette passe).
- Solution recommandée : lors de l'étape 8 (audit CSRF/XSS/validation du plan), faire une passe exhaustive `grep -L "@csrf" resources/views/**/*.blade.php` contenant un `<form method="POST">` pour confirmer à 100%.
- Statut : À vérifier de façon exhaustive (non bloquant, aucun indice contraire trouvé)

## 4. Validation des entrées

**[HIGH] SEC-07 — Validation `exists:users,id` non contrainte au tenant (instructor_id)**
- Domaine : Students
- Description : voir TECH-04 / MT-03. `StoreStudentRequest` valide seulement l'existence de l'utilisateur, pas son appartenance au tenant courant.
- Impact : potentiel contournement multi-tenant via manipulation de formulaire (POST direct avec un `instructor_id` d'un autre établissement). Sévérité HIGH tant que non confirmé/infirmé par du code applicatif compensatoire.
- Solution recommandée : `Rule::exists('users', 'id')->where(fn ($q) => $q->where('structure_id', TenantContext::id()))`.
- Statut : **CORRIGÉ (2026-08-12)** — voir `multi-tenancy-audit.md` MT-03.

**[MEDIUM] SEC-08 — Pas de plafond de paiement (overpayment)**
- Domaine : Finance
- Description : `StorePaymentRequest` valide uniquement `amount: required|numeric|min:0.01`, sans `max:` lié au solde restant dû (`Invoice::balanceDue()`). `PaymentService::statusFor()` traite tout `amount_paid >= amount_due` comme `Paid`, sans notion de trop-perçu.
- Impact : un paiement peut être enregistré pour un montant très supérieur à la dette réelle, sans blocage ni avertissement, et sans mécanisme de remboursement/avoir pour corriger — voir aussi `docs/audit/business-workflow.md` FIN-01.
- Preuve : `app/Domain/Finance/Http/Requests/StorePaymentRequest.php`, `app/Domain/Finance/Services/PaymentService.php:56-63`.
- Solution recommandée : ajouter une validation applicative (pas nécessairement bloquante, mais au minimum un warning de confirmation) et envisager un concept de crédit/avoir si le sur-paiement est un cas métier réel (acompte, arrondi).
- Statut : **CORRIGÉ (2026-08-12)** — voir `business-workflow.md` FIN-01.

## 5. Uploads / stockage de documents

**[INFO] SEC-09 — Documents stockés sur disque privé, jamais public**
- Domaine : Documents
- Description : `DocumentService` force `$file->store('documents', 'local')` — le disque `local` a pour racine `storage_path('app/private')` (non exposé via `/storage`), contrairement au disque `public`. Le téléchargement passe systématiquement par `DocumentController::download()` qui vérifie explicitement `Auth::user()->can('view', $document)` avant de streamer le fichier — pas d'URL prévisible/publique.
- Impact : positif — conforme à l'exigence CLAUDE.md §9 (documents privés jamais accessibles par URL publique prévisible).
- Preuve : `app/Domain/Documents/Services/DocumentService.php:12-19,34`, `app/Domain/Documents/Http/Controllers/DocumentController.php:54-61`.
- Statut : OK

**[LOW] SEC-10 — Pas d'URL signée/temporaire pour les téléchargements**
- Domaine : Documents
- Description : le téléchargement est protégé par policy à chaque requête (pas d'URL signée avec expiration), ce qui est suffisant fonctionnellement mais diffère du pattern "Signed/Temporary URL" recommandé en §9 CLAUDE.md pour un accès délégué (ex. partage d'un lien vers un tiers, futur usage mobile).
- Impact : aucun aujourd'hui (pas de cas d'usage de partage externe identifié) — à anticiper si un besoin de partage hors-session apparaît (ex. envoi d'un certificat par email/SMS).
- Solution recommandée : documenter le choix actuel comme suffisant pour l'usage interne ; prévoir `Storage::temporaryUrl()` uniquement si un besoin de partage externe apparaît.
- Statut : Accepté pour l'instant

**[MEDIUM] SEC-11 — Validation MIME/taille des uploads non confirmée exhaustivement**
- Domaine : Documents, Fleet (documents véhicules), Instructors (documents moniteurs)
- Description : `StoreDocumentRequest` n'a pas été inspecté champ par champ pour confirmer la présence systématique de règles `mimes:`/`max:` sur chaque type de document (permis, certificats, documents véhicule/moniteur).
- Impact : si une règle `mimes:` manque sur un type de document, upload de fichiers exécutables/scripts possible (bien que le stockage privé + non-exécution PHP dans `storage/` limite fortement l'impact réel).
- Solution recommandée : lors de l'étape 8 du plan, relire `StoreDocumentRequest::rules()` et toute autre requête d'upload (photo élève, logo structure) pour confirmer `mimes:jpg,png,pdf` + `max:<Ko>` sur chacune.
- Statut : **VÉRIFIÉ (2026-08-12) — aucune lacune trouvée.** `StoreDocumentRequest::rules()` contient déjà `'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp']`. C'est le seul point d'upload de fichier de l'application (`grep` sur `'file'`/`type="file"` dans `app/` et `resources/views/` ne trouve que ce formulaire, réutilisé par les pages Élèves et Flotte). Aucune correction nécessaire.

## 6. Erreurs et fuite d'information

**[INFO] SEC-12 — `withExceptions()` vide dans bootstrap/app.php**
- Domaine : Global
- Description : aucun rendu d'exception personnalisé n'est configuré ; l'application repose sur le comportement par défaut de Laravel (page d'erreur Whoops en debug, page générique 500 sinon), contrôlé par `APP_DEBUG`.
- Impact : tant que `APP_DEBUG=false` est garanti en production (voir §32 CLAUDE.md), aucune fuite de stack trace/SQL. Aucune vérification de la configuration `.env` de production n'a été faite dans cette passe (pas d'environnement de prod disponible).
- Solution recommandée : ajouter une vérification CI/CD qui échoue le build si `APP_DEBUG=true` est détecté dans une configuration destinée à la production (étape 33 CLAUDE.md).
- Statut : À contrôler au moment du déploiement (étape 14)

---

## Synthèse des priorités sécurité

| ID | Gravité | Sujet |
|---|---|---|
| SEC-07 | HIGH | `instructor_id` non contraint au tenant |
| SEC-08 | MEDIUM | Pas de plafond sur les paiements (overpayment) |
| SEC-11 | MEDIUM | Validation MIME/taille des uploads à confirmer exhaustivement |
| SEC-03 | LOW | Pas de rate limiting générique sur le groupe web |
| SEC-10 | LOW | Pas d'URL signée pour les téléchargements (non bloquant) |

Aucune faille CSRF/XSS active identifiée. Le socle Breeze + policies + stockage privé des documents est globalement sain ; les points à corriger sont concentrés sur la **validation croisée tenant** et la **robustesse financière**, cohérent avec les priorités de la mission (multi-tenancy et finance = zones critiques).
