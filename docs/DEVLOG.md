# Development Log

Chronological record of significant decisions and changes. Newer entries on top. See `ARCHITECTURE.md` for the current-state explanation — this file is the *why/when*, not a duplicate of it.

---

## 2026-08-20 — Manual attendance revamp (data bug + UX + date-aware upsert)

Reworked the manual attendance module end to end. Biometric attendance code and views were explicitly left untouched — the two subsystems share the `attendances` table, so the manual side was aligned *to* biometric's conventions rather than the reverse.

**1. Fixed the class/section duplication on the launcher.** `attendances/index` (section mode) looped `classes_and_sections['school_sections']` — the *flat* list of every section in the session — inside every class card, so all 18 sections rendered under each of the 9 classes (162 items, every section duplicated everywhere). Now the controller loads each class's own `sections()` relation (`$classes->load('sections')`) and the blade iterates `$school_class->sections`, so a class shows only its real sections. Teacher-role filtering also narrowed from class-only to assigned class **and** section **and** course.

**2. Aligned manual status to the biometric vocabulary.** Manual attendance and biometric write to the *same* `attendances.status` column. Biometric uses `on` (present), `off` (absent), `late`, `on_leave`, `holiday`, `pending` (it maps present→`on`/absent→`off` in `BiometricAttendanceProcessor`, rest as-is). Manual historically also used `on`/`off`, but the old `view.blade` only recognized `'on'` as present and "Total Attended" counted *every* row regardless of status. Rather than invent a second vocabulary, manual now writes exactly what biometric writes — `on`/`off`/`late`/`on_leave` (`AttendanceRepository::MANUAL_STATUSES`); the UI shows Present/Absent/Late/Leave but stores the biometric string. Views count only present-like (`on`/`present`/`late`) as attended and still accept `present`/`absent` defensively. **One shared vocabulary, so no data migration and no normalization is needed** — biometric code and data are untouched, and there is no read-time translation between the two writers.

**3. Date-aware taking (back-dating + history).** Was hardcoded to today (`whereDate created_at = today`). Repository read methods (`getSectionAttendance`/`getCourseAttendance`) and `saveAttendance` now take an optional date (default today, never the future — clamped by `AttendanceController::resolveDate()`). Each Take/View page has a date picker; back-dated rows are stamped at the chosen date. Interface signatures updated with `$date = null` defaults.

**4. Upsert instead of insert.** `saveAttendance` was `Attendance::insert()` with no unique key — a double submit or a manual take after biometric created duplicate rows, and the once-per-day guard was UI-only (`attendance_count < 1` hid the button, but `store()` never re-checked). Now it upserts one row per (student, scope, date): update if present, else create. Manual rows are tagged `attendance_source = 'manual'`. The one-shot lock is gone — teachers can re-take/edit; an info banner shows when editing an existing day.

**5. Professional roster UI.** Rebuilt all three blades (`index`/`take`/`view`) on Bootstrap 5 cards: launcher with scoped class cards + global date picker; Take page with a 4-state radio button-group per student (Present/Absent/Late/Leave), live search, All-Present/All-Absent bulk actions, and live status counters; View page with summary stat cards (Total/Present/Late/On-Leave/Absent) + a clean table showing status badge, source, and session days-attended. Also allowed all-absent saves (`status` validation relaxed from `required` to `nullable|array` with a per-value `in:` whitelist).

**Verified in-browser** (Super Admin, local Mac dev): launcher shows 9 classes × their own 2 sections with zero duplicates; edited a section's roster (one Present→stayed, one set to Late), saved → "save successful", counters correct, row count unchanged (no duplicate); View page summary + badges + days-attended all correct.

**Files:** `AttendanceController`, `AttendanceRepository`, `AttendanceInterface`, `AttendanceStoreRequest`, `resources/views/attendances/{index,take,view}.blade.php`. No migration (manual reuses the existing biometric vocabulary). Biometric processor/services/views not touched.

---

## 2026-08-16 (end of day) — End-to-end verification pass before push

Ran a full browser-driven pass through the payment flow and a sample of unrelated pages before pushing `payment-flow`, using a local Mac dev setup (Homebrew PHP 8.2 + Composer + SQLite — see "Local Mac test setup" below). Found and fixed three real bugs uncovered by testing, none of them UI issues:

**1. `CollectFeeRequest` silently dropped `fee_structure_id`.** The form correctly sent it, but the request's validation rules never declared it, so Laravel's `$request->validated()` stripped it before it reached the payment service. Every recorded payment ended up with `fee_structure_id = NULL`, and every receipt fell back to showing "Standard Fee" instead of the real structure name. Added `'fee_structure_id' => 'nullable|exists:fee_structures,id'` to the rules — one-line fix, but would have shipped silently broken.

**2. App-wide "current session" resolution was inconsistent.** `getSchoolCurrentSession()` (used by all real business logic — fee collection, promotions, biometric device controllers, etc.) resolves the latest session via `orderBy('id', 'desc')`. Six other spots (top-nav badge, left-menu class count, `BiometricReportController`, `BiometricAttendanceProcessor`) instead used `SchoolSession::latest()`, which orders by `created_at`. With the demo seeder creating both academic sessions in the same second, these two methods disagreed on which session was "current" — the nav showed 2025-2026 while every real query operated against 2026-2027. Standardized all six call sites on `orderBy('id', 'desc')` to match the trait already used everywhere else. This wasn't just cosmetic: `BiometricAttendanceProcessor` resolving the wrong "current session" could have misattributed attendance processing.

**3. Confirmed the [[fee assignment]] fix from earlier today holds up under real data** — a Class 6 student who previously showed a stale "Class 4 Fee structure 2026" (₹1,300) now correctly shows "Class 6" (₹1,000); creating a new fee structure for a class instantly backfilled 12 already-enrolled students; the duplicate-structure guard correctly blocks a second default/class structure.

**Also verified working end-to-end:** partial + full fee collection (status transitions Overdue → Partial → Paid correctly), fee receipt rendering and PDF, due-students report (list + print preview, including fee breakup), fee component types CRUD, expense creation → voucher rendering, transaction ledger → receipt/voucher deep links, and the `container-fluid` layout fix across a sampled non-finance page (student list) — full width, no regression.

**Local Mac test setup used for this pass** (distinct from the [[project_deployment_model_a]] client setup, which stays MySQL/XAMPP): `brew install php@8.2 composer` (PHP 8.5 was installed by default via plain `brew install php` but is too new for this Laravel 8 app's locked dependencies — had to switch to 8.2 explicitly), `composer install` with dev deps included (`laravel/ui` is required at runtime for `Auth::routes()` despite living in `require-dev` — a separate pre-existing packaging bug, not fixed here), bumped `phpunit`/`prophecy` via `composer update --with-all-dependencies` since the locked versions don't support PHP 8.2, then SQLite (`DB_CONNECTION=sqlite`) for zero-dependency local iteration.

**Added `run_project.sh`** — Mac/Linux equivalent of `run_project.bat`, OS-detecting (installs PHP 8.2 + Composer + MySQL via `brew`/`apt`/`dnf` if missing, otherwise reuses what's there). Deliberately still MySQL-only, not SQLite — SQLite was fine for this session's ad hoc testing but the reusable script should stay consistent with the single-DB-engine decision. On Mac, uses `brew install mysql` rather than XAMPP — sidesteps the Gatekeeper block entirely since it's not a signed installer bundle.

---

## 2026-08-16 (later) — Payment flow work: fee assignment bug, configurable fee breakup, due-students report, universal receipts

Worked through the notes backlog for the payment/finance module, in priority order agreed with the user (bug fix first, then schema work, then additive features).

**1. Fixed: student fee not being assigned.** `FeeStructureRepository::assignToStudent()` existed but was never called anywhere in the codebase — dead code. Also its `updateOrCreate` key `(student_id, session_id)` didn't match the actual DB unique constraint `(student_id, session_id, fee_structure_id)`, which would have caused a second fee structure for the same student/session to silently overwrite the first instead of coexisting. Fixed the key, and wired two real trigger points: a new `PromotionObserver` (first model observer in this codebase — registered in `AppServiceProvider::boot()`) assigns applicable fee structures whenever a student is added or promoted into a class; `FeeStructureRepository::store()` now backfills students already enrolled in a class when a new fee structure is created for it.

**2. Added configurable fee component breakup.** User's requirement: fee structures shouldn't just take one lump amount — they should be destructured into parts (tuition, GST, transport, etc.), and those parts must be admin-configurable per school without the UI getting complex for non-technical users. Added `fee_component_types` (admin-managed master list — fixed amount or percentage/tax-style, same CRUD pattern as the existing `LeaveType`) and `fee_structure_components` (per-structure breakup, linked to a type). The fee structure create form gained an *optional* "Fee Breakup" section, mirroring the existing installments UI exactly so it feels familiar — if left empty, the old lump-sum/installment behavior is unchanged. Percentage components (tax) compute on top of the fixed subtotal.

**3. Built the due-students report.** Previously `ReportController` computed per-student fee data only to aggregate it into class-wise totals and discard the individual rows — there was no way to see or print which specific students owed what. New `finance/reports/due-students` page: filter by class/section, checkbox-select individual or multiple students, "only show students with dues" toggle, shows each student's fee breakup, preview/PDF via the same BrowserShot pipeline as the existing financial report.

**4. Closed the receipt gap.** Fee payments already had a proper receipt (reusing `report-header`/`report-footer` components). Expenses had no printable document at all — added an expense voucher using the same components/pattern. The unified transaction ledger now links each row to its actual source document (Receipt for income, Voucher for expense) instead of being a dead-end list. Also added the missing date-range filter on the expenses page (existed everywhere else already).

**Not done, explicitly deferred:** rotating the hardcoded `SuperAdminSeeder` credentials — user asked to leave this for now.

---

## 2026-08-16 — On-premise deployment model chosen; run scripts fixed

**Problem found:** `run_project.bat` (added with the biometric/leave feature) assumed a SQLite database (`database/database.sqlite`), but `.env.example` and the Docker setup both default to MySQL. Copying `.env.example` fresh and running the script left the app configured for MySQL while the script prepared a SQLite file — a real mismatch, not just a config nit.

**Decision:** standardize on **MySQL everywhere** (dev = prod = the on-premise client install), rejecting SQLite even for quick local runs. Reason: the biometric-and-leave migration extends the `attendances` table using `->after()` for column positioning, which SQLite's schema grammar doesn't support the same way MySQL does — exactly the kind of bug that a dev/prod DB divergence hides until it's too late.

**Deployment decision:** going with fully on-premise per school (one Windows PC runs app + MySQL + the biometric bridge together), because the biometric bridge (`bin/m50_bridge/M50DeviceTester.exe`) is Windows-only and must be on the same LAN as the physical fingerprint device. A cloud-hosted model would need the bridge reworked to push data over the internet instead of being invoked synchronously — deferred until there are multiple clients and centralizing is worth the rework.

**Changes made:**
- Rewrote `run_project.bat` — pins `.env` to MySQL/XAMPP defaults instead of SQLite, creates the DB if missing, runs migrations idempotently, seeds default accounts only on a genuinely first run (checks `users` table is empty — won't wipe a live client's data on a later run), serves on `0.0.0.0:8000` so other computers on the school's LAN can reach it (was `127.0.0.1`, local-machine-only).
- Added `install_service.bat` — registers the app as a Windows service via NSSM so it survives reboots without someone leaving a terminal window open.
- Added `backup.bat` — nightly MySQL dump + `storage/app` zip, meant to be scheduled via Windows Task Scheduler. Cloud upload of the backup is a documented TODO, not implemented yet.
- Updated `README.md` with an "On-Premise (Client/Production) Setup" section alongside the existing Docker instructions (Docker stays as the dev-oriented path).
- Added `docs/ARCHITECTURE.md` (this doc's sibling) as the up-to-date architecture reference.

**Deferred, explicitly not started:** a separate VPS-hosted dashboard for cross-client subscription/usage visibility (user/student/message counts only, no full data leaving client premises). No requirements gathered yet.

**Flagged, not yet fixed:** `SuperAdminSeeder.php` seeds a hardcoded super-admin account — needs rotating/removing before a real client goes live.

---

## Earlier history (from git log, not independently re-verified against code — see actual commits for ground truth)

- `baa70cf` — Merge: biometric attendance + leave management
- `198b24d` — Biometric device integration (bridge, sync, enrollment, reports) and leave management (types, apply/approve/reject, tied into attendance recalculation) added
- `82267b9` — Report base created and tested (generic `ReportEngineController`)
- `590e78c` — Student edit bug fixed
- `d2d1552` — Super admin role + white-label settings page added
