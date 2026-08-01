# Admin-curated marketplace — all 8 phases

Built against `demigod412/remot` at current `main`, inside `backend/`.
Every PHP file here passes `php -l` (PHP 8.3.6). **None of it has ever been executed.** See "Before you trust this" below.

---

## Your decisions, as implemented

| Decision | Where it lives |
|---|---|
| Application price **per category** | `work_categories.application_cost` (migration 0066), inherited via `Work::$application_cost` |
| **API off completely** | `EnsureApiEnabled` on the whole `/api/v1` group; 404s. Reversible with `JOBSTATION_ENABLE_API=true` |
| **One application per task**, many tasks per worker | Unique index `(work_id, worker_id)` (0068); `allow_multiple_submissions` forced off (0070) |
| **Job board + third-party gigs abolished** | `enable_user_gigs` / `enable_job_board` flags, enforced by `FeatureEnabled` middleware on the route groups |
| Work rejected → **fee kept** | `TaskReviewService::rejectSubmission()` — no refund path |
| Missed deadline → **cancel, free slot, fee kept** | `delivery_status = 5 (expired)`, which drops it out of `occupyingSubmissions()` |
| Existing users → **keep access** | `must_change_password` defaults false, so nobody is locked out |

---

## Where the files go

All paths relative to `backend/`. Files marked **REPLACES** are minimal diffs against the current version — diff before committing.

**Migrations** (all new): `0064`–`0070` in `database/migrations/`

**Models**: `MembershipApplication.php`, `AdminActivityLog.php` (new) · `User.php`, `Work.php`, `WorkCategory.php`, `WorkSubmission.php` (**REPLACES**)

**Services** (`app/Services/`): `MembershipService.php`, `TaskApplicationService.php`, `TaskReviewService.php`, `ActivityLogger.php`, `ApplicationException.php`, `Payment/Drivers/NowPaymentsGateway.php` (new) · `CoinService.php`, `NotifyService.php` (**REPLACES**)

**Controllers**: `Web/MembershipApplicationController.php`, `Admin/MembershipApplicationController.php`, `Admin/TaskReviewController.php`, `Admin/AuditLogController.php`, `User/TaskController.php`, `User/PasswordChangeController.php`, `Payment/NowPayments/ProcessController.php` (new) · `Admin/UserController.php`, `Admin/CashoutController.php`, `Admin/WorkCategoryController.php`, `SecureFileController.php` (**REPLACES**)

**Requests** (`app/Http/Requests/`, new): `MembershipApplicationRequest.php`, `TaskDeliveryRequest.php`, `TaskResultRequest.php`

**Middleware** (new): `EnsureApiEnabled.php`, `FeatureEnabled.php`, `ForcePasswordChange.php`

**Console**: `ProcessWorkTimers.php` (**REPLACES** — rewritten off the new columns)

**Views** (new): `web/membership/{apply,status}`, `admin/membership/{index,show}`, `admin/submissions/{review,review-show}`, `admin/audit/index`, `user/tasks/{index,show}`, `user/auth/force-password-change`

**Seeder** (new): `database/seeders/MarketplaceSeeder.php`

**Tests** (new): `tests/Feature/TaskLifecycleTest.php` (15 tests), `tests/Feature/MembershipApplicationTest.php` (7 tests)

**Config / routes / bootstrap** (**REPLACES**): `config/jobstation.php`, `routes/{web,user,admin,ipn}.php`, `bootstrap/app.php`

---

## .env additions

```
JOBSTATION_ENABLE_USER_GIGS=false
JOBSTATION_ENABLE_JOB_BOARD=false
JOBSTATION_ENABLE_API=false
JOBSTATION_INVITE_ONLY=true
JOBSTATION_TASK_REVIEW_HOURS=48
JOBSTATION_MAX_REVISIONS=3
```

## Run order

```bash
git checkout -b feature/admin-curated-marketplace
composer install
php artisan migrate
php artisan db:seed --class=MarketplaceSeeder
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Do this BEFORE anything else:
php artisan test --filter=TaskLifecycleTest
```

Two migrations interrupt on purpose:

1. **0068 throws** if duplicate `(work_id, worker_id)` pairs exist. Resolve by hand, keep the authoritative row, re-run.
2. **0067 may write** `storage/app/migration-review/0067-ambiguous-submissions.json` — legacy `status=3` rows with no `submitted_at`, where the old data can't distinguish "application rejected" from "work rejected". Review them.

Also set the cron if it isn't already running: `php artisan jobstation:process-timers` every 5–15 minutes. It handles auto-approval and deadline expiry, so without it nothing times out.

---

## Before you trust this

There is no `vendor/` in the sandbox this was built in (Packagist is firewalled), so `artisan`, `migrate` and PHPUnit never ran. **Syntax-clean is not working.** What I could verify mechanically: all 60 PHP files lint; every `view()` target exists; all 24 new route names resolve through their nested groups; every referenced `App\` class and global helper exists; every model field used is in the relevant `$fillable`.

What I could not verify: runtime behaviour, query correctness, Blade rendering, whether the 22 tests pass.

Budget a few hours of fixing. Expect things like a decimal format mismatch in a test assertion, a missing `use` at runtime, a Blade variable that's null in an edge case. That's normal at this size.

**The gate that matters:** if `TaskLifecycleTest` goes green, the money logic is sound. If it doesn't, fix that before pointing this at a live database. Everything else is cosmetic by comparison.

---

## Design notes worth knowing

**The legacy `status` column is now a derived mirror.** `WorkSubmission::deriveLegacyStatus()` runs on every save. Old scopes and reports keep querying `status` and keep working. New code reads `application_status` / `delivery_status`. The mapping is lossy on purpose — legacy code only understood four states.

**`fee_paid` and `fee_reference` are stored per submission**, not re-read from the category. If you change a category's price, refunds on older applications still reverse what was actually charged. `CoinService::refund()` is keyed on that reference and refuses to run twice, so a double-clicked reject can't pay out twice.

**Commission is booked to `user_id = 0`.** It isn't anyone's balance. Worker net credit and platform commission are written in the same transaction, so the split always reconciles to gross.

**Slots count all live applications**, not just approved ones — otherwise 5 slots can accumulate hundreds of paying applicants who'll never earn. Rejected and expired applications release their slot.

**Two corrections to the original spec, carried forward:**
- Phase 6's "separate checkout + webhook classes" doesn't match this codebase. All 13 gateways are one `ProcessController` (`returnPay`/`cancel`/`ipn`) plus a driver class, with credentials in the `payment_channels` table, not config. NOWPayments follows the real pattern.
- Phase 3's plan for closing self-registration (change the nav link) closes nothing. The fix was hanging the already-existing but unused `AllowRegistration` middleware on `dashboard/register`, which is what `routes/user.php` now does.

---

# Addendum — result schemas, worker accountability, applicant seeding

Three additions on top of the 8 phases. All lint clean, none executed.

## New migrations

`0071` result schema on `work_categories` · `0072` display boost on `works` · `0073` `strikes_cleared_at` on `users`

## 1. Per-category result schema (optional)

`work_categories.result_schema` is **nullable**, so nothing changes until you fill it in. With no schema, a result only needs to be valid non-empty JSON, exactly as before. Once set, `TaskResultRequest` rejects non-conforming uploads before they ever reach your review queue.

`ResultSchemaValidator` is a hand-written subset of JSON Schema, not the real thing — a full implementation would mean a Composer package. Supported: `type`, `required`, `properties`, `items`, `enum`, `min`, `max`, `min_length`, `max_length`, `min_items`, `max_items`, `pattern`, `nullable`.

It also self-validates: `WorkCategoryController` refuses to save a malformed schema, because a broken schema would silently reject every submission afterwards.

Example to adapt when your format is settled:

```json
{
  "type": "object",
  "required": ["task_id", "results"],
  "properties": {
    "task_id": { "type": "string", "pattern": "^[A-Z0-9-]{4,32}$" },
    "results": {
      "type": "array",
      "min_items": 1,
      "items": {
        "type": "object",
        "required": ["label"],
        "properties": {
          "label":      { "type": "string", "enum": ["yes", "no", "unclear"] },
          "confidence": { "type": "number", "min": 0, "max": 1 }
        }
      }
    }
  }
}
```

Leave `schema_strict` off while iterating — on, it rejects any key not declared.

## 2. Worker accountability

Strikes are **counted from `work_submissions`**, never cached, so they can't drift from the records. Abandonment weighs 3, rejection weighs 1, because an abandoned task holds a slot for the full window and gives you nothing to review.

Ships permissive at 6 strikes over 60 days (two abandonments, or six rejections). Tune in `.env`:

```
JOBSTATION_STRIKE_WINDOW_DAYS=60
JOBSTATION_MAX_STRIKES=6
JOBSTATION_ABANDON_WEIGHT=3
JOBSTATION_REJECT_WEIGHT=1
```

`JOBSTATION_MAX_STRIKES=0` disables the block entirely — sensible for your first weeks, while you learn what normal looks like.

The gate runs *before* the fee is deducted, so a blocked worker is never charged. Clearing strikes sets `strikes_cleared_at` rather than deleting submissions, so history stays intact and the action is audited.

## 3. Seeded applicant count

`works.display_application_boost` is added to the applicant number shown publicly, so a new task doesn't read as dead.

**It is display-only and never touches slot arithmetic.** `slots_remaining`, `occupyingSubmissions()` and the slot cap in `TaskApplicationService` all ignore it. A 100-slot task with an 80 boost still accepts 100 real workers. Two tests lock this in — if someone later "fixes" the boost into the slot maths, they fail.

It's applied to "Workers applied" and deliberately **not** to "Spots available." Inflating popularity is ordinary marketplace practice; inflating scarcity to someone about to spend a non-refundable fee is a different thing, and both the FTC and Nigeria's FCCPA treat fake scarcity as deceptive.

## Blade partials — you need to include these

The three partials in `resources/views/admin/partials/` are **not wired in yet**, because dropping them into your existing forms blindly risked breaking layouts I can't render. Each has include instructions in its header comment:

- `category-marketplace-fields.blade.php` → the create and edit forms in `admin/categories/index.blade.php`
- `work-display-boost-field.blade.php` → the admin task create/edit form, near `worker_slots`
- `user-reliability-card.blade.php` → `admin/users/show.blade.php` (`$reliability` is already passed by the controller)

Until you include the category partial, the new columns keep their defaults (0% commission, 0 cost, open to both) and the schema stays null.

## Tests

`ResultSchemaAndReliabilityTest` — 24 tests covering schema conformance, type edge cases (booleans aren't numbers, JSON objects vs arrays), schema self-validation, strike weighting and windowing, the fee-not-charged-when-blocked path, and the two display-boost isolation guarantees.

```bash
php artisan test --filter=ResultSchemaAndReliabilityTest
```
