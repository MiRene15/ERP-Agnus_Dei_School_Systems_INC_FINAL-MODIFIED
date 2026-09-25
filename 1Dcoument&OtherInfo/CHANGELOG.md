# Release Notes

## [Unreleased] — Full Audit-Log Coverage (27 gaps) + Promotion Margins — 2026-09-25
- **Coverage:** scanned all 37 controllers → added `log_activity()` to all **27 gaps** (28 new call sites; now **94 total calls**, was 66)
  - **Student flows:** `Admission Submitted`, `Admission Draft Saved`, `Admission Draft Discarded`, `Requirements Uploaded`, `Enrollment Request Submitted`, public inquiry `Account Created`
  - **Security/auth:** `Login Failed` + `Login Locked Out` (LoginRequest), own-password `Password Changed`, token `Password Reset`, `Password Reset Requested`, `Email Verified`, `Verification Email Sent`, `Password Confirmed`, `Profile Updated`, API 401/403 `Login Failed`
  - **Exports (PII):** `Exported` on admin enrollments/grades/collections, cashier collections export, directress library/cashier exports, `Report Card Exported`, `Subjects Imported` bulk CSV
  - **Announcements:** `Announcement Created/Updated/Deleted` (Principal)
  - **Skipped (dead code):** `RegisteredUserController` (routes commented out), root `InquiryController` stub
- **Colors:** new event badge colors for all of the above in `audit-logs-results.blade.php`
- **Promotion margins (per screenshot):** note + filter pills merged into one aligned gray band (`px-4`), inactive pills bordered, sticky-bar select/button heights matched, gray band moved outside the scroll container, spacing scale unified
- See `audit_logs_full_coverage_20260925.md`

## [Unreleased] — Promotion: Scoped Batch Select + Margin Fixes — 2026-09-25
- **Bug:** per-grade "select all" checkbox used a global `document.querySelectorAll('.promo-checkbox')`, so selecting all in Kinder checked **all students in all grade levels** — and there was no separate global select-all box
- **Fix:** row checkboxes now carry `data-grade` / `data-qualified`; per-grade header checkbox is scoped to its own table (`setGrade()`), plus a distinct global **"Select all students (all grade levels)"** control (`setAll()`), global "Qualified only (all)" and "Clear selection"
- **Sync:** per-grade and global headers now reflect real row state with `checked` + `indeterminate` (`syncHeaders()`); "Select qualified in {grade}" no longer guesses visibility via `row.style.display`
- **Margins:** unified spacing scale (`mb-6`/`mb-5`/`mb-4`), table cells `px-4 py-3`, `min-w-[960px]` so columns stop collapsing, `w-44` action select, footer row `mt-6 pt-4 border-t`
- **Also fixed:** removed the bottom batch form that was nested inside the "Process All Actions" form (invalid HTML — never submitted); batch controls stay in the sticky bar, and the school-year select is now `x-model`-bound so batch promote submits the chosen year
- See `promotion_batch_select_fix_20260925.md`

## [Unreleased] — Audit Logs: Missing Events + Filter Fixes — 2026-09-25
- **Root cause:** admin "confirm student account" actions wrote **no** audit log — `AdminController::confirmAccount()` and `confirmBatch()` had zero `log_activity()` calls, so verifying a student produced no event (registrar requirement verification was already logged)
- **New events:** `Account Confirmed` (single), `Accounts Confirmed` (batch, with student names), `Settings Updated` (admin settings save) — `AdminController.php`
- **Filter fixes (`AdminController::auditLogs`):** search now also matches **causer name** and **subject type** (was description/event only), input is trimmed, new **System (no user)** user option filters `causer_id IS NULL`
- **Filter UX (`admin/audit-logs.blade.php`):** initial filter state passed via `@js([...])` instead of unescaped Blade interpolation inside a JS object (quotes in search text/event names could break the component); filter panel auto-opens when any filter is preset via URL/pagination
- **Filter UX (`resources/js/app.js`):** `showAdvanced` is now derived from `initialFilters` (affects audit logs + librarian books) — `vite build` rerun
- **Colors:** added `Account Confirmed`, `Accounts Confirmed`, `Settings Updated`, `API Token Created`, `Schedule Created/Updated/Imported/Deleted` to the event badge map

## [Unreleased] — Strands→Electives + Directress Demographics — 2026-09-23
- **Strands → Electives:** renamed user-facing labels Strand → Elective across admissions, COR, report cards, demographics (`byStrand`→`byElective`), validation enum kept (Arts/SocSci/Humanities + Business/Entrepreneurship)
- **Directress Demographics:** ensured Demographics page accessible via `directress.demographics` route + sidebar `Demographics` link (first item) + chart views (By Grade/Section/Year/Elective)

## [Unreleased] — Batch Requests 2026-09-23 (16 items) — 2026-09-23
- **Verification audit logs:** fix admin Verify not showing — correct `log_activity()` event/causer on `RegistrarAdmissionController.php:verifyRequirement/verifyAll` + visible color
- **Grade rank:** grade levels sorted Kinder→Grade 12 via rank map (was alphabetical Grade 10 before Grade 2)
- **Promotion:** per-level batch + global All, batch button top-right fixed, qualified-only filtering, carried balance added to new tuition via `carryFees()`
- **Cashier payments:** remove floating search modal on `cashier/payments.blade.php`
- **Financial view:** include library fees (late/lost) from `LibraryTransaction` in ledger breakdown + statement of accounts
- **Breakdown list:** own full table section — Fee Breakdown with Tuition/Misc/Library/Discount/Carried/Assessed/Paid/Balance per term + summary
- **Reports tab:** rename Collections Report → Reports with collapsible Collections Report + Receivables Report (balance>0, by grade)
- **Principal schedules:** refine CRUD — index/create/edit/delete, validation, dark mode, audit logs
- **Section names:** actual names (St. Agnes etc.) for all 13 grade levels in seeders
- **Advisers/Teachers:** every section/class/schedule connected via `TeachersClassesSchedulesSeeder.php`
- **Seeders:** 75 students (50–100) diverse distribution + library/borrow mix
- **Report card:** GA + Remarks column-aligned; grades from `grades.final_grade` accurately shown per subject/term
- **Grade table:** remove `teacher.grade-table` tab/route/sidebar link + button
- **Grade assessment:** add student search on assessment list

## [Unreleased] — Promotion Filters, Audit Logs, Reports Enhancement — 2026-09-23
- **Promotion:** added grade-level filter tabs (All Grades + per-grade) to `promotion-index-results.blade.php:7` — no more scrolling through all grades
- **Audit Logs:** fixed display — added 30+ event colors (was 7), dark mode on audit page + results table; verified `AdminController.php:auditLogs` shows all 60 events with `withQueryString()` pagination
- **Library Reports:** export CSV (`DirectressController.php:exportLibraryReports` + `web.php:library-reports.export`) + Chart.js doughnut (Available/Borrowed/Overdue) + bar (Popular Books)
- **Cashier Reports:** export CSV (`DirectressController.php:exportCashierReports` + `web.php:cashier-reports.export`) + Chart.js bar (Monthly Collected + Receipts) with dark-aware colors

## [Unreleased] — Dark Mode Readability & Contrast — 2026-09-23
- Added `darkMode: 'class'` to `tailwind.config.js` (was `media` — `dark:` utilities were dead-code)
- Extended `portal/layouts/app.blade.php` global dark overrides: amber/slate/blue, hovers, skeleton, sidebar
- Added explicit `dark:` classes to all portal pages — cards, tables, inputs, modals, badges, hovers
- Fixed promotional website dark mode — hero overlay, nav dropdown, inquiry inputs, footer
- Full audit in `dark_mode_audit.md` — 28 files, 120 hovers, systemic gaps → all readable/contrasting
- See also: Validation Notes (30 items, all phases), Grade Table (spreadsheet), Audit Logs (60 calls)

## [Unreleased](https://github.com/laravel/laravel/compare/v12.12.1...12.x)

### Full AJAX Conversion — 2026-08-20
- Converted 22 traditional full-page-reload pages to AJAX across 9 portals
- **Teacher portal** (7 pages): classes, class-list, grade-assessment, computed-grades, schedule, dashboard, class-students — all use `ajaxTable` with server-side search/filter and skeleton loading
- **Student portal** (5 pages): dashboard, schedule, ledger, report-card, admission-status — all use `ajaxTable` with skeleton loading
- **Admin portal** (3 pages): dashboard, pending-accounts, promotion/index — ajaxTable
- **Registrar** (3 pages): dashboard, admissions-show, report-cards/show — ajaxTable
- **Cashier** (2 pages): dashboard, student-financial — ajaxTable
- **Librarian, Nurse, Principal, Directress** dashboards — all ajaxTable with skeleton loading
- Created 26+ new partial views in `partials/` subdirectories
- Added AJAX support to 10 controllers
- Remaining non-AJAX pages are all forms (create/edit/grade entry) or standalone print pages (COR, report-card print)

### Sidebar Active-State Fix — 2026-08-19
- Fixed Onboarding tab incorrectly highlighting when clicking Dashboard (`sidebar-admin.blade.php`)

### Admin Account Management — 2026-08-19
- Created collapsible "Account Management" sidebar section with Staff + Students sub-links
- New `StudentAccountController` for student account management (list, view, toggle status, reset password)
- Student account views and AJAX partials
- Re-ran `SystemRolesAndStaffSeeder` — role IDs now correct (8=Directress, 9=Principal)
- Added login redirect for roles 8 and 9 in `AuthenticatedSessionController`

### Performance Optimization — 2026-08-18
- Created `PERFORMANCE_OPTIMIZATION.md` — 56 performance issues identified across 10 categories
- **Phase 1 — Quick Wins:**
  - Cached `active_school_year()` helper (eliminates 1 DB query per page load)
  - Cached `all_school_years()` helper (eliminates 3 DB queries per call)
  - Cached `Setting::getValue()` model with cache invalidation on `setValue()`
  - Added 20 database indexes across 10 tables (enrollments, classes, payments, assessments, fee_schedules, student_ledgers, activity_log, library_transactions, students, admissions)
- **Phase 2 — N+1 Query Fixes:**
  - ExportController::grades() — pre-fetched all grades (500+ queries → 1)
  - TeacherController::computedGrades() — pre-fetched assessments and grades (80 queries → 2)
  - TeacherController::index() — eager-loaded enrollments for count
  - TeacherController::schedule() — loaded all schedules in 1 query (5 queries → 1)
  - CashierController::payments() and searchStudents() — pre-fetched fee schedules
  - CashierController::showPayment/printReceipt/studentFinancial() — used already-loaded enrollment
- **Phase 3 — Blade View Fixes:**
  - cashier/payment.blade.php — replaced `payments()->count()` with `payments->isEmpty()`
  - cashier/student-financial.blade.php — replaced `payments()->count()` with `payments->isNotEmpty()`
  - cashier/partials/discounts-results.blade.php — replaced `enrollments()->where()` with `enrollments->where()`
- **Phase 4 — Email Queueing:**
  - Added `ShouldQueue` to all 5 mail classes (GradesSubmittedMail, AdmissionCredentialsMail, InquiryCredentialsMail, InquiryVerificationMail, PaymentReminderMail)
- **Phase 5 — API Pagination:**
  - Added `paginate(50)` to 8 ApiController endpoints (adminUsers, registrarAdmissions, registrarStudents, cashierLedgers, librarianBooks, directressFees, principalSchedules, principalAnnouncements)
- Updated `workflow_priority_plan.md` with performance items (H9-H16, L8-L10)
- Updated `update_sessions_log.md` with Session 39

## [v12.12.1](https://github.com/laravel/laravel/compare/v12.12.0...v12.12.1) - 2026-03-10

* [12.x] Makes imports consistent by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/laravel/pull/6760

## [v12.12.0](https://github.com/laravel/laravel/compare/v12.11.2...v12.12.0) - 2026-03-09

* Update phpunit version to ^11.5.50 to address CVE by [@PerryvanderMeer](https://github.com/PerryvanderMeer) in https://github.com/laravel/laravel/pull/6746
* [12.x] Add `APP_NAME` fallback in mail config by [@apoorvdarshan](https://github.com/apoorvdarshan) in https://github.com/laravel/laravel/pull/6755
* [12.x] Neutralize DB_URL in default phpunit.xml by [@Husseinadq](https://github.com/Husseinadq) in https://github.com/laravel/laravel/pull/6761

## [v12.11.2](https://github.com/laravel/laravel/compare/v12.11.1...v12.11.2) - 2026-01-19

* [12.x] Update composer dev script to ensure no timeout by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/laravel/pull/6735
* [12.x] Update jobs/cache migrations by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/laravel/pull/6736
* [12.x] Remove failed jobs indexes by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/laravel/pull/6739
* [12.x] Add `APP_URL` fallback in filesystems config by [@KentarouTakeda](https://github.com/KentarouTakeda) in https://github.com/laravel/laravel/pull/6742
* chore: Update outdated GitHub Actions version by [@pgoslatara](https://github.com/pgoslatara) in https://github.com/laravel/laravel/pull/6743

## [v12.11.1](https://github.com/laravel/laravel/compare/v12.11.0...v12.11.1) - 2025-12-23

* Use environment variable for `DB_SSLMODE` - Postgres by [@robsontenorio](https://github.com/robsontenorio) in https://github.com/laravel/laravel/pull/6727
* fix: ensure APP_URL does not have trailing slash in filesystem by [@msamgan](https://github.com/msamgan) in https://github.com/laravel/laravel/pull/6728

## [v12.11.0](https://github.com/laravel/laravel/compare/v12.10.1...v12.11.0) - 2025-11-25

* fix: cookies are not available for subdomains by default by [@joostdebruijn](https://github.com/joostdebruijn) in https://github.com/laravel/laravel/pull/6705
* Fix PHP 8.5 PDO Driver Specific Constant Deprecation by [@RyanSchaefer](https://github.com/RyanSchaefer) in https://github.com/laravel/laravel/pull/6710
* Ignore Laravel compiled views for Vite  by [@QistiAmal1212](https://github.com/QistiAmal1212) in https://github.com/laravel/laravel/pull/6714

## [v12.10.1](https://github.com/laravel/laravel/compare/v12.10.0...v12.10.1) - 2025-11-06

* Update schema URL in package.json by [@robinmiau](https://github.com/robinmiau) in https://github.com/laravel/laravel/pull/6701

## [v12.10.0](https://github.com/laravel/laravel/compare/v12.9.1...v12.10.0) - 2025-11-04

* Add background driver by [@barryvdh](https://github.com/barryvdh) in https://github.com/laravel/laravel/pull/6699

## [v12.9.1](https://github.com/laravel/laravel/compare/v12.9.0...v12.9.1) - 2025-10-23

* [12.x] Replace Bootcamp with Laravel Learn by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6692
* [12.x] Comment out CLI workers for fresh applications by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6693

## [v12.9.0](https://github.com/laravel/laravel/compare/v12.8.0...v12.9.0) - 2025-10-21

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.8.0...v12.9.0

## [v12.8.0](https://github.com/laravel/laravel/compare/v12.7.1...v12.8.0) - 2025-10-20

* [12.x] Makes test suite using broadcast's `null` driver by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/laravel/pull/6691

## [v12.7.1](https://github.com/laravel/laravel/compare/v12.7.0...v12.7.1) - 2025-10-15

* Added `failover` driver to the `queue` config comment.  by [@sajjadhossainshohag](https://github.com/sajjadhossainshohag) in https://github.com/laravel/laravel/pull/6688

## [v12.7.0](https://github.com/laravel/laravel/compare/v12.6.0...v12.7.0) - 2025-10-14

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.6.0...v12.7.0

## [v12.6.0](https://github.com/laravel/laravel/compare/v12.5.0...v12.6.0) - 2025-10-02

* Fix setup script by [@goldmont](https://github.com/goldmont) in https://github.com/laravel/laravel/pull/6682

## [v12.5.0](https://github.com/laravel/laravel/compare/v12.4.0...v12.5.0) - 2025-09-30

* [12.x] Fix type casting for environment variables in config files by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6670
* Fix CVEs affecting vite by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/laravel/pull/6672
* Update .editorconfig to target compose.yaml by [@fredikaputra](https://github.com/fredikaputra) in https://github.com/laravel/laravel/pull/6679
* Add pre-package-uninstall script to composer.json by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/laravel/pull/6681

## [v12.4.0](https://github.com/laravel/laravel/compare/v12.3.1...v12.4.0) - 2025-08-29

* [12.x] Add default Redis retry configuration by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/laravel/pull/6666

## [v12.3.1](https://github.com/laravel/laravel/compare/v12.3.0...v12.3.1) - 2025-08-21

* [12.x] Bump Pint version by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6653
* [12.x] Making sure all related processed are closed when terminating the currently command by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6654
* [12.x] Use application name from configuration by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6655
* Bring back postAutoloadDump script by [@jasonvarga](https://github.com/jasonvarga) in https://github.com/laravel/laravel/pull/6662

## [v12.3.0](https://github.com/laravel/laravel/compare/v12.2.0...v12.3.0) - 2025-08-03

* Fix Critical Security Vulnerability in form-data Dependency by [@izzygld](https://github.com/izzygld) in https://github.com/laravel/laravel/pull/6645
* Revert "fix" by [@RobertBoes](https://github.com/RobertBoes) in https://github.com/laravel/laravel/pull/6646
* Change composer post-autoload-dump script to Artisan command by [@lmjhs](https://github.com/lmjhs) in https://github.com/laravel/laravel/pull/6647

## [v12.2.0](https://github.com/laravel/laravel/compare/v12.1.0...v12.2.0) - 2025-07-11

* Add Vite 7 support by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/laravel/pull/6639

## [v12.1.0](https://github.com/laravel/laravel/compare/v12.0.11...v12.1.0) - 2025-07-03

* [12.x] Disable nightwatch in testing by [@laserhybiz](https://github.com/laserhybiz) in https://github.com/laravel/laravel/pull/6632
* [12.x] Reorder environment variables in phpunit.xml for logical grouping by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6634
* Change to hyphenate prefixes and cookie names by [@u01jmg3](https://github.com/u01jmg3) in https://github.com/laravel/laravel/pull/6636
* [12.x] Fix type casting for environment variables in config files by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6637

## [v12.0.11](https://github.com/laravel/laravel/compare/v12.0.10...v12.0.11) - 2025-06-10

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.0.10...v12.0.11

## [v12.0.10](https://github.com/laravel/laravel/compare/v12.0.9...v12.0.10) - 2025-06-09

* fix alphabetical order by [@Khuthaily](https://github.com/Khuthaily) in https://github.com/laravel/laravel/pull/6627
* [12.x] Reduce redundancy and keeps the .gitignore file cleaner by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6629
* [12.x] Fix: Add void return type to satisfy Rector analysis by [@Aluisio-Pires](https://github.com/Aluisio-Pires) in https://github.com/laravel/laravel/pull/6628

## [v12.0.9](https://github.com/laravel/laravel/compare/v12.0.8...v12.0.9) - 2025-05-26

* [12.x] Remove apc by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6611
* [12.x] Add JSON Schema to package.json by [@martinbean](https://github.com/martinbean) in https://github.com/laravel/laravel/pull/6613
* Minor language update by [@woganmay](https://github.com/woganmay) in https://github.com/laravel/laravel/pull/6615
* Enhance .gitignore to exclude common OS and log files by [@mohammadRezaei1380](https://github.com/mohammadRezaei1380) in https://github.com/laravel/laravel/pull/6619

## [v12.0.8](https://github.com/laravel/laravel/compare/v12.0.7...v12.0.8) - 2025-05-12

* [12.x] Clean up URL formatting in README by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6601

## [v12.0.7](https://github.com/laravel/laravel/compare/v12.0.6...v12.0.7) - 2025-04-15

* Add `composer run test` command by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/laravel/pull/6598
* Partner Directory Changes in ReadME by [@joshcirre](https://github.com/joshcirre) in https://github.com/laravel/laravel/pull/6599

## [v12.0.6](https://github.com/laravel/laravel/compare/v12.0.5...v12.0.6) - 2025-04-08

**Full Changelog**: https://github.com/laravel/laravel/compare/v12.0.5...v12.0.6

## [v12.0.5](https://github.com/laravel/laravel/compare/v12.0.4...v12.0.5) - 2025-04-02

* [12.x] Update `config/mail.php` to match the latest core configuration by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6594

## [v12.0.4](https://github.com/laravel/laravel/compare/v12.0.3...v12.0.4) - 2025-03-31

* Bump vite from 6.0.11 to 6.2.3 - Vulnerability patch by [@abdel-aouby](https://github.com/abdel-aouby) in https://github.com/laravel/laravel/pull/6586
* Bump vite from 6.2.3 to 6.2.4 by [@thinkverse](https://github.com/thinkverse) in https://github.com/laravel/laravel/pull/6590

## [v12.0.3](https://github.com/laravel/laravel/compare/v12.0.2...v12.0.3) - 2025-03-17

* Remove reverted change from CHANGELOG.md by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/laravel/pull/6565
* Improves clarity in app.css file by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6569
* [12.x] Refactor: Structural improvement for clarity by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6574
* Bump axios from 1.7.9 to 1.8.2 - Vulnerability patch by [@abdel-aouby](https://github.com/abdel-aouby) in https://github.com/laravel/laravel/pull/6572
* [12.x] Remove Unnecessarily [@source](https://github.com/source) by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/laravel/pull/6584

## [v12.0.2](https://github.com/laravel/laravel/compare/v12.0.1...v12.0.2) - 2025-03-04

* Make the github test action run out of the box independent of the choice of testing framework by [@ndeblauw](https://github.com/ndeblauw) in https://github.com/laravel/laravel/pull/6555

## [v12.0.1](https://github.com/laravel/laravel/compare/v12.0.0...v12.0.1) - 2025-02-24

* [12.x] prefer stable stability by [@pataar](https://github.com/pataar) in https://github.com/laravel/laravel/pull/6548

## [v12.0.0 (2025-??-??)](https://github.com/laravel/laravel/compare/v11.0.2...v12.0.0)

Laravel 12 includes a variety of changes to the application skeleton. Please consult the diff to see what's new.
