# Account Management (Users domain)

Admins manage every account in their tenant — admin, moniteur, and eleve —
from one screen: **Paramètres > Comptes utilisateurs** (`/settings/users`).

## What this does and doesn't do

- Creates a `User` row with a role assigned via `spatie/laravel-permission`.
  This is the *only* place in the app staff can create a `moniteur` account —
  `/instructors` still owns the professional profile (license number,
  specialties, availabilities) layered on top of one; it has never created
  the underlying login account itself (see `InstructorController::store()`,
  which requires an existing `user_id`).
- For an `eleve` account, optionally links it to an existing `Student` row
  that has no `user_id` yet — closing the gap where a student created via
  the admin `/students` screen (`EnrollmentService::register()`) never got a
  login account, unlike the public self-registration flow
  (`PublicStudentRegistrationService`), which is the only other place a
  `User`+`Student` pair is created together.
- Triggers Laravel's standard password-reset email (`Password::sendResetLink()`)
  both at account creation and on demand — no temporary password is ever
  chosen by an admin or shown on screen. `email_verified_at` is set
  immediately at creation (see `UserManagementService::createAccount()`'s
  docblock for why this doesn't need the self-registration OTP flow).
- Deactivates/reactivates an account (`users.is_active`) rather than
  deleting it — a deactivated user can't log in (`LoginRequest`) and an
  already-open session is killed on its very next request
  (`EnsureUserIsActive` middleware, appended globally to the `web` group).

## Tenant isolation

Every query goes through `User`'s `BelongsToTenant` global scope; `UserPolicy`
re-checks `structure_id` explicitly on top of that for `update` (reset/
deactivate/reactivate), matching the pattern used by every other tenant-scoped
policy in this app. `UserManagementService::createAccount()`'s student-linking
lookup is scoped the same way — a `student_id` belonging to another tenant
404s via `ModelNotFoundException`, never silently ignored or cross-linked.

Because `User` carries the same `BelongsToTenant` global scope, route-model
binding on `{user}` (used by the reset-password/deactivate/reactivate routes)
already excludes another tenant's user before the request even reaches the
controller — those actions 404, not 403, on a cross-tenant id. This matches
the convention used everywhere else in the app (students, vehicles, invoices,
leads, scheduling, documents, …): a 403 only appears when the target row *is*
visible but the policy still denies the specific action (e.g. a moniteur
trying to view a student not assigned to them). `UserPolicy::update()`'s own
`structure_id` check is therefore defense-in-depth on this path — it can't
actually be reached via these routes, but keeps the policy correct if it's
ever called directly (e.g. from a future API or Nova-style resource that
bypasses route binding).

## Residual risks

### Password-reset tokens aren't tenant-scoped

`password_reset_tokens` is keyed by plain `email` (Laravel's default schema),
and `users` is only unique per `(structure_id, email)` — two different
tenants can share an admin's email address. If they do, which tenant's
account actually gets its password changed when the reset link is clicked is
ambiguous, since the link is a public route with no `TenantContext` active.
This is a pre-existing gap in the app's Breeze password-reset flow (already
true today for any two tenants sharing an email), not something this feature
introduces — fixing it would mean redesigning Laravel's password broker to be
tenant-aware, which is out of scope here. In practice this only matters if
the same person (or a coincidence) shares one email across two different
driving schools' accounts on this platform.

### Password-reset email sent inside the account-creation transaction

`UserManagementService::createAccount()` calls `Password::sendResetLink()`
inside the same `DB::transaction()` that creates the `User` row. This is a
deliberate but imperfect choice (flagged and accepted during review): if the
transaction failed to commit for some reason *after* the email was already
sent — the reset-link email itself isn't transactional and can't be rolled
back — an admin's freshly-invited teammate could receive a reset-password
link for a user that never actually persisted. This requires a commit
failure after every prior statement in the transaction already succeeded, so
the probability is low. It hasn't been fixed as part of this plan; a future
fix would move the notification to an `afterCommit()`-dispatched listener or
queued job.

### `is_active` regression in public self-registration (fixed)

While building this feature, `PublicStudentRegistrationService::register()` —
a pre-existing, unrelated flow used by the public student self-registration
form — was found creating its `User` without `is_active` in the `create()`
array. Eloquent's `create()` doesn't refetch the model after insert, so the
in-memory instance read `is_active` as `null`, which this feature's new
`is_active` boolean cast then evaluated as `false`. Combined with this
feature's new global `EnsureUserIsActive` middleware, that meant a visitor
who had just self-registered would have their own brand-new session killed
on their very next request. This was caught and fixed during this plan (a
one-line `'is_active' => true` added to that `create()` call) rather than
being a new bug this feature introduces — noted here because it's a real
interaction between this feature and an existing, otherwise-unrelated flow
that a future reader might otherwise be surprised to find already resolved.
