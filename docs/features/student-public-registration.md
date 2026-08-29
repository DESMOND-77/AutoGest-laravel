# Public Student Registration

Each auto-école can generate a public, tenant-scoped link that lets prospective
students register themselves without an account:

```
https://<app-url>/register/student?token=<64-hex-char token>
```

## Architecture

Everything lives inside the existing `Students` domain — no new tenancy
mechanism, no parallel authentication system:

```
app/Domain/Students/
├── Models/StudentRegistrationLink.php
├── Database/Factories/StudentRegistrationLinkFactory.php
├── Services/
│   ├── StudentRegistrationLinkService.php   (generate/validate/revoke/regenerate)
│   └── PublicStudentRegistrationService.php (the public registration flow)
├── Exceptions/
│   ├── InvalidRegistrationLink.php
│   └── DuplicateRegistration.php
├── Policies/StudentRegistrationLinkPolicy.php
├── Http/Controllers/
│   ├── StudentRegistrationLinkController.php   (admin: settings/student-registration)
│   └── PublicStudentRegistrationController.php (public: register/student)
├── Http/Requests/
│   ├── GenerateRegistrationLinkRequest.php
│   └── PublicStudentRegistrationRequest.php
└── Events/StudentPublicRegistrationCompleted.php

app/Listeners/NotifyAdminsOnStudentPublicRegistration.php
```

## Tenant resolution — the core rule

> **The link identifies the tenant. The client never chooses the tenant.**

There are two, deliberately different, ways `TenantContext` gets populated in
this app:

| Context | Tenant comes from |
|---|---|
| Authenticated admin (settings page) | `ResolveTenant` middleware, from `auth()->user()->structure` |
| Public registration | `StudentRegistrationLinkService::validate($token)`, called explicitly by `PublicStudentRegistrationService` |

`PublicStudentRegistrationRequest` has **no `tenant_id`/`structure_id` field at
all** — there is nothing for a client to tamper with, because it isn't part of
the request's validated shape. The only thing the request accepts that has
anything to do with a tenant is `registration_token`, a random string that the
server hashes and looks up.

Ordering matters and is enforced by
`PublicStudentRegistrationService::register()`:

```
token
  → StudentRegistrationLinkService::validate()   (no tenant context yet)
  → DB transaction + lockForUpdate() on the link row
  → re-check usability under the lock
  → TenantContext::set($link->structure)          ← tenant activates here, not before
  → duplicate check (tenant-scoped via BelongsToTenant)
  → EnrollmentService::register()                  (structure_id auto-stamped)
  → link->markUsed()
  → audit log + StudentPublicRegistrationCompleted event
  → TenantContext::clear()                         (always, via finally)
```

`Student::structure_id` is never set from request data — `BelongsToTenant`'s
`creating()` hook stamps it from `TenantContext::id()` automatically, the same
mechanism every other tenant-scoped model in this app already relies on.

## Database

`student_registration_links`:

| Column | Notes |
|---|---|
| `structure_id` | FK, cascade delete |
| `created_by` | FK to `users`, nullable |
| `label` | optional free text |
| `token_hash` | `sha256` of the plain token — **the plain token is never stored** |
| `usage_count` / `max_uses` | `max_uses` nullable = unlimited |
| `expires_at`, `revoked_at`, `last_used_at` | nullable timestamps |

No unique constraint enforces "one active link per tenant" — the business
rule (at most one active link) is enforced in
`StudentRegistrationLinkService::generate()`, which revokes any existing
active link before creating a new one. A DB constraint that the application
logic itself can't fully guarantee (e.g. across a partial rollback) would be
worse than no constraint at all, so this is deliberate.

### Why the public token lookup is tenant-scope-safe

`StudentRegistrationLink` uses `BelongsToTenant` like every other tenant
model. Its global scope only adds a `WHERE structure_id = ...` clause when
`TenantContext::hasTenant()` is true. The public token lookup
(`StudentRegistrationLinkService::validate()`) runs *before* any tenant is
resolved, so the scope contributes nothing and the lookup naturally searches
every tenant's links — exactly once, with no `withoutTenantScope()` special
case needed. Every admin-facing query on the same model runs with a tenant
already active, so it's transparently isolated like any other tenant-scoped
model.

## Token lifecycle

- Generated with `bin2hex(random_bytes(32))` — 64 hex characters, 256 bits of
  entropy, not derived from any id/timestamp.
- Only `hash('sha256', $token)` is ever written to the database.
- The plain token is returned once, by `generate()`/`regenerate()`, and
  flashed into the session for exactly one subsequent page render (the admin
  settings page shows a one-time "copy it now" warning). After that page
  load, the settings page can only show a masked placeholder — the plain
  value cannot be recovered.
- Default expiry: `STUDENT_REGISTRATION_LINK_TTL_DAYS` (default 90 days,
  `.env`-configurable, see `config/services.php`).
- `revoke()` / `regenerate()` are available to any tenant admin from
  **Paramètres → Inscription publique**.

## Validity rules

A token is usable only if, all at once:

- it resolves to a known `token_hash`;
- it isn't revoked;
- it isn't expired;
- it still has remaining uses (`max_uses` is null or `usage_count < max_uses`);
- its tenant's `status` is `Active` (not `Pending`/`Suspended`/`Deactivated`).

## Anti-enumeration

`InvalidRegistrationLink` collapses "unknown token", "revoked", "max uses
reached" and "tenant not accepting registrations" into a single generic
`invalid` reason. Only `expired` is split out as its own message, since a
link naturally ageing out reveals nothing tenant-specific. This stops a
public, unauthenticated visitor from using response differences to probe
whether a given school exists, is suspended, or how many people used a link.

Duplicate registrations (matching email or phone within the same tenant) get
the same treatment: `DuplicateRegistration`'s message never says which field
matched or names the existing student.

## Workflow integration

Public registration does **not** introduce a new "candidature" table or
bypass the existing student lifecycle. It calls the same
`EnrollmentService::register()` used by the authenticated admin "New
student" form. However, the flow is no longer a single step: a self-registered
account is created at `LifecycleStage::Prospect` / `DossierStatus::Incomplete`,
but the student is immediately logged in. They then verify their email address
via a 6-digit OTP code, which transitions them through `PreEnrollment` to
`DossierSetup`. At that stage, the student uploads the tenant's required
documents one by one. Submitting the dossier (`Validation` stage) opens it to
per-document admin review: each document is approved or rejected individually.
When the last pending document is approved, the student automatically advances
to `Enrollment` — there is no final "unlock" step, and no path that skips
administrative validation. The complete flow is described in
`docs/superpowers/specs/2026-08-23-inscription-eleve-otp-dossier-design.md`.

## Email OTP verification

A freshly self-registered account is created with `email_verified_at = null`
and immediately logged in, but `otp.verified` middleware blocks every other
eleve route (dashboard, planning, quiz, dossier) until the 6-digit code sent
to the account's email is confirmed. Codes are stored hashed (`sha256`,
mirroring `StudentRegistrationLink::token_hash`), expire after
`EMAIL_OTP_EXPIRY_MINUTES` (default 10), and lock out after
`EMAIL_OTP_MAX_ATTEMPTS` wrong guesses (default 5) — at that point the only
way forward is a resend, itself throttled to once per minute.

Verifying dispatches `StudentEmailVerified`, whose listener
(`ActivateStudentAfterEmailVerification`) chains two automatic
`LifecycleService::transitionTo()` calls with no visible intermediate state:
`Prospect → PreEnrollment → DossierSetup`.

## Dossier: required documents and per-document review

Each tenant configures its own list of required pieces
(`RequiredDocumentType`, admin-managed at **Paramètres → Pièces requises**).
A student at `DossierSetup` uploads one file per active required type
(`eleve/dossier`); each upload versions the existing `Document` row exactly
like every other document upload in this app (`DocumentService::upload()`,
extended with an optional `$requiredDocumentType` param that keys the
"previous version" lookup on `required_document_type_id` instead of
`DocumentType`, since dossier pieces share the generic `DocumentType::Other`).

"Soumettre mon dossier" transitions the student to `Validation` — server-side
gated on every active required type having at least one uploaded version,
regardless of its review status.

Review happens **per document**, not per dossier: an admin approves or
rejects each one individually from the `dossiers` queue (students currently
at `Validation`). Rejecting one immediately
sends the student back to `DossierSetup` (`Validation → DossierSetup`),
regardless of the other documents' state; approving the *last* remaining
pending/rejected active-type document advances the student to `Enrollment`.
Both directions are refused server-side (403) if the student isn't currently
at `Validation` when the review action runs — this is enforced in
`DocumentReviewController::decide()`, not just hidden in the UI.

## Concurrency

`max_uses` is enforced under `SELECT ... FOR UPDATE` on the link row inside a
DB transaction (`PublicStudentRegistrationService::register()`), not just at
the initial `validate()` call — two requests racing on the same
single-use link can both pass the first check, but only one can hold the row
lock and successfully `markUsed()`.

## Rate limiting

- `GET /register/student` (token lookups / brute-forcing): `throttle:30,1`
- `POST /register/student` (submissions): `throttle:6,1`
- Admin generate/revoke/regenerate: gated by `auth`, `role:admin`, and
  `StudentRegistrationLinkPolicy` (no separate throttle — these require an
  authenticated tenant admin already).

Both public throttles key on IP by default (Laravel's standard
`ThrottleRequests` behaviour for guest requests). This does not stop an
attacker who rotates IPs — see "Residual risks" below.

## Logging & audit

- `AuditService::log('student.public_registration_completed', ...)` on
  success, with `registration_link_id` in the metadata.
- `AuditService::log('registration_link.generated'/'regenerated'/'revoked', ...)`
  is implicit in the timestamps already on the row (`created_at`,
  `revoked_at`) plus the standard action logging pattern; see
  `StudentRegistrationLinkController` for where these actions happen.
- `Log::info('student.public_registration.completed'/'failed', ...)` with
  `structure_id`, `registration_link_id`, `student_id` — **never the plain
  token**.

## Notifications

`StudentPublicRegistrationCompleted` is dispatched on success.
`App\Listeners\NotifyAdminsOnStudentPublicRegistration` (auto-discovered,
same pattern as `NotifyAdminsOnPaymentReceived`) notifies every admin of the
tenant via the existing `AlertNotification` (database channel).

## Routes

```
GET  /register/student            public-registration.show      throttle:30,1
POST /register/student            public-registration.store     throttle:6,1
GET  /register/student/success    public-registration.success

GET  /settings/student-registration            settings.student-registration.show
POST /settings/student-registration/generate    settings.student-registration.generate
POST /settings/student-registration/regenerate  settings.student-registration.regenerate
POST /settings/student-registration/revoke      settings.student-registration.revoke
```

The `settings.*` routes carry the app's standard `auth` + `role:admin`
middleware, plus `StudentRegistrationLinkPolicy` checks in the controller.
The public routes carry neither `auth` nor `guest` — they're meant to work
regardless of whether the visitor's browser happens to have an unrelated
session.

## Tests

- `tests/Unit/Students/StudentRegistrationLinkServiceTest.php` — token
  generation/uniqueness/hashing, validate() for every rejection reason,
  single-active-link enforcement, regenerate, tenant-scoped `getActiveLink()`.
- `tests/Feature/Students/PublicStudentRegistrationTest.php` — golden path,
  invalid/expired/revoked/suspended-tenant rejections, duplicate email/phone,
  anti-tampering (client-supplied `tenant_id`/`structure_id` ignored), IDOR
  across tenants, concurrency under `max_uses = 1`, rate limiting on both
  routes, field validation.
- `tests/Feature/Students/StudentRegistrationLinkAdminTest.php` — admin
  generate/revoke/regenerate, one-time token reveal, role enforcement,
  cross-tenant IDOR on the admin management routes.

## Residual risks / accepted trade-offs

- **Rate limiting is IP-based.** A distributed attacker rotating source IPs
  can still probe tokens faster than a single-IP throttle allows. Mitigating
  this further (CAPTCHA, WAF-level throttling) is infrastructure, not
  application code, and was left out of this pass.
- **`RegistrationLinkGenerated`/`Regenerated`/`Revoked` are not separate
  Laravel event classes** — they're covered by the existing `AuditService`
  action-logging pattern instead of three new single-purpose Dispatchable
  classes. This keeps the surface area small; promoting them to real events
  is a small, backward-compatible follow-up if another listener ever needs
  to react to them.
- **No QR code / SMS / WhatsApp channel yet.** The URL is a plain
  `?token=...` query string by design, so any of those channels can wrap it
  later without touching this feature's backend.
