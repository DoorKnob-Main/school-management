# DoorKnob School Management — Architecture

> Fork of [Unifiedtransform](https://github.com/change-teams/unifiedtransform) (open-source Laravel 8 school ERP, GPLv3), extended with biometric attendance and leave management. This doc explains what exists, how it fits together, and why key decisions were made.

## 1. Stack

- **Framework:** Laravel 8, PHP 7.3–8.x
- **Frontend:** Blade views + Bootstrap 5, Laravel Mix (webpack) for JS/CSS
- **Auth/roles:** Single `web` session guard, one `users` table with a `role` column, `spatie/laravel-permission` for fine-grained permissions
- **PDF/reports:** `spatie/browsershot` (headless Chrome rendering)
- **DB:** MySQL (single engine across all environments — see [Deployment](#5-deployment-model))
- **Biometric hardware:** external Windows `.exe` bridge talking to fingerprint device SDK over LAN TCP

## 2. High-level module map

| Module | Controllers | Purpose |
|---|---|---|
| Auth | `Auth/*Controller` | Standard Laravel scaffolding |
| School setup | `SchoolSessionController`, `SemesterController`, `SchoolClassController`, `SectionController`, `CourseController` | Academic year → semester → class → section → course hierarchy |
| Staff/Students | `UserController`, `AssignedTeacherController`, `StudentAcademicInfoController`, `StudentParentInfoController` | CRUD + profile data, teacher-course assignment |
| Attendance (manual) | `AttendanceController` | Daily class attendance taken by a teacher — date-aware (any day, default today), 4 statuses (`on`/`off`/`late`/`on_leave` — same strings biometric writes), upsert per student/day (no duplicates). See §4.5 |
| Exams/Marks | `MarkController`, `ExamController`, `ExamRuleController`, `GradingSystemController`, `GradeRuleController` | Marks entry, grading rule config |
| Promotion | `PromotionController` | Move students between classes/sessions |
| Calendar/Notices/Routine | `EventController`, `NoticeController`, `RoutineController`, `SyllabusController`, `AssignmentController` | Scheduling and communication |
| Finance | `FeeStructureController`, `FeeCollectionController`, `TransactionController`, `ExpenseController`, `FeeReminderController`, `ReportController` | Fees, payments, expenses, unified ledger |
| Settings | `SettingController` | White-label / org-wide config, JSON export-import |
| Super Admin | `SuperAdminController` | Role impersonation (view app as admin/teacher/student) |
| **Biometric** | `BiometricDeviceController`, `BiometricAttendanceController`, `BiometricEnrollmentController`, `BiometricReportController` | Fingerprint device integration (see §4) |
| **Leave** | `StudentLeaveController` | Student leave apply/approve/reject (see §4.4) |

Routes: `routes/web.php`, all behind `auth` middleware. `routes/api.php` is barely used (one `/user` endpoint).

## 3. Data model

No multi-tenancy — this is a **single-school** application. "Super Admin" is a role on `User`, not a separate tenant/organization concept. Scoping across academic years is done via `session_id` (from `school_sessions`), not a `school_id`.

**Core:**
```
User (single table for admin/teacher/student, role column)
 ├─ StudentParentInfo (hasOne)
 ├─ StudentAcademicInfo (hasOne)
 └─ Mark (hasMany)

SchoolSession → Semester → SchoolClass → Section → Course
                                              └─ AssignedTeacher (teacher↔course↔class↔section)
```

**Finance** (`database/migrations/2026_08_03_100000_create_finance_tables.php`, extended by `2026_08_16_100000_create_fee_components_tables.php`):
```
FeeStructure → FeeInstallment (payment schedule: Term 1, Term 2...)
             → FeeStructureComponent → FeeComponentType (fee breakup: Tuition, GST, Transport...)
             → StudentFee (per student/session/structure — auto-assigned via PromotionObserver)
FeePayment (receipt_number unique) → Transaction (hasOne)
Expense                            → Transaction (hasOne)
Transaction  — unified ledger: transaction_type income|expense, links back to a receipt or voucher
FeeReminder → FeeReminderRecipient
```
`FeeComponentType` is admin-managed (fixed amount or percentage/tax-style, applied on top of the fixed subtotal) — same pattern as `LeaveType`. Installments (schedule) and components (breakup) are independent axes: a structure can have both, either, or neither.

**Biometric + Leave** (`database/migrations/2026_08_14_100000_create_biometric_and_leave_tables.php`):
```
BiometricDevice → BiometricDeviceUserMapping (student_id ↔ device_user_id, unique per device)
                → BiometricPunchLog (raw punches, unique on device+user+time+verify_type)
BiometricAuditLog (action log, JSON details)

LeaveType → StudentLeave (student_id, dates, status, approved_by)

Attendance table extended with: in_time, out_time, attendance_source,
  late_minutes, early_leave_minutes, is_corrected, corrected_by, correction_reason, remarks
```
**Status vocabulary (one shared set):** manual and biometric attendance write to the *same* `attendances.status` column and use the *same* strings:
- **Biometric** (`BiometricAttendanceProcessor`) writes `on` (present), `off` (absent), `late`, `on_leave`, `holiday`, `pending`. It maps present→`on` and absent→`off`; the rest are stored as-is. `attendance_source = 'biometric'`.
- **Manual** (`AttendanceRepository::saveAttendance`) writes the same core set — `on`/`off`/`late`/`on_leave` (`AttendanceRepository::MANUAL_STATUSES`). `attendance_source = 'manual'`. UI labels these Present/Absent/Late/Leave but the stored value is the biometric string.

Only difference is `attendance_source`. Readers still defensively accept `present`/`absent` too (`status === 'on' || 'present'`) for safety against any old data, but nothing in the app writes those now. "Days attended" counts present-like only (`on`/`present`/`late`).

**Repository pattern:** older modules (users, academic, finance) use `app/Repositories/*Repository.php` + `app/Interfaces/*Interface.php`, bound in per-domain service providers (`FinanceServiceProvider`, `UserServiceProvider`, etc). Biometric and Leave modules are newer and go straight through Eloquent — no repository layer for them yet. Worth knowing if extending the codebase: don't assume every domain follows the repository pattern.

## 4. Biometric attendance — end to end

This is the main custom subsystem added on top of Unifiedtransform.

### 4.1 The hardware bridge

`bin/m50_bridge/M50DeviceTester.exe` — a compiled Windows console app (C# source in `Program.cs`) that P/Invokes the vendor SDK (`SBXPCDLL.dll` / `SBPCCOMM.dll`, Suprema/ZKTeco-style protocol, TCP port 5005). Commands: `ping|connect|sysinfo|getlogs|getusers|adduser|deleteuser|unlockdoor|clearlogs|synctime`, each printing a single JSON object to stdout.

**Why an external exe and not a PHP library:** the device SDK is a native Windows DLL with no PHP bindings. Shelling out to a small dedicated bridge program is the pragmatic way to talk to it from a web app.

**Constraint this creates:** the bridge — and therefore the whole app, since it's invoked via `shell_exec` on the same machine — must run on Windows, on the same local network as the physical device. This is the deciding factor in the deployment model (§5).

### 4.2 PHP-side invocation

`app/Services/BiometricDeviceService.php`:
- `socketPing()` does a fast `fsockopen` preflight (2s timeout) before shelling out, so an offline device fails fast instead of hanging a PHP worker.
- `executeCommand()` builds `"<exe>" <command> <args> --json`, runs `shell_exec`, parses the first `{...}` JSON blob from stdout.
- Public methods map 1:1 to bridge commands: `testConnection`, `getSystemInfo`, `fetchAttendanceLogs`, `fetchEnrolledUsers`, `enrollUser`, `deleteUser`, `syncDeviceTime`, `unlockDoor`, `clearDeviceLogs`.

### 4.3 Sync pipeline

`app/Services/BiometricSyncService.php`:
1. `syncDevice()` → `fetchAttendanceLogs()` → raw punches
2. `storePunchLogs()` — dedups against `BiometricPunchLog` (unique key), resolves `device_user_id → student_id` via `BiometricDeviceUserMapping`, bulk-inserts in chunks of 100
3. `BiometricAttendanceProcessor::processDate()` — converts raw punches into daily `Attendance` rows, applying school timing settings from `SettingService` (late/early-leave minutes, etc)
4. Every action logged to `BiometricAuditLog`

**Sync is manual only** — triggered by a button in the UI (`biometric.devices.sync/sync-all`), no cron job exists. If automatic periodic sync is wanted later, that's a gap to fill (`app/Console/Kernel.php` has no scheduled command for this yet).

### 4.4 Leave ↔ Attendance integration

`app/Services/LeaveService.php::approveLeave()` doesn't just flip a status — it calls `BiometricAttendanceProcessor::processDate()` for every date in the approved leave range, so attendance is recalculated to reflect the leave instead of showing a false absence. This is the one place the two new features are directly coupled.

### 4.5 Manual attendance — how it works

Teacher/admin-driven daily attendance, independent of the biometric pipeline but writing to the same `attendances` table (§3).

- **Launcher** (`attendances/index`) — class cards, each showing its **own** sections (section mode) or courses (course mode) via the class's `sections()`/`courses()` relations. A global date picker rewrites the Take/View links. (Historic bug: the blade looped the flat all-sections list under every class, duplicating every section everywhere — fixed to use per-class relations.)
- **Take** (`attendances/take`) — roster with a 4-state radio group per student (Present/Absent/Late/Leave), live search, All-Present/All-Absent bulk actions, and live counters. Pre-loads any existing marks for the selected date and stays editable.
- **Save** (`AttendanceRepository::saveAttendance`) — **upserts** one row per (student, class, section/course, date): updates if a row exists for that date, else creates one stamped at the chosen date. This is what prevents duplicates and enables editing/back-dating. Tags `attendance_source = 'manual'`.
- **View** (`attendances/view`) — summary stat cards + per-student status badges, source, and session days-attended, for the selected date.
- **Date handling** — `AttendanceController::resolveDate()` defaults to today and clamps out future dates; the read methods (`getSectionAttendance`/`getCourseAttendance`) and `saveAttendance` all take an optional date.
- **Guards** — session read-only check (only the latest session is editable unless admin/super-admin) and `AssignedTeacherCheck` (a teacher may only touch their assigned class/section/course).

Course-mode roster still relies on `UserRepository::getAllStudents` which needs a section; course-mode attendance is the secondary path (the live setting is section mode) and was left as-is.

## 5. Deployment model

**Decision: fully on-premise, one Windows PC per school.** App + MySQL + biometric bridge all run together on one machine at the school, on the school's own LAN. Staff/admin devices reach it via the local network (e.g. `http://192.168.1.x:8000`).

**Why:** the biometric bridge is Windows-only and must be on the same LAN as the device (§4.1) — this rules out pure cloud hosting without first reworking the bridge to push data over the internet instead of being invoked synchronously. On-premise matches the code as already built, with zero rework needed to ship.

**Setup stack:** [XAMPP](https://www.apachefriends.org) (bundles PHP + MySQL + web server — one familiar installer, no custom portable-MySQL packaging needed). App is shipped **pre-built** (Composer `vendor/` and `npm run production` output already compiled before zipping) so the client machine never needs Composer or Node installed.

**One database engine everywhere** — MySQL in dev, test, and production. Deliberately rejected using SQLite for quick local runs: a migration in this codebase uses `->after()` for column positioning (`2026_08_14_100000_create_biometric_and_leave_tables.php`, extending the `attendances` table), which MySQL supports natively but SQLite's schema grammar does not — a real, previously-hit bug source. Divergent dev/prod DB engines hide exactly this kind of issue.

**Scripts** (repo root):
- `run_project.bat` — one-click start: fixes `.env` to MySQL/XAMPP defaults, creates the DB if missing, runs migrations (idempotent, safe to re-run), seeds default accounts **only on first run** (checks `users` table is empty first — never wipes existing client data), serves on `0.0.0.0:8000` so other computers on the school LAN can reach it.
- `install_service.bat` — registers the app as a persistent Windows service via [NSSM](https://nssm.cc/download), so it survives reboots without a terminal window staying open.
- `backup.bat` — nightly MySQL dump + `storage/app` zip, meant to be wired into Windows Task Scheduler. Keeps 14 days of local backups; cloud upload is a documented TODO, not yet implemented.

**Future (not yet started):** a separate, small VPS-hosted dashboard (a different app, not part of this repo) to track lightweight cross-client metrics (user/student counts, message counts) for subscription/billing visibility — explicitly without pulling full student data off any client's premises. No implementation yet; revisit when scoped in detail.

## 6. Known gaps / things to watch

- No role/permission middleware on routes — access control (e.g. "Admin Only" leave-type CRUD, settings) is checked ad hoc inside controllers (`isSuperAdmin()`, `isAdminOrSuperAdmin()`), not declaratively at the route layer. Fine for now, but easy to accidentally leave a new route unguarded.
- `database/seeders/SuperAdminSeeder.php` seeds a hardcoded super-admin account (`DOORKNOB@SU` / `SU@ADMINDOORKNOB`) — must be rotated/removed before any real client goes live on it.
- Biometric and Leave modules bypass the repository pattern used elsewhere — inconsistent, but not urgent to fix.
- No automated/scheduled biometric sync — currently manual button only.
