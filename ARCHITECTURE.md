# Architecture & Repo Guide

This document exists so a new contributor, agent, or LLM can get productive in this
codebase fast, without re-deriving everything from scratch. It covers what the
software is, why it's structured the way it is, where the seams are, and what's
half-built.

> For "how do I run GitNexus / trace a symbol / check blast radius" tooling
> instructions, see `CLAUDE.md`/`AGENTS.md` (auto-generated, machine-maintained —
> don't hand-edit). This file is the human-authored domain/architecture doc.

## 1. What this actually is

The base software is **Unifiedtransform** — an open-source Laravel school
management + accounting system (GPLv3, see `README.md`). **This fork is not the
upstream project** — it's being actively modified by two developers for their own
use, with the direction of turning it into a **white-label, client-configurable
ERP** (seed data already refers to a "DoorKnob" brand — see
`database/seeders/SuperAdminSeeder.php` — that's the working rebrand name, not
necessarily final).

Practical implication: don't assume README.md describes current behavior 1:1 —
several features it lists (Stripe, messaging, library, income/expense tracking)
are marked "yet to be migrated" from a v1.x codebase and may or may not land here.
Trust the code over the README for anything you're about to build on.

Two eras of code coexist:
- **Original Unifiedtransform core**: academic structure, attendance, exams/marks,
  notices/events. Fat-controller Eloquent, little abstraction.
- **New custom modules** (finance, settings, super-admin, report engine — added
  recently, see `git log`): Repository + Service pattern, cleaner separation,
  Spatie-permission-gated, but not yet fully integrated with the old core (two
  parallel settings systems, no seeders/factories yet, etc. — see §7).

## 2. Stack

- **Backend**: Laravel 8, PHP 7.4+, MySQL 5.7
- **Auth/permissions**: Laravel's default `web` guard + `spatie/laravel-permission`
- **PDF/reports**: Spatie Browsershot (headless Chrome) via `ReportEngineService`
- **Frontend**: server-rendered Blade, Bootstrap 5 (loaded statically, not via
  npm — the Mix/npm toolchain still pulls Bootstrap 4, that's dead weight, see
  `webpack.mix.js`/`package.json`), jQuery, no SPA framework
- **Notable JS libs (CDN, not bundled)**: CKEditor5 (notices), FullCalendar 3
  (routines/events)
- **Infra**: Docker Compose (nginx + php-fpm + mysql), see `docker-compose.yml`

## 3. Domain model

Root of (almost) everything is the **academic session**:

```
SchoolSession (academic year)
 ├─ Semester
 └─ SchoolClass
     └─ Section
Course  (belongs to SchoolClass + Semester)
```

Design choice: almost every downstream table denormalizes `session_id` /
`class_id` / `section_id` / `course_id` directly rather than relying purely on
joins through the hierarchy above. This is deliberate (query simplicity) but
means these FKs must be kept consistent by application code — there's no DB
constraint enforcing e.g. that a `Section.class_id` matches the `class_id` stored
on a `Promotion` row for the same student. Keep this in mind before "just add a
join" — check whether the existing code already denormalizes the field you need.

**People**: single `users` table for admin/teacher/student/guardian, discriminated
by a `role` string column *and* Spatie roles (`hasRole()`). See `App\Models\User`.
Students get two 1:1 extension tables: `student_parent_infos` (guardian contact),
`student_academic_infos` (board registration no).

**Enrollment**: `Promotion` — links a student to a class/section for a given
session (also the record of "promoting" a student to the next class each year).
This is the row `PaymentRepository::getStudentFeeSummary()` depends on to resolve
which class a student is currently in for fee lookups.

**Assessment chain**: `Exam` → `ExamRule` (rules per exam) → `Mark` (raw score per
student/exam/course) → `FinalMark` (computed/overridable final grade per
student/course/semester). Separately, `GradingSystem` → `GradeRule` maps a score
range to a letter grade/GPA point, joined in at *display* time, not stored on
`FinalMark`.

**Attendance**: single `Attendance` table, mode (`section` vs `course`) toggled
globally via `academic_settings.attendance_type`.

**Finance** (newest subsystem — `database/migrations/2026_08_03_100000_create_finance_tables.php`):

```
FeeStructure (per session/class) ─┬─ FeeInstallment (named amount rows)
                                   └─ StudentFee (per-student override — UNUSED, see §7)
FeePayment (actual payment, receipt_number unique)
Expense (school spend, independent of fee flow)
Transaction (unifying ledger row — references EITHER fee_payment_id OR expense_id)
FeeReminder ─ FeeReminderRecipient (SMS/WhatsApp campaign, per-student send log)
```

`Transaction` is the reporting source of truth (`TransactionRepository::getAnalyticsSummary`)
— every `FeePayment` and `Expense` gets mirrored into it at creation time via
`createIncomeTransaction()` / `createExpenseTransaction()`. If you ever insert a
`FeePayment` or `Expense` row directly (e.g. in a seeder or migration script)
without going through `PaymentService`/`ExpenseService`, the ledger and reports
will silently miss it.

## 4. Request flow / architectural pattern

**Routing**: single flat `routes/web.php`, one big `Route::middleware(['auth'])`
group with `prefix()` groups per feature area (`school.*`, `finance.*`,
`reports.*`, `settings.*`, `super-admin.*`). **There is no route-level or
middleware-level role gating** — no `->middleware('role:admin')` anywhere.

**Real authorization happens in two places**:
1. Controller constructors: `$this->middleware(['can:view payments'])` etc.
   (Spatie permission check)
2. Form Requests: `authorize()` methods call `auth()->user()->can('...')`

If you add a new route, the permission check needs to be added explicitly in one
of these two places — it will not inherit protection from the route group.

**Two coexisting patterns**:
- **Old core** (academics, exams, notices, etc.): controllers talk to Eloquent
  models directly. Fat controllers.
- **New finance/settings modules**: `Interface` (contract) → `Repository`
  (Eloquent implementation) → optional `Service` (transactions, business rules,
  events) → `Controller` (thin, injects interface + service via DI). Bound in
  small per-domain `Provider` classes (`FinanceServiceProvider`,
  `CourseServiceProvider`, etc. — not one central provider).

  Concrete example worth reading end-to-end if you want to copy the pattern for a
  new feature: `PaymentInterface` → `PaymentRepository` → `PaymentService::collectFee()`
  → `FeeCollectionController`. `PaymentService` wraps the whole operation in
  `DB::transaction`, validates business rules (no overpayment), writes the
  payment, mirrors it into `Transaction`, then fires `PaymentCollected`.

- Not every new-module controller uses a Service — controllers that only need
  reads/writes without cross-cutting business logic (e.g. `ExamController`)
  inject repositories directly, skipping the Service layer.

**Events**: only one custom event exists — `PaymentCollected` →
`SendPaymentNotificationListener` (queued, calls `MessageService::sendPaymentConfirmation`).
**It is currently dead** — not registered in `App\Providers\EventServiceProvider`,
so it never fires. If you're relying on payment notifications, register it there
first.

**Traits worth knowing**:
- `App\Traits\SchoolSession` — `getSchoolCurrentSession()`, used everywhere to
  resolve "what session is currently being browsed" (reads `session('browse_session_id')`,
  falls back to latest). Use this instead of hardcoding session lookups.
- `App\Traits\AssignedTeacherCheck` — aborts 404 if the logged-in teacher isn't
  assigned to the class/section/course being accessed.

## 5. Roles, permissions, super admin

- Roles: `admin`, `teacher`, `student`, `guardian`(ish — check `role` column
  values in migrations/seeders for the authoritative list), plus a newer
  `super_admin` layered on top.
- Real permission checks are Spatie permission strings (`'view payments'`,
  `'create users'`, etc.) — see `database/seeders/PermissionSeeder.php` for the
  full list, and add new permission strings there when gating new features.
- **Super Admin** (`User::isSuperAdmin()`) sits above the normal role system:
  - `AuthServiceProvider::boot()` uses `Gate::before()` to grant super admins
    every ability unconditionally — a super admin bypasses all permission
    checks, not just the ones you'd expect.
  - Super admin can **impersonate** admin/teacher/student
    (`SuperAdminController::switchRole`/`exitImpersonation`, stored in
    `session('impersonated_role')`), read via `User::getEffectiveRoleAttribute()`.
    Views/logic that need to know "what role is this user acting as right now"
    should use the effective role, not the raw `role` column, or impersonation
    will silently be ignored.

**Security note (flagged, not yet fixed)**: `database/seeders/SuperAdminSeeder.php`
seeds a hardcoded super admin account — `DOORKNOB@SU` / `SU@ADMINDOORKNOB` — with
every permission plus the `Gate::before()` bypass. This is now in the repo's
history. Before any real deployment: rotate/remove this seeded account, or at
minimum force a password reset on first login.

## 6. Configuration system — current state (relevant to "make everything client-configurable")

There are **two parallel, non-unified settings mechanisms**:

1. **`academic_settings`** — a narrow, fixed-column table (`attendance_type`,
   `marks_submission_status`). Adding a new toggle here means a migration +
   model change. Not self-service.

2. **`settings` (generic key/value/group/type store)** — `App\Models\Setting`,
   read/written through `SettingService`, exposed globally via the `setting($key, $default)`
   and `setting_asset($key)` helpers (`app/Helpers/settings.php`). Cached forever
   (`Cache::rememberForever`, invalidated on write). This is the **actual
   extension point** for client self-configuration — it already covers a lot:
   branding (logo/colors/fonts), theme, contact info, social links, SEO, login
   page copy, email branding, report styling, system prefs (currency, timezone,
   date format, pagination, feature toggles like `sms_enabled`), and raw
   custom CSS/JS injection. See `database/seeders/SuperAdminSeeder.php` for the
   full current key list (~100 keys) and `resources/views/settings/index.blade.php`
   for the UI (super-admin-only, `SettingController`).

   To add a new client-configurable option anywhere in the app: add a key to the
   `$defaultSettings` array in `SuperAdminSeeder`, add a form field in
   `settings/index.blade.php`, read it via `setting('your_key', $default)`
   wherever it's needed. No migration required — this is the fast path.

**Gap towards "everything configurable"**: the generic `settings` store currently
only covers *presentation/branding/system* config. Business-logic configuration
is still hardcoded or schema-rigid:
- Fee components (tuition/tax/transport breakdown) — schema only supports named
  "installments" (amount + due date), not composable fee *types*. No tax/GST
  field anywhere.
- Permission-to-role mapping is seeder-defined, not editable through the
  settings UI — a client can't self-serve "let teachers also collect fees"
  without a code change to `PermissionSeeder` + role assignment.
- Attendance type / marks submission window live in the separate rigid
  `academic_settings` table, not the flexible one.
- No per-client feature flags to turn whole modules on/off (e.g. disable finance
  module entirely for a client that doesn't want it).

If the goal is "clients configure everything themselves," the natural direction is
migrating `academic_settings` into the generic `settings` table (same
group/key/value/type shape, just a `group = 'academic'`), and extending the fee
structure schema to support named, typed components (see §7 below — the
`FeeInstallment` table is close to what's needed already).

## 7. Known incomplete/broken areas (verified against code, not assumptions)

These were investigated directly — each has a concrete root cause, not just a
symptom:

- **Fee structure is "flat amount" instead of composable (tuition/GST/etc)**:
  `FeeInstallment` already supports multiple named rows summing to a total, but
  it's framed as a *payment schedule* (name + amount + **due_date**), not fee
  *composition*. No `type`/`category` column, no tax field, no drag-and-drop
  (zero sortable-library usage anywhere in the repo). Reusable skeleton exists;
  needs a `type` column + tax calc + reordering UI.

- **Per-student fee assignment is dead code**: `FeeStructureRepository::assignToStudent()`
  exists and would write to `student_fees`, but nothing calls it — no route, no
  controller action, no view. The `student_fees` table is permanently empty in
  practice. The system runs entirely on the fallback chain in
  `PaymentRepository::getStudentFeeSummary()`: `StudentFee` (always empty) →
  class-level `FeeStructure` for the session → session-wide default
  (`class_id IS NULL`). "Fee not assigned" for a real student traces to one of:
  missing `Promotion` row, `FeeStructure` created for wrong session, no
  `FeeStructure` for that class + no null-class-id fallback, or class changed
  after the fee structure was set up.

- **No due-students report**: `ReportController::prepareReportData` (finance
  reports) only aggregates class-level totals (expected/collected/pending per
  class). No per-student due list, no student selection, no section-level
  breakdown, no print action for it. This is a missing feature, not a bug —
  the per-student data (`getStudentFeeSummary`) already exists, it's just never
  surfaced as a report.

- **Receipts only exist for fee collection**: `FeeCollectionController` has
  `receipt`/`receiptPdf` routes using shared `report-header`/`report-footer`
  Blade components + `ReportEngineService`. `ExpenseController` and
  `TransactionController` have zero receipt/PDF routes. The components are
  proven reusable (fee-collection already uses them) — wiring them to
  expenses/transactions is additive work, not a redesign.

- **`PaymentCollected` event listener never fires** (see §4) — registration
  missing from `EventServiceProvider`.

- **Exam results "not visible" is likely a config/data issue, not a missing
  feature**: the full submit → store → view chain exists end-to-end for both
  teacher/admin (`marks/results.blade.php`) and student (`marks/student.blade.php`)
  views, no orphaned routes. Two things that would look exactly like "results not
  visible" to a user: (a) `academic_settings.marks_submission_status` is off,
  blocking submission with a flash message; (b) no `GradingSystem`/`GradeRule`
  configured for that class+semester causes a silent `abort(404)` with no
  user-facing empty state, even though `Mark`/`FinalMark` data exists. Check
  actual data/settings on the affected class before assuming code is broken.

- **Receipt number generation race**: `PaymentRepository::generateReceiptNumber()`
  computes the next sequence by reading the latest matching row — two concurrent
  payments on the same day can read the same "latest" and collide. Only the DB
  unique constraint on `receipt_number` catches it (as a hard failure, not a
  retry).

## 8. Where to look for things (cheat sheet)

| Need to... | Look at |
|---|---|
| Add a new client-configurable setting | `database/seeders/SuperAdminSeeder.php` (`$defaultSettings`), `resources/views/settings/index.blade.php`, read via `setting('key')` |
| Add a new permission-gated feature | `database/seeders/PermissionSeeder.php`, `$this->middleware(['can:...'])` in controller ctor, `authorize()` in the FormRequest |
| Understand/extend the finance ledger | `app/Services/PaymentService.php`, `app/Repositories/TransactionRepository.php`, `app/Models/Transaction.php` |
| Add a new PDF report | `app/Services/ReportEngineService.php` (Browsershot wrapper), `resources/views/reports/layouts/base.blade.php`, follow `ReportController::exportPdf` as a template |
| Trace a role's permissions in practice | `app/Models/User.php` (`isSuperAdmin`, `isAdminOrSuperAdmin`, `getEffectiveRoleAttribute`), `app/Providers/AuthServiceProvider.php` (`Gate::before`) |
| Find what session/class a query should scope to | `App\Traits\SchoolSession::getSchoolCurrentSession()` |
| Understand the academic hierarchy | `app/Models/SchoolSession.php`, `SchoolClass.php`, `Section.php`, `Semester.php`, `Course.php`, `Promotion.php` |

## 9. Before you touch anything

This is a fork of a real, working open-source app that's mid-transformation into
a custom product. Two failure modes to avoid:
1. **Don't assume a gap is a bug** — several "missing" things (per-student fee
   override, due-student reports, expense receipts) are unbuilt features with a
   clear extension point already in place, not regressions. Check §7 before
   filing something as broken.
2. **Don't duplicate the settings mechanism** — if you need a new
   admin-configurable value, use the generic `settings` table/`SettingService`,
   not a new fixed-column table like `academic_settings`. The latter is legacy
   and actively working against the "everything configurable" goal.
