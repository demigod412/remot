# Session notes — integration gaps + apply page rebuild

Worked against `main`. Every diagnosis in the handover doc was checked against the
actual file before anything was changed, and every edit was grep-confirmed
afterwards.

**Not verified by running tests.** There is no PHP/Composer in the environment this
was done in and Packagist was unreachable, so `php artisan test` was never run.
PHP files were checked with `php -l`. Blade templates were checked with a
purpose-built compiler emulation (see "Blade linting" below). Treat the test suite
as unverified until you run it.

---

## 1. Corrections to the handover doc

| Doc said | Actually |
|---|---|
| `main` has moved on, reconcile before merging | `main` and `feature/admin-curated-marketplace` are byte-identical. PR #1 is merged (`2e665d7`). Nothing to reconcile. |
| Worker accountability disabled via `JOBSTATION_MAX_STRIKES=0` | `config/jobstation.php` defaults `max_strikes` to **6**, i.e. enabled. The disable must live in `.env`, which is not in the repo. Confirm before assuming strikes are off. |
| 6.2: category filtering is probably a missing UI affordance | The filter UI **already exists** in both places: a radio sidebar in `web/works/index.blade.php` (~line 66) and a `<select name="category">` in `user/works/browse.blade.php` (~line 63). `category_id` is `required` in `Admin\WorkController` validation, and categories are force-created with `status = 1`. Query layer, data layer and filter UI are all sound. See §4. |
| 6.8: `Api/Auth/RegisterController.php:75` is one known offender | There are **23** raw `increment`/`decrement('coin_balance')` call sites outside `CoinService`. See §5. |

---

## 2. Root cause fixed (doc §5) — new admin screens wired into the nav

`resources/views/admin/layouts/app.blade.php`

- **Task Review** added to the Works submenu, with a pending badge counting
  `application_status = APP_APPLIED OR delivery_status = DEL_SUBMITTED`.
- Legacy **Submissions** relabelled `Submissions (legacy)`, badge removed, and
  commented to say its approve path bypasses category commission.
- **Membership** added as a top-level link with a pending-applications badge.
- **Audit Log** added as the first entry under Reports.
- Parent `active` classes and Alpine `open` state updated so the right submenu
  expands on each of these routes.

`resources/views/user/layouts/app.blade.php`

- **My Tasks** added (`user.tasks.index`). Its absence is what made the JSON
  result upload (doc 6.6) unreachable rather than broken.

### Dead nav links not mentioned in the doc

The worker sidebar linked five routes that `FeatureEnabled` 403s server-side: the
whole **Jobs** section (`enable_job_board`) and **My Works** (`enable_user_gigs`).
Both are now gated behind their own flags with array spreads, so flipping the env
value restores the nav and the routes together instead of leaving one without the
other.

---

## 3. Free-application hole (doc 6.4 — root cause is different)

`app/Http/Controllers/User/WorkBrowseController.php::start()`

6.4 was reported as "applying immediately says start work". It was not a display
bug. `start()` was a second, parallel application path that called
`WorkSubmission::create()` directly and therefore:

- **charged no application fee at all** — no `CoinService` call, no ledger row, no
  `fee_reference`, so the coin maths could never reconcile;
- left `application_status` / `delivery_status` at their column defaults instead of
  moving through the lifecycle;
- honoured `works.allow_multiple_submissions`, retired in migration 0070;
- skipped the eligibility, user-type, KYC and reliability checks;
- redirected to the **legacy proof form**, which is why the task read as started
  before an admin had approved anything or delivered a package.

This also accounts for a slice of 6.8 that the legacy-admin-screen diagnosis does
not cover: coins were never deducted on the way *in*, not merely mis-credited on
the way out.

`start()` is now a thin wrapper over `TaskApplicationService::apply()` — the same
tested path `works.apply` uses — mirroring `User\TaskController::apply()`. The
route name is unchanged so existing links keep working.

Also in that controller: the `allow_multiple_submissions` re-apply branch is gone.
One application per worker per task is now absolute, matching the unique index.

`resources/views/user/works/detail.blade.php` now shows `lifecycle_label` instead
of "Already started", links to `user.tasks.show` instead of the legacy proof form,
states the non-refundable fee **before** the click, and offers no work-start
affordance until `isApprovedToWork()` is true.

---

## 4. Fake applicant count (doc 6.3) and reliability card

- `admin/works/create.blade.php` and `edit.blade.php` now
  `@include('admin.partials.work-display-boost-field', ...)`, passing `work`
  explicitly (`null` on create, `$work` on edit) per the `@forelse` leak rule.
  Validation already existed at `Admin\WorkController` lines 93 and 179.
- `admin/users/show.blade.php` now includes
  `admin.partials.user-reliability-card`. `Admin\UserController::show()` already
  passed `$reliability` (line 114).

Re-run these two after applying, since they assert the boost never touches slots:
`test_display_boost_inflates_shown_count_but_not_slots`,
`test_boosted_task_still_accepts_a_full_set_of_real_workers`.

---

## 5. Apply page rebuilt (doc 6.1)

`resources/views/web/membership/apply.blade.php`, plus two new partials:

- `web/membership/partials/file-drop.blade.php` — drag-and-drop zone with
  filename and human-readable size display, a Remove button, keyboard access
  (Enter/Space), and client-side extension + size checks that read their limits
  from `config('jobstation.membership.*')` so they can never drift from
  `MembershipApplicationRequest`. Dropped files are assigned onto the real
  `<input type="file">`, so submission is still a plain multipart POST with no
  AJAX, and the field degrades to a native picker without JavaScript.
- `web/membership/partials/step-heading.blade.php` — numbered step headings.

The form is now four visually separated steps, the applicant type is a pair of
selectable cards rather than bare radios, every field has inline `@error` output
and a red border on failure, and the submit button disables itself and shows a
spinner plus "do not close this page" while documents upload.

Stack unchanged: Blade + Alpine + existing CSS variables. No new frontend
dependency. Inline styles were kept because that is the convention in every
sibling view and Tailwind v4 here has no config file, so class scanning over these
templates is not something I could confirm.

Client-side validation is a courtesy only. The server re-validates both type and
size.

---

## 6. Blade linting

The doc notes the views are never exercised by any test and that this is where
every bug has been found. Since the suite could not be run, the Blade layer was
checked instead by emulating the compiler's regex passes in the correct order
(raw PHP extracted before comments are stripped, non-greedy echo matching so the
`}}}` early-termination trap reproduces rather than hides) and running `php -l`
over the output.

**All 162 templates in `resources/views` parse cleanly**, including the three new
and six modified ones. Two apparent failures during the sweep turned out to be
gaps in the emulation (`@forelse`/`@empty`/`@endforelse` nesting, and
`@auth`/`@guest` with `@else`), not defects in the templates.

This is worth turning into a real smoke test — a feature test that GETs each
route and asserts 200 would have caught every Blade bug in this project's history.
It is the single highest-value test you do not yet have.

---

## 7. Audit finding, deliberately not acted on

23 raw balance mutations exist outside `CoinService`:

```
app/Services/Payment/PaymentService.php:58
app/Services/Payout/PayoutService.php:85
app/Http/Controllers/Api/WalletController.php:325,408,421
app/Http/Controllers/Api/WorkSubmissionController.php:117
app/Http/Controllers/Api/Auth/RegisterController.php:75
app/Http/Controllers/Api/WorkController.php:213,274
app/Http/Controllers/Admin/WorkSubmissionController.php:96,179
app/Http/Controllers/Admin/CashoutController.php:202
app/Http/Controllers/Admin/WorkController.php:313
app/Http/Controllers/Admin/CoinTopupController.php:70
app/Http/Controllers/Admin/UserController.php:203,209
app/Http/Controllers/User/WalletController.php:338
app/Http/Controllers/User/WorkSubmissionController.php:145,245
app/Http/Controllers/User/Auth/RegisterController.php:88
app/Http/Controllers/User/WorkController.php:100,170
```

Triage:

- `Api/*` — unreachable, `EnsureApiEnabled` 404s the whole group.
- `User/WorkSubmissionController.php:145,245` and `User/WorkController.php` —
  behind `feature:enable_user_gigs`, so unreachable while gigs are off. Worth
  noting that 145/245 credit `coins_per_worker` with **no commission deduction**,
  so they must not be re-enabled as-is.
- `Admin/WorkSubmissionController.php:96,179` — the known legacy path. Reachable.
  Resolution depends on Q2.
- The rest (topup, cashout, transfers, admin adjustments) are pre-existing script
  code that writes its own ledger rows.

None were changed. That is money code, and the project's own rules say not to
touch it without the suite green, which could not be done here.

---

## 8. Still outstanding

- **6.2** — needs Q4 answered. The filter UI already exists in both browse views,
  so the only thing left to build is a discovery affordance (category tiles or a
  landing page), which is precisely what Q4 asks about. Building the wrong one is
  wasted work.
- **6.7** — `php artisan db:seed --class=MarketplaceSeeder`. A command to run, not
  code to write.
- **6.9** — blocked on Q1. The doc is explicit that the balance model must be
  agreed before coding, and this moves money.
- **Q2, Q3, Q5** — unanswered.
- Nothing was committed. Review the working tree, then commit in reviewable
  chunks.
