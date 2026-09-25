# Full Audit-Log Coverage + Promotion Margins — 2026-09-25

> **Request:** (1) Make sure ALL events happening within the system show in the audit logs.
> (2) Fix margins based on the End-of-Year Promotion screenshot.
> **Status:** ✅ Executed — MDs written first, all 27 gaps implemented, `php -l` + `php artisan view:cache` clean, 94 total `log_activity()` calls (was 66)

---

## 1. Coverage Audit (read-only scan of all 37 controllers)

**Before:** 64 `log_activity()` calls. **Gap analysis found 27 mutating/security-relevant actions with no logging**, across 5 buckets.

### A. Student-facing flows (highest impact — zero logging today)

| File | Method | Event to add | Subject |
|------|--------|--------------|---------|
| `Portal/StudentAdmissionController.php:129` | `store()` | `Admission Submitted` | created `$admission` |
| `Portal/StudentAdmissionController.php:61` | `saveDraft()` | `Admission Draft Saved` | `$admission` |
| `Portal/StudentAdmissionController.php:232` | `discardDraft()` | `Admission Draft Discarded` | `$draft` |
| `Portal/StudentAdmissionController.php:268` | `uploadRequirements()` | `Requirements Uploaded` | `$admission` |
| `Portal/StudentEnrollmentController.php:42` | `store()` | `Enrollment Request Submitted` | created `$admission` |
| `PromotionalWebsite/InquiryController.php:23` | `store()` | `Account Created` | created `$user` (public route, causer = System) |

### B. Security / auth events

| File | Method | Event to add | Subject |
|------|--------|--------------|---------|
| `app/Http/Requests/Auth/LoginRequest.php` | `authenticate()` failed path | `Login Failed` | user-by-email or null |
| `app/Http/Requests/Auth/LoginRequest.php` | `ensureIsNotRateLimited()` lockout | `Login Locked Out` | null |
| `Auth/PasswordController.php:16` | `update()` (own password) | `Password Changed` | `$request->user()` |
| `Auth/NewPasswordController.php:32` | `store()` (token reset) | `Password Reset` | user in closure |
| `Auth/PasswordResetLinkController.php:27` | `store()` | `Password Reset Requested` | user by email / null |
| `Auth/VerifyEmailController.php:15` | `__invoke()` | `Email Verified` | `$request->user()` |
| `Auth/EmailVerificationNotificationController.php:14` | `store()` | `Verification Email Sent` | `$request->user()` |
| `Auth/ConfirmablePasswordController.php:25` | `store()` | `Password Confirmed` | `$request->user()` |
| `Api/ApiController.php:41` | `token()` 401/403 paths | `Login Failed` | `$user` when found |
| `ProfileController.php:27` | `update()` | `Profile Updated` | `$request->user()` |

**Not routed (dead code — skip):** `RegisteredUserController` (routes commented out in `routes/auth.php:16-19`), root `InquiryController` (empty stub).

### C. Exports (PII leaving the system)

| File:Line | Method | Event |
|-----------|--------|-------|
| `Portal/ExportController.php:13/37/75` | `enrollments/grades/collections` | `Exported` + what was exported |
| `Portal/CashierController.php:443` | `collectionsReportExport()` | `Exported` |
| `Portal/DirectressController.php:448/471` | `exportLibraryReports/exportCashierReports` | `Exported` |
| `Portal/ReportCardController.php:132` | `print()` | `Report Card Exported` |
| `Admin/SubjectController.php:89` + `PrincipalController.php:218` | static templates | skip (no real data) |

### D. Announcements + misc

| File:Line | Method | Event |
|-----------|--------|-------|
| `Portal/PrincipalController.php:452/476/494` | announcements store/update/destroy | `Announcement Created/Updated/Deleted` |
| `Admin/SubjectController.php:103` | `import()` (bulk CSV) | `Subjects Imported` |

### E. Already covered (no change) — 64 existing calls
Login/Logout, Password Changed (first-login), Archived, staff/section/subject CRUD + status/reset, promotion 5 outcomes, payments/discount, all librarian book/loan/clock events, clinic log, schedules CRUD/import, fee schedules + graduation fees + school years + locks, requirement verify/approve/reject, teacher grades/assessments (5 events), withdrawals (3), account confirm + settings (added earlier today), API token.

### F. Event colors to add (audit-logs-results.blade.php)
`Login Failed`/`Login Locked Out` (red), `Password Confirmed`/`Password Reset Requested`/`Email Verified`/`Verification Email Sent` (orange), `Profile Updated`/`Account Created`/`Admission Submitted`/`Requirements Uploaded`/`Enrollment Request Submitted`/`Admission Draft Saved/Discarded` (violet), `Exported`/`Report Card Exported`/`Subjects Imported` (amber), `Announcement Created/Updated/Deleted` (cyan/sky/red).

---

## 2. Promotion Page Margins (screenshot-based)

From the End-of-Year Promotion screenshot:

| # | Issue | Fix |
|---|-------|-----|
| 1 | Info note sits in a gray band (`px-4`) but filter pills used `px-1` — pill row didn't line up with note or table columns | Merge note + pills into **one** gray header band (`px-4 pt-3 pb-3`), pills `mt-2.5`, inactive pills get borders so they read on gray |
| 2 | Sticky bar select (`text-sm py-1`) vs Batch button (`text-xs py-1.5`) — mismatched heights | Both `text-xs py-1.5` |
| 3 | Grade tabs / sticky bar / school-year row / cards spacing inconsistent | Unified scale: header `mb-6`, tabs `mb-4`, sticky `mb-5`, toolbar `mb-5`, cards `mb-5`, footer `mt-6 pt-4 border-t` |
| 4 | Table columns cramped (`px-2`, `w-full`) | Cells `px-4 py-3`, `min-w-[960px]`, `w-44` action col, `whitespace-nowrap` headers |
| 5 | Gray band inside `overflow-x-auto` (would scroll off on narrow screens) | Moved the band **outside** the scroll container, table scrolls alone |

**Already shipped in `de10ad3`:** scoped batch select (per-grade vs global), nested-form removal, `x-model` school-year binding.

---

## 3. Verification

- `php -l` on every touched PHP file
- `php artisan view:cache` for blades
- `php artisan route:list` unchanged (no route changes)
- Manual: create admission → check audit logs; failed login → `Login Failed` appears; export CSV → `Exported` appears.
