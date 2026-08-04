# Bug Report — Agnus Dei School ERP

Bug sweep of the Laravel application (`Actual_Website/ERP_Agnus_Dei_School_Systems_INC`).
Generated: 2026-08-04
Status: Initial findings were by code review only. **Updated 2026-08-04 with live smoke-test results** (XAMPP Apache + MySQL running, real DB `agnusdei_erp` used, all test data restored afterward). **Fixed 2026-08-04: all bugs 1–8 + first-login password enforcement implemented and verified** (see "Fix status" under each).

---

## Live Smoke Test Results (2026-08-04)

Verified working while connected to the live database:

- All **9 role accounts** log in and reach their dashboards (Admin, Registrar, Cashier, Librarian, Nurse, Directress, Principal, Teacher, Student).
- **~70 authenticated pages** across all roles render HTTP 200 (dashboards, CRUD lists/forms, detail views).
- All **13 public/promotional pages** render HTTP 200 (`/`, academics, mission, vision, identity, etc.).
- **Registrar**: admission list/detail/report-cards (view + print) all render 200.
- **Teacher**: class pages and assessment pages render 200; **grade save works** (updated `final_grade` to 85.5 and reset status to `Pending`); teacher ownership check correctly returns **403** for classes belonging to other teachers.
- **Cashier**: payment page renders for all students; **payment process works** (amount recorded, `total_paid`/`balance` correct).
- **Nurse**: clinic log creation works (record created correctly).
- **Librarian**: borrow → `available_quantity` 17→16; return → 16→17; transaction marked `Returned`.
- All **33 migrations** applied (Batch 1-3). Seed data present: 42 users, 14 students, 13 enrollments, 14 admissions, 13 ledgers, 18 payments, 6 clinic logs, 8 library transactions.
- `storage` symlink exists (`public/storage`) → uploaded requirement files serve correctly.
- Settings present: `active_school_year = 2026-2027`.

---

## Confirmed bugs (live-tested)

### 1. Cashier discount wiped on every payment — HIGH — CONFIRMED LIVE
- File: `app/Http/Controllers/Portal/CashierController.php:111-118`
- **Live proof:** set `discount_applied = 5000` on ledger 1, then processed a normal payment → `discount_applied` dropped back to `0.00`.
- Root cause: the payment form (`resources/views/portal/cashier/payment.blade.php:160-184`) exposes **only** `payment_plan` and `amount_paid` — no discount fields. `processPayment` defaults `discount_amount` to `0`, so every payment overwrites an existing ledger's `discount_applied` to `0`.
- Impact: any student who was granted a discount loses it on their next payment; balance recalculates upward (double-billing).
- Fix direction: persist `discount_applied` and only modify it deliberately (e.g. a dedicated discount UI or setting it once at ledger creation).
- **FIXED 2026-08-04:** discount fields (`discount_type` select + `discount_amount` with live Alpine balance calc) moved **inside** the Process Payment form (`payment.blade.php`); orphaned left-column "Discounts" card removed. `CashierController::processPayment` now clamps `$discountAmount = min($discountAmount, $totalAssessed)` so it can never exceed assessed fees.

### 2. Application timezone is UTC (should be Asia/Manila) — HIGH — CONFIRMED LIVE
- Files: `config/app.php:68` (`'timezone' => 'UTC'`), `php.ini` `date.timezone = UTC`; machine local time is UTC+8.
- **Live proof:** receipt numbers generated during testing were `RCP-20260803-0001/2` while local date was already 2026-08-04 (17:32 UTC = 01:32 +08 next day).
- Impact: `today()`/`now()`-based logic is shifted ~8h — receipt number date stamps, Cashier "today's collection"/receipt counts, Nurse "today's visits", librarian overdue checks, `first_login_at`/`last_login_at` stamps are all affected around midnight.
- Fix direction: set `config/app.php` `timezone` to `Asia/Manila` and `date.timezone=Asia/Manila` in php.ini.
- **FIXED 2026-08-04:** `config/app.php:68` → `'timezone' => 'Asia/Manila'`; `php artisan config:clear`; verified `config('app.timezone')` = `Asia/Manila`, `now()` = `2026-08-04 02:03:13`. (CLI `php.ini` had `date.timezone` commented out, so `config/app.php` is authoritative — no php.ini change needed.)

### 3. Admission-approval email sends a blank "Temporary Password" — MEDIUM — CONFIRMED (code)
- Files: `app/Http/Controllers/Portal/RegistrarAdmissionController.php:160` passes `new AdmissionCredentialsMail($student, '')`; `resources/views/emails/admission-credentials-mail.blade.php:7` renders `Temporary Password: {{ $password }}` → students receive "Temporary Password: " (empty).
- The student's real password was already sent via `InquiryCredentialsMail` at account creation; this email should either drop the password line or not imply a new password exists.
- Fix direction: remove the password line from the template, or generate/reset an actual password before approving.
- **FIXED 2026-08-04:** `AdmissionCredentialsMail` no longer accepts/renders a password (constructor is now `(Student $student)` only); `RegistrarAdmissionController:160` passes `new AdmissionCredentialsMail($student)`. Verified `$mail->render()` outputs correctly with no `{{ $password }}`.

### 4. Queued emails are never sent (no queue worker) — MEDIUM — CONFIRMED (live)
- `.env:38` `QUEUE_CONNECTION=database`; `AdmissionCredentialsMail` (registrar approve) and `GradesSubmittedMail` (teacher submit) use `Mail::to()->queue(...)`.
- **Live check:** no PHP/artisan process is running → no `php artisan queue:work`. Emails enqueue into the `jobs` table and sit there forever.
- `InquiryCredentialsMail` uses `Mail::to()->send(...)` (synchronous) so the inquiry flow does send.
- Fix direction: run `php artisan queue:work` (or use `Mail::to()->send(...)`), and/or add a `.bat`/service to keep the worker alive.
- **FIXED 2026-08-04:** both remaining `->queue(...)` calls switched to synchronous `->send(...)`: `RegistrarAdmissionController.php:160` and `TeacherController.php:124` (`GradesSubmittedMail`). Verified via `Mail::fake()` that `AdmissionCredentialsMail` sends. No queue worker needed for these flows.

### 5. Account deletion destroys the Student record — MEDIUM (FIX DECIDED: archive, do not delete)
- Files: `app/Http/Controllers/ProfileController.php:43-59`, migration `2026_03_29_142152_create_erp_core_tables.php:41` (`students.user_id ... onDelete('cascade')`)
- The Breeze "Delete Account" action deletes the `User`, cascading to `Student` and downstream enrollments/ledgers/payments/grades.
- **Decided fix: account deletion must archive/deactivate instead of delete.** Mechanism already exists: `User::getAuthPassword()` (`app/Models/User.php:46-49`) blocks login for `status = 'inactive'`.
- **FIXED 2026-08-04:** migration `2026_08_04_000000_add_archive_columns_to_students_table.php` adds `archive_action` (varchar), `archive_reason` (text), `archived_at` (timestamp) to `students`. `ProfileController::destroy` now **deactivates instead of deletes**: validates `password` + required `reason` (+ required `action` in `transfer|graduated` when the user is a student), sets student `status='archived'` with the reason/action/archived_at, sets user `status='inactive'`, logs `log_activity(..., 'Archived', ...)`, then logs out. `delete-user-form.blade.php` reworded to "Deactivate Account" with reason textarea + student-only Action dropdown. Verified end-to-end in tinker (archived status/action/reason/archived_at + inactive user, rolled back).

### 6. Library loans keyed by book title — MEDIUM — CONFIRMED (code; works only when titles are unique/unchanged)
- Files: `app/Models/Book.php:25`, `app/Models/LibraryTransaction.php:15`, `app/Http/Controllers/Portal/LibrarianController.php:164-189`
- `storeBorrow` saves `book_title` (string); `returnBook` looks up `Book::where('title', $transaction->book_title)`.
- Live borrow/return round-trip works (17→16→17) **when the title is unchanged**. Editing a book's title orphans history and prevents `available_quantity` from being restored; duplicate titles pick the wrong book.
- Fix direction: add `book_id` FK on `library_transactions` and match by id.
- **FIXED 2026-08-04:** migration `2026_08_04_000001` adds `book_id` FK (nullable→backfilled→non-nullable); `LibraryTransaction` model gets `book_id` in `$fillable` + `book()` belongsTo; `Book::borrowings()` updated to use FK; `storeBorrow` saves `book_id`; `returnBook` uses `$transaction->book->increment()` instead of title lookup; search uses `orWhereHas('book', ...)`. All 8 existing transactions backfilled successfully.

### 7. Receipt number race — LOW — CONFIRMED (code)
- File: `app/Http/Controllers/Portal/CashierController.php:122` — `RCP-YYYYMMDD-####` via `count()+1`, no unique constraint on `receipt_number` → duplicates under concurrency.
- **FIXED 2026-08-04:** unique constraint already existed in migration; `processPayment` now retries up to 5 times with `exists()` check before inserting, preventing duplicates under concurrent access.

### 8. Count-based number generation races — LOW — CONFIRMED (code)
- Files: `app/Models/Student.php:47`, `app/Models/Admission.php:35-43` — student/application numbers via `COUNT()+1`, no locking/unique guard; `student_number` groups by `created_at` year (pre-admission year), not approval year.
- **FIXED 2026-08-04:** `Student::generateStudentNumber` and `Admission::boot` now use `DB::transaction` + `lockForUpdate()` for atomic count; unique constraints already existed on both columns.

---

## Style / logic nits — INFO

- `app/Mail/AdmissionCredentialsMail.php` / `app/Mail/GradesSubmittedMail.php` use legacy `build()` + `$this->text()`; `InquiryCredentialsMail` uses modern `content()`. Works in Laravel 12; inconsistent style.
- `app/Models/User.php:46-49` — `getAuthPassword()` returns `null` for inactive users, which also blocks password reset/confirm for those accounts (behavioral note; likely intended).
- `resources/views/portal/student/dashboard.blade.php:240` — `date('Y') + 1 - 1` renders correctly but confusing; simplify to `date('Y')`.
- `.env:1` `APP_NAME=Laravel` (cosmetic; not set to "Agnus Dei").

---

## Ruled out (NOT bugs)

- **Public registration crash** — `/register` routes commented out (`routes/auth.php:15-18`); `InquiryController` always creates the linked `Student` row.
- **`payment_plan` enum mismatch** — converted to VARCHAR `installment|full`, used consistently; capstone "Plan A/B/C" is stale docs.
- **Clinic log duplicate fields** — both `symptoms`/`complaint` and `visit_date`/`incident_date` columns exist via migrations.
- **Principal grades view** — subject/class matching consistent with `TeacherController::storeGrades`.
- **Teacher page 403s** — those are the intended ownership guard, not a bug.
