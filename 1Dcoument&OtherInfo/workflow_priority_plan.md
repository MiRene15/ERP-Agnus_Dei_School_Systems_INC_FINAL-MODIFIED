# Workflow Priority Plan — Agnus Dei School ERP

Bugs to fix and features to add, ordered by the system workflow (inquiry → admission → payment → operations → cross-cutting).
Generated: 2026-08-04 · **Last updated: 2026-08-06**
Bugs are from the verified list in `bug_report.md`; items marked **NEW** are feature additions.

> **2026-08-06:** The 12 feature enhancements in `feature_enhancements.md` (library booking logs + overdue/damage/lost fees, book inactive logs + serial numbers, AR numbers, attached receipts, privacy-first cashier search, misc fees, student financial view, payment reminders, collections by date, school-year filters) were all implemented. This supersedes **L5 (Overdue fines)** below and partially covers **M7 (overdue email reminders — payment reminders only, not overdue-book emails).**

Priority legend: HIGH = blocks/corrupts core operations · MEDIUM = significant user-facing gap · LOW = polish/robustness

---

## 1. Pre-Admission & Inquiry (public)

- **Fix:** none critical found.
- **Add (HIGH):** Rate-limit the `/inquiry` form. `PromotionalWebsite\InquiryController@store` creates a real `User` + `Student` with no throttle/anti-spam protection.

> **Rate-limit inquiry: FIXED 2026-08-04** — 5 req/min/IP via `throttle:inquiry` middleware.

- **Add (LOW):** Set `email_verified_at` or dispatch verification when the inquiry account is created (verification routes exist but are never triggered).

---

## 2. Admission Application (student)

- **Fix (LOW):** `application_number` generation race — `app/Models/Admission.php:35-43` uses `COUNT()+1` with no locking and no unique index. Add unique constraint + locked per-year sequence.
- **Add (MEDIUM):** Allow the student to edit a submitted application while it is still `Pending` (currently only `Draft` is editable).

---

## 3. Requirement Upload & Verification (student + registrar)

- **Fix:** none found. Upload (`StudentAdmissionController@uploadRequirements`) and verification (`RegistrarAdmissionController@verifyRequirement` / `verifyAll`) work; storage link present.
- **Add (MEDIUM):** Email the student when all requirements have been verified (currently silent).

---

## 4. Admission Approval & Enrollment (registrar)

- **Fix (MEDIUM):** Blank "Temporary Password" in the approval email — `RegistrarAdmissionController.php:160` passes `''`; `emails/admission-credentials-mail.blade.php:7` renders `Temporary Password: ` (empty).
- **Fix (MEDIUM):** Approval email is queued but **no queue worker runs** — emails sit in the `jobs` table forever. Run `php artisan queue:work` or switch to `send()`.
- **Fix (LOW):** `student_number` generation race — `app/Models/Student.php:47`. Also groups by `created_at` year (pre-admission year) instead of approval year. Add unique constraint.
- **Add (MEDIUM):** Approval/Rejection notification emails to the student.

> **All of the above: FIXED 2026-08-04** (see `bug_report.md` #3, #4, #8).

---

## 5. Assessment & Payment (cashier)

- **Fix (HIGH):** Discount wiped on every payment — `CashierController.php:111-118`, **confirmed live** (₱5,000 discount reset to ₱0 after one payment). The payment form (`cashier/payment.blade.php`) exposes no discount field at all.
- **Fix (LOW):** Receipt number race — `CashierController.php:122` `COUNT()+1`, no unique constraint on `receipt_number`.
- **Add (HIGH):** Dedicated discount management UI — grant/edit a discount once and persist it in the ledger.
- **Add (MEDIUM):** Printable/reprintable receipt (PDF).

> **Discount management UI: FIXED 2026-08-04** — `cashier/discounts` page, `discount_type` column, Alpine.js modal, preserves discount across payments.

---

## 6. IT Confirmation (admin)

- **Fix:** none found. `AdminController@confirmAccount` works.
- **Add (LOW):** Send a "portal activated" email when the account is IT-confirmed.

---

## 7. Account Management & First-Login Experience

- **Fix (MEDIUM):** Profile "Delete Account" (`ProfileController.php:43-59`) cascades to delete the Student record and downstream data (`students.user_id ... onDelete('cascade')`).
  - **Decided fix: archive/deactivate instead of delete.**
    - Set user `status='inactive'` (+ student `status='archived'`). Login is already blocked for inactive users via `User::getAuthPassword()`.
    - **Add a required reason textbox** when deleting/archiving an account (why the account is being closed/removed).
    - **Add an action dropdown** for the disposition: **Transfer** or **Graduated** (records the reason the student's record is being closed). The selected action + reason are stored and shown on the student's record.
- **Add (HIGH):** Change-password prompt on first login — the app already records `first_login_at`/`last_login_at` (`AuthenticatedSessionController@store`); after the first login, force/remind the user to change their temporary password (relevant since inquiry-created accounts receive a random 8-char password by email).
- **Add (MEDIUM):** Brief onboarding/instruction prompt for newly logged-in users (especially students) shown after first login, and **tracked through the activity log** via `log_activity()` (event e.g. `first_login_instructions_shown`) so staff can see it was presented.

> **First-login password prompt: FIXED 2026-08-04** — `ForceChangePasswordController` + route + view; redirects when `first_login_at` is null; sets timestamp + logs activity on completion. 30 users currently affected.

---

## 8. Classes / Schedules / Teachers (admin, directress, principal)

- **Fix:** none found. Schedule conflict detection (`PrincipalController@schedulesStore`) works.
- **Add (LOW):** Room/building master list instead of free-text `room` field.

---

## 9. Grades & Assessments (teacher)

- **Fix:** none found. Grade save + submit (`TeacherController@storeGrades`/`submitGrades`) work; teacher ownership guard works.
- **Add (MEDIUM):** Auto-computed term average (GWA) per student.
- **Add (LOW):** Submission deadline enforcement per grading period.

---

## 10. Library (librarian)

- **Fix (MEDIUM):** Loans keyed by book title — `app/Models/Book.php:25`, `LibraryTransaction.php:15`, `LibrarianController.php:164-189`. Editing a book title orphans history and breaks `available_quantity` restoration; duplicate titles cross-match. Add `book_id` FK and match by id.
- **Add (MEDIUM):** Overdue email reminders.
- **Add (LOW):** Overdue fines.

> **Library fix: FIXED 2026-08-04** (see `bug_report.md` #6).

---

## 11. Clinic (nurse)

- **Fix:** none found. Log creation works.
- **Add (MEDIUM):** Per-student medical history timeline.
- **Add (LOW):** Medicine inventory/stock tracking.

---

## 12. Report Cards / Promotion

- **Fix:** none found. View + print (`dompdf`) work; batch promotion route exists.
- **Add (MEDIUM):** Batch PDF report cards for an entire class/section.

---

## 13. Withdrawal

- **Fix:** none found.
- **Add (MEDIUM):** Clearance checklist before approval (fees, library returns, clinic) — currently approves with one click (`WithdrawalController@approve`).

---

## 14. Graduation Fees (directress)

- **Fix:** none found.

---

## 15. Cross-cutting / Ops

- **Fix (HIGH):** Timezone `UTC` → `Asia/Manila` — `config/app.php:68` (+ `php.ini`). **Confirmed live:** receipts stamped `RCP-20260803-...` on local date 08-04; breaks `today()` stats, overdue checks, timestamps.
- **Fix (MEDIUM):** Start a queue worker or switch approval/grade emails to `send()`.
- **Fix (INFO):** `.env APP_NAME=Laravel` → `Agnus Dei School Systems`.
- **Add (HIGH):** Scheduled DB backups (mysqldump).
- **Add (HIGH, NEW):** **Audit logs section in admin (tracks everything)** — a full admin page that surfaces ALL activity-log entries with filters (by user, by event, by date), not just the latest 5 on the dashboard. "Tracks everything": every `log_activity()` call, login/logout, CRUD create/update/delete, payment, grade save, requirement verify, archive/deactivate, first-login password change, etc. (Admin dashboard currently shows only the latest 5.)
- **Add (MEDIUM):** Full activity-log viewer (admin dashboard shows only the latest 5).
- **Add (LOW):** Automated tests for the payment and grades flows.

> **Cross-cutting fixes (timezone + send()): DONE 2026-08-04.** DB backups, audit-logs section, and API: DONE 2026-08-04.

---

## 16. API / Integrations (NEW SECTION)

- **Add (MEDIUM/HIGH, NEW):** **API authorized access per role** — build a REST API (e.g. `/api/*`) with token-based authentication (Sanctum) and per-role authorization so each role can only access its own endpoints (admin → users/settings, registrar → admissions/enrollments, cashier → payments/ledgers, teacher → grades/classes, librarian → books/loans, nurse → clinic logs, student → own records, etc.). Include rate limiting and audit logging of API calls.

> **API per role: FIXED 2026-08-04** — Sanctum installed, `POST /api/auth/token` issues personal access tokens, 20+ role-scoped GET endpoints, `CheckRole` returns JSON 403, 60 req/min rate limit.

---

## Suggested fix order (fastest wins first)

> **All bugs 1–8 + first-login password enforcement + HIGH features H1–H5: DONE 2026-08-04.** Remaining work is MEDIUM features only (see STATUS SUMMARY below).

1. ~~Timezone → `Asia/Manila`~~ ✅
2. ~~Cashier discount persistence~~ ✅
3. ~~Account deletion → archive/inactive with reason + action dropdown~~ ✅
4. ~~Admission email: blank password + queue→send~~ ✅
5. ~~First-login password-change prompt~~ ✅
6. ~~Library `book_id` FK migration~~ ✅
7. ~~Receipt/application/student-number unique constraints~~ ✅
8. ~~Rate-limit `/inquiry` form~~ ✅ H1
9. ~~Audit logs section in admin~~ ✅ H4
10. ~~Discount management UI~~ ✅ H2
11. ~~Scheduled DB backups~~ ✅ H3
12. ~~REST API per role~~ ✅ H5
13. MEDIUM features by priority within each workflow stage.

---

## STATUS SUMMARY (updated 2026-08-04)

### ✅ DONE
| # | Item | Section |
|---|------|---------|
| 1 | Timezone → Asia/Manila | 15 |
| 2 | Cashier discount persistence (form + clamp) | 5 |
| 3 | Account archive/deactivate + reason + action dropdown | 7 |
| 4 | Admission email: blank password + queue→send | 4 |
| 5 | First-login password-change prompt | 7 |
| 6 | Library `book_id` FK migration | 10 |
| 7 | Receipt/application/student-number races | 2,4,5 |
| 8 | .env APP_NAME fix | 15 |
| H1 | Rate-limit `/inquiry` form (5 req/min/IP) — FIXED 2026-08-04 | 1 |
| H2 | Dedicated discount management UI (cashier) — FIXED 2026-08-04 | 5 |
| H3 | Scheduled DB backups (PHP PDO backup command + daily schedule) — FIXED 2026-08-04 | 15 |
| H4 | Audit logs section in admin (filters by user/event/date, login/logout + CRUD logging) — FIXED 2026-08-04 | 15 |
| H5 | API authorized access per role (Sanctum, per-role endpoints, rate limiting) — FIXED 2026-08-04 | 16 |
| L5 | Overdue fines — DONE 2026-08-06 via feature_enhancements.md #2 (late/damage/lost fees, settings-configurable) | 10 |

### 🟡 MEDIUM — not started
| # | Item | Section |
|---|------|---------|
| M1 | Allow editing `Pending` admission applications | 2 |
| M2 | Email student when requirements verified | 3 |
| M3 | Approval/rejection notification emails | 4 |
| M4 | Printable/reprintable receipt (PDF) | 5 |
| M5 | Onboarding instructions after first login (tracked via `log_activity`) | 7 |
| M6 | Auto-computed GWA per student | 9 |
| M7 | Overdue email reminders | 10 |
| M8 | Per-student medical history timeline | 11 |
| M9 | Batch PDF report cards | 12 |
| M10 | Withdrawal clearance checklist | 13 |
| M11 | Full activity-log viewer (admin) — superseded by H4 audit-logs page | 15 |

### 🟢 LOW — not started
| # | Item | Section |
|---|------|---------|
| L1 | Set `email_verified_at` on inquiry accounts | 1 |
| L2 | IT-confirmation "portal activated" email | 6 |
| L3 | Room/building master list | 8 |
| L4 | Submission deadline enforcement | 9 |
| ~~L5~~ | ~~Overdue fines~~ — **DONE 2026-08-06** (see DONE table) | 10 |
| L6 | Medicine inventory/stock tracking | 11 |
| L7 | Automated tests for payment/grades flows | 15 |
