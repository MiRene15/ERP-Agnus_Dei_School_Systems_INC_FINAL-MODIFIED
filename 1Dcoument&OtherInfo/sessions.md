# Sessions Log — ERP Agnus Dei School Systems INC

> All session/documentation files now located in: `1Dcoument&OtherInfo/`

## Session: Aug 14, 2026 (continued)

### Completed (this session)
- **Removed My Grades** — redundant with Report Card; deleted blade, route, controller method, sidebar link, dashboard button
- **COR reverted to standalone printable** — self-contained A4 landscape page with toolbar (Back + Print), logo, compact sizing to fit one page
- **Class Schedule — removed Subject-Teacher column** — teacher info already shown in day cells
- **All 46 account passwords reset** — standardized to `Agnus2026!`
- **First-login password change → floating modal** — overlay card on top of dashboard instead of standalone page; modal included in `portal/layouts/app.blade.php` so all dashboards get it automatically
- **ForceChangePasswordController updated** — redirects back instead of hardcoded role URLs
- **AuthenticatedSessionController updated** — removed redirect to force-change-password; users go to dashboard, modal appears there
- **Student dashboard buttons** — fixed to 4-column single row (Schedule, Account, Report Card, COR)
- **First-login tutorial cards for all 9 roles** — only shows on first login, dismissable via AJAX, `has_seen_welcome` column added to users table
- **Sidebar active state highlighting** — all 9 sidebars now show which page the user is on via `request()->routeIs()` checks
- **Email notifications** — already implemented (SendPaymentReminders command runs daily at 8:00, sends on the 12th of each month)
- **Moved all .md files** to `D:\xampp\htdocs\ERP_Agnus_Dei_School_Systems_INC\1Dcoument&OtherInfo/`
- **Removed "Powered by Laravel x Agile Tech"** — from PromotionalWebsite layout footer
- **Scroll-to-top button** — bottom-right floating arrow in portal layout, appears after scrolling 300px
- **Cashier payment success modal** — after payment, shows floating card with "Done" and "Print Receipt" buttons
- **Teacher: assessment types updated** — `Performance Task` → `Seatwork`, `Semestral Assessment` → `Exam`; new types: Written Work, Quiz, Seatwork, Exam
- **Teacher: List of Classes sub-tab** — `/teacher/class-list`, pick a class → master student list
- **Teacher: Grade Assessment sub-tab** — `/teacher/grade-assessment`, pick class → pick student → enter scores across 4 categories (Written Work, Quiz, Seatwork, Exam) with 3 rows each
- **Teacher: Computed Grades sub-tab** — `/teacher/computed-grades`, pick class → see weighted computed % per category per student → batch save final grades
- **Teacher sidebar updated** — 5 links: Dashboard, List of Classes, Grade Assessment, Computed Grades, My Schedule
- **Teacher classes page filter** — search input + grade level dropdown for filtering class cards

### Seeding Status
- **All seeders executed successfully:**
  - `SettingsSeeder` — 6 rows (school_name, school_address, school_year, current_term, contact_email, contact_phone)
  - `SystemRolesAndStaffSeeder` — roles + admin/registrar/cashiers/librarian/nurse + 20 teachers with teacher profiles
  - `SubjectsAndSectionsSeeder` — K-12 + SHS subjects, sections per grade level
  - `TeachersClassesSchedulesSeeder` — classes, schedules per grade/section/subject, teacher assignments with conflict avoidance
  - `FeeSchedulesSeeder` — fee schedules per grade/term for 2026-2027 (tuition + misc split into 3 terms)
  - `StudentsAndFeesSeeder` — 13 students (K-12 + SHS) with user accounts, student profiles, admissions, enrollments, enrollment_subject, ledgers, payments
  - `GradesAssessmentsSeeder` — 7,572 assessments + 1,893 grades across 4 categories (Written Work, Quiz, Seatwork, Exam), 3 terms
  - `LibraryAndClinicSeeder` — 20 books, ~15 library transactions, ~10 clinic logs
  - `AnnouncementsTableSeeder` — 4 announcements/events
  - `FixArNumbersSeeder` — AR numbers assigned to all payments (AR-2026-0500 through AR-2026-0518)
- **All accounts password**: `Agnus2026!`

### Pending (from Aug 14 session)
- ~~Commit + push~~ — completed Aug 14

### Database Seeder Overhaul (this session)
- **Reviewed user-written seeders** — `StudentsAndFeesSeeder`, `GradesAssessmentsSeeder`, `LibraryAndClinicSeeder`, `AnnouncementsTableSeeder`, `FixArNumbersSeeder` (user-created); `StudentsSeeder`, `StudentLedgersSeeder`, `BooksAndLibrarySeeder`, `AnnouncementsSeeder` (removed — duplicates of user's seeders)
- **`DatabaseSeeder` rewritten** — reordered to: Settings → Roles/Staff → Subjects/Sections → Teachers/Classes/Schedules → FeeSchedules → StudentsAndFees → Grades/Assessments → Library/Clinic → Announcements → FixArNumbers
- **`GradesAssessmentsSeeder` fixed** — updated assessment types from old (`Performance Task`, `Semestral Assessment`) to new (`Quiz`, `Seatwork`, `Exam`); updated weights (WW 20%, Quiz 20%, Seatwork 20%, Exam 40%); bulk inserts with `upsert` instead of individual Eloquent calls; batched in chunks of 2000 rows to avoid 65535 parameter limit
- **`SettingsSeeder` moved first** — `active_school_year()` helper depends on `settings` table; must seed settings before `StudentsAndFeesSeeder`
- **New migration: `add_grading_period_to_assessments_table`** — adds `grading_period` column to assessments table + unique constraint `(enrollment_id, class_id, type, grading_period)`; cleans duplicate rows before adding constraint
- **New migration: `add_unique_constraint_to_grades_table`** — adds unique constraint `(enrollment_id, class_id, grading_period)` to grades table for upsert support

### Database Changes
- **`has_seen_welcome`** column added to `users` table (boolean, default: false)
- **`assessments.type`** updated: dropped old enum check constraint, changed to varchar(30), new constraint: `Written Work | Quiz | Seatwork | Exam`
- Existing data mapped: `Performance Task` → `Seatwork`, `Semestral Assessment` → `Exam`
- **`assessments.grading_period`** — new varchar column added (nullable), with unique constraint on `(enrollment_id, class_id, type, grading_period)`
- **`grades`** — unique constraint added on `(enrollment_id, class_id, grading_period)`

### Routes Added
- `POST /dismiss-welcome` — sets `has_seen_welcome = true` for current user
- `GET /teacher/class-list` — `teacher.class-list`
- `GET /teacher/class-list/{class}/students` — `teacher.class-list.students`
- `GET /teacher/grade-assessment` — `teacher.grade-assessment`
- `GET /teacher/grade-assessment/{class}/student/{enrollment}` — `teacher.grade-assessment.student`
- `POST /teacher/grade-assessment/{class}/student/{enrollment}` — `teacher.grade-assessment.student.store`
- `GET /teacher/computed-grades` — `teacher.computed-grades`
- `POST /teacher/computed-grades/batch-submit` — `teacher.computed-grades.batch-submit`

### Files Modified This Session
- `resources/views/portal/student/dashboard.blade.php` — removed grades button, added tutorial card
- `resources/views/portal/student/cor.blade.php` — reverted to standalone printable with compact sizing
- `resources/views/portal/student/schedule.blade.php` — removed Subject-Teacher column
- `resources/views/portal/partials/sidebar-student.blade.php` — active state highlighting
- `resources/views/portal/partials/sidebar-admin.blade.php` — active state highlighting
- `resources/views/portal/partials/sidebar-cashier.blade.php` — active state highlighting
- `resources/views/portal/partials/sidebar-teacher.blade.php` — active state highlighting, updated with 5 sub-tab links
- `resources/views/portal/partials/sidebar-registrar.blade.php` — active state highlighting
- `resources/views/portal/partials/sidebar-principal.blade.php` — active state highlighting
- `resources/views/portal/partials/sidebar-nurse.blade.php` — active state highlighting
- `resources/views/portal/partials/sidebar-directress.blade.php` — active state highlighting
- `resources/views/portal/layouts/app.blade.php` — added scroll-to-top button + force-change-password modal include
- `resources/views/portal/teacher/dashboard.blade.php` — tutorial card, filter for My Classes
- `resources/views/portal/teacher/classes.blade.php` — search input + grade level filter dropdown
- `resources/views/portal/admin/dashboard.blade.php` — tutorial card added
- `resources/views/portal/cashier/dashboard.blade.php` — tutorial card added
- `resources/views/portal/cashier/payment.blade.php` — payment success modal with Done/Print Receipt
- `resources/views/portal/principal/dashboard.blade.php` — tutorial card added
- `resources/views/portal/registrar/dashboard.blade.php` — tutorial card added
- `resources/views/portal/directress/dashboard.blade.php` — tutorial card added
- `resources/views/portal/librarian/dashboard.blade.php` — tutorial card added
- `resources/views/portal/nurse/dashboard.blade.php` — tutorial card added
- `resources/views/PromotionalWebsite/layout.blade.php` — removed "Powered by" text
- `app/Http/Controllers/Portal/StudentController.php` — removed grades() method
- `app/Http/Controllers/Portal/TeacherController.php` — added classList, classStudents, gradeAssessment, gradeAssessmentStudent, storeGradeAssessmentStudent, computedGrades, batchSubmitGrades; updated assessment types to new categories
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — removed first-login redirect
- `app/Http/Controllers/Auth/ForceChangePasswordController.php` — redirects back instead of hardcoded URLs
- `routes/web.php` — removed grades route, added dismiss-welcome, added 7 new teacher routes
- `database/migrations/2026_08_14_105549_update_assessment_type_to_new_categories.php` — new migration
- `database/seeders/GradesAssessmentsSeeder.php` — rewritten: new assessment types, bulk upsert with chunking
- `database/seeders/DatabaseSeeder.php` — rewritten with correct execution order (Settings first)
- `database/seeders/FeeSchedulesSeeder.php` — minor adjustments
- `database/seeders/SystemRolesAndStaffSeeder.php` — minor adjustments
- `database/seeders/TeachersClassesSchedulesSeeder.php` — minor adjustments

### Files Created
- `resources/views/portal/partials/force-change-password-modal.blade.php` — floating modal for first-login password change
- `resources/views/portal/teacher/class-list.blade.php` — list of classes with search/filter
- `resources/views/portal/teacher/class-students.blade.php` — master student list for a class
- `resources/views/portal/teacher/grade-assessment.blade.php` — class picker + student list with scores
- `resources/views/portal/teacher/grade-assessment-student.blade.php` — individual student score entry (4 categories, 3 rows each)
- `resources/views/portal/teacher/computed-grades.blade.php` — computed grades table with batch save
- `database/seeders/SettingsSeeder.php` — seeds school settings (school_name, school_year, etc.)
- `database/seeders/SubjectsAndSectionsSeeder.php` — K-12 + SHS subjects, sections per grade level
- `database/migrations/2026_08_14_084431_add_has_seen_welcome_to_users_table.php` — adds has_seen_welcome boolean
- `database/migrations/2026_08_14_105549_update_assessment_type_to_new_categories.php` — enum → varchar(30), new types
- `database/migrations/2026_08_14_115224_add_grading_period_to_assessments_table.php` — adds grading_period column + unique constraint
- `database/migrations/2026_08_14_115251_add_unique_constraint_to_grades_table.php` — adds unique constraint to grades
- `database/migrations/2026_08_14_120000_switch_requirements_to_bytea.php` — BYTEA storage for requirements
- `resources/views/portal/student/ledger.blade.php` — Statement of Account page
- `resources/views/portal/student/schedule.blade.php` — Class Schedule page
- `1Dcoument&OtherInfo/laravel_academic_seeders.md` — rewritten with complete seeder documentation
- `1Dcoument&OtherInfo/sessions.md` — session log

### Files Deleted
- `resources/views/portal/student/grades.blade.php` — redundant with report card
- `database/seeders/BooksAndLibrarySeeder.php` — duplicate of user's LibraryAndClinicSeeder
- `database/seeders/AnnouncementsSeeder.php` — duplicate of user's AnnouncementsTableSeeder
- `database/seeders/StudentsSeeder.php` — duplicate of user's StudentsAndFeesSeeder
- `database/seeders/StudentLedgersSeeder.php` — duplicate of user's StudentsAndFeesSeeder
- `app/Services/SupabaseStorage.php` — removed (using BYTEA)
- `CHANGELOG.md` — deleted from project root

---

## Session: Aug 20, 2026

### Completed (this session)
- **Grading weights updated to 20/20/20/40** — Written Work 20%, Quiz 20%, Seatwork 20%, Exam 40% (was 25/25/25/25). Changed in: `TeacherController.php`, `computed-grades-results.blade.php`, `GradesAssessmentsSeeder.php`, and all capstone doc references
- **Duplicate Dashboard button removed from teacher sidebar** — layout already provides a generic Dashboard link; removed the duplicate from `sidebar-teacher.blade.php`; added active state highlighting to layout's Dashboard link via `request()->routeIs('*.dashboard')`
- **Duplicate force-change-password page removed** — deleted standalone `GET /force-change-password` route from `routes/auth.php` and `show()` method from `ForceChangePasswordController.php`; modal remains the sole mechanism
- **Admission step 4 green checkmark fixed** — `isStepComplete()` in `admission-apply.blade.php` now returns `false` for empty required fields arrays instead of `true`
- **Teacher management moved from Directress to Admin** — removed teacher CRUD routes, controller methods, sidebar link, and 4 view files from Directress; Admin `UserController.php` now creates/syncs Teacher model records on teacher account creation/update; `User.php` gets `teacher()` hasOne relationship; Directress dashboard simplified
- **Capstone document Section IX fully rewritten** — all 21 modules + system-wide constraints updated with cleaned/standardized text matching actual codebase implementation; grading weights corrected throughout
- **Broken route names fixed (500 errors)** — `librarian.books.index` → `librarian.books` in librarian dashboard; `nurse.consultations.create` → `nurse.logs.create` in nurse dashboard
- **All changes pushed to GitHub** — 7 commits separated by feature/fixes/docs

### Git Commits (Aug 20)
1. `fix: remove duplicate Dashboard button in teacher sidebar`
2. `fix: remove duplicate force-change-password page`
3. `fix: step 4 green checkmark and form validation`
4. `feat: move teacher management from Directress to Admin`
5. `feat: update grading weights to 20/20/20/40`
6. `docs: update capstone Requirements Analysis with corrected module descriptions and 20/20/20/40 weights`
7. `fix: broken route names in librarian and nurse dashboards causing 500 errors`

### Files Modified (Aug 20)
- `resources/views/portal/partials/sidebar-teacher.blade.php` — removed duplicate Dashboard link
- `resources/views/portal/layouts/app.blade.php` — added active state to generic Dashboard link
- `app/Http/Controllers/Auth/ForceChangePasswordController.php` — removed `show()` method
- `routes/auth.php` — removed `GET /force-change-password` route
- `resources/views/portal/student/admission-apply.blade.php` — fixed `isStepComplete()` empty array check
- `app/Http/Controllers/Portal/DirectressController.php` — removed teacher CRUD methods
- `app/Http/Controllers/Admin/UserController.php` — added Teacher model creation/sync on teacher account create/update
- `app/Models/User.php` — added `teacher()` hasOne relationship
- `resources/views/portal/partials/sidebar-directress.blade.php` — removed Teachers link
- `resources/views/portal/directress/partials/dashboard-results.blade.php` — removed teacher stats, simplified to 2-col grid
- `routes/web.php` — removed 6 directress teacher routes
- `app/Http/Controllers/Portal/TeacherController.php` — grading weights 0.25 → 0.20/0.40
- `resources/views/portal/teacher/partials/computed-grades-results.blade.php` — weight labels 25% → 20%/40%
- `resources/views/portal/librarian/dashboard.blade.php` — fixed `librarian.books.index` → `librarian.books`
- `resources/views/portal/nurse/dashboard.blade.php` — fixed `nurse.consultations.create` → `nurse.logs.create`
- `1Dcoument&OtherInfo/capstone_master_document.md` — Section VI dashboards updated, Section IX fully rewritten

### Files Deleted (Aug 20)
- `resources/views/portal/directress/teachers/index.blade.php`
- `resources/views/portal/directress/teachers/create.blade.php`
- `resources/views/portal/directress/teachers/edit.blade.php`
- `resources/views/portal/directress/partials/teachers-results.blade.php`

### Next (Executed Aug 20, pushed)
- Rename **Onboarding → Verification** — sidebar, pending-accounts page, dashboard cards ✅
- Promotion: show per-student **GWA / grades + qualified/not-qualified** badge on `admin/promotion` table ✅
- Promotion workflow: cleaner flow (transfer, dropout, retain, graduate, promote) — `promotion_workflow_proposal.md` drafted, then implemented (5 actions + reason) ✅
- Admin settings: actual technical settings — `admin_settings_proposal.md` drafted, then implemented (school identity, academic, library, system) ✅
- Scheduling: **hybrid CSV** — manual kept + optional CSV import with preview + conflict check for Principal (`principal/schedules/template` + `import`) ✅

### Git Commits (Aug 20 — after first push)
8. `refactor: rename Onboarding to Verification across admin UI`
9. `feat: promotion table shows GWA + qualification + Dropped Out with reason`
10. `feat: expand admin settings to cover school identity, academic, library and system config`
11. `feat: hybrid CSV import for principal schedules (manual kept, CSV optional with conflict check)`
12. `docs: add promotion workflow and admin settings proposals`

### Next — Polish (Approved Aug 20, MDS updated before execution)
Per `1Dcoument&OtherInfo/polish_suggestions.md` — Quick wins (<30m) + Small features (30-90m) to be executed now.
Updates to be made to: `StudentAdmissionController` (enrollment_open gate), `PromotionController` + `ReportCardController` (passing_grade), `SubjectController` (hybrid CSV like schedules), `portal/admin/partials/promotion-index-results.blade.php` (filter chips + grade modal), `portal/librarian/loans` (overdue badge), `portal/admin/audit-logs` link, `ExportController` (filename row count), plus favicon/title and docs ERD sync.

### Executed Polish (Aug 20 late — pushed)
- `feat: polish - enrollment gate, passing grade config, promotion filters + grade breakdown` — admission-closed gate (`enrollment_open`), passing_grade wired to report cards/promotion/exports, promotion filter chips + per-row grade modal
- `feat: polish - subjects CSV hybrid, export row counts, loans overdue badge, audit link` — `admin/subjects/template+import` hybrid CSV, export filenames include row counts, loans overdue `Overdue • Xd`, promotion audit link
- `docs: refresh ERD + schema map` — `capstone_master_document.md` VII: settings KV, enrollment statuses, 20/20/20/40 weights; seeder demo: 2 failing + 1 no-grades for promotion demo
- `docs: update sessions log to mark polish execution complete and MDS verified`
- All MDS verified: `polish_suggestions.md` items checked, capstone VII done, no broken routes, no log errors, working tree clean

### Next — Password Overlay + Dark/Light Consistency (Approved, MDS updated before execution)
- Password prompt: convert to true AJAX overlay (no page reload) — `ForceChangePasswordController` returns JSON, modal `fetch` with inline errors, success hides overlay
- UI dark/light: audit all portal views/layouts for readable colors/fonts in both modes — `portal/layouts/app.blade.php` global input/placeholder/border fixes, modal/card/button contrast

### Executed — Password Overlay + Dark/Light (Aug 20)
- `ForceChangePasswordController.php:13` — now handles JSON (`expectsJson`/`ajax` returns `{"success":true}`), uses explicit `Password::min(8)->letters()->mixedCase()->numbers()->symbols()`, plain assignment (hashed cast), `session()->regenerate()`
- `force-change-password-modal.blade.php` — fully AJAX overlay (`forcePasswordModal()`), no reload, inline `errors[]` + `successMsg`, spinner, dark-aware inputs (`dark:bg-[#23274C] dark:text-[#E8EAF6]`), error box with bag fallback
- `portal/layouts/app.blade.php` — global dark input/select/textarea (`bg #23274C`, `text #E8EAF6`, `border #3B4172`, placeholder `#6A7094`), welcome banners forced to `bg #1A1E3B` in dark, modal backdrop `rgba(14,17,36,0.55)`

### Next — Tutorial Floating Modal (Approved, MDS updated before execution)
- Tutorial prompt: convert inline welcome cards (9 dashboards) to floating modal like password prompt — `portal/partials/tutorial-modal.blade.php` as overlay (backdrop blur, dark/light consistent), role-specific content, dismiss via `POST /dismiss-welcome` without reload, shown only after password set (`first_login_at` && `!has_seen_welcome`) so it doesn't compete with password modal

### Executed — Tutorial Floating Modal (Aug 20)
- Created `tutorial-modal.blade.php` as floating overlay (same style as password modal) with role-specific title/desc/link, `tutorialModal()` Alpine, `show` with `x-transition`, dark-aware (`dark:bg-[#1A1E3B]`), dismiss via fetch
- Included in `portal/layouts/app.blade.php` after password modal
- Removed inline `@if(!has_seen_welcome)` cards from 9 dashboards (admin, registrar, cashier, teacher, librarian, nurse, student, directress, principal)

### Login 500/419 Check (Aug 20)
- Checked `laravel.log` — no recent ERROR/Exception entries
- Verified auth routes: `login` (GET/POST), `dismiss-welcome` (POST), `password.force.update` (PUT) all present via `route:list`
- View cache compiled successfully — no blade 500s
- Password overlay now AJAX (no reload) and dark/light inputs verified readable in both modes (global `dark input/select/textarea` + modal `dark:bg/#23274C` + welcome banner `dark:bg #1A1E3B`)

### Next — Scheduling Review + Settings One-Tab + Tutorial Once + Student 500 (Approved, MDS updated before execution)
- Scheduling: review Principal module (manual + hybrid CSV) — put in `scheduling_review.md` (strengths, gaps, recommendations)
- Settings: collapse Admin Settings collapsible (Subjects/Sections/Main Settings) into **ONE tab** (`admin.settings` only)
- Tutorial: fix floating modal to show **only on dashboard once per login** (not on every transaction) — add `request()->routeIs('*.dashboard')` + session guard, fix dismiss persistence
- Student dashboard 500 for `lorene.valencia2` (user has no `student` record) — `StudentController@index` null check

### Executed — Scheduling Review + Settings One-Tab + Tutorial + Student 500 (Aug 20)
- `scheduling_review.md` created (current impl, strengths, gaps: teacher/room conflict, time format, UX, recommendations)
- `sidebar-admin.blade.php` — collapsed Settings to single link (ONE tab) — removed Subjects/Sections from dropdown, moved them to top-level if needed (now single Settings)
- `tutorial-modal.blade.php` + `portal/layouts/app.blade.php` — added `*.dashboard` route guard + session `tutorial_dismissed` check, dismiss now sets session + DB
- `StudentController.php:11` — added null `student` guard (lorene.valencia2 case) to prevent 500 on `student/dashboard`

### Next — Student Accounts Audit + Scheduling Recommendations (Approved, MDS updated before execution)
- Student accounts: audit `role_id=7` users vs `students`/`enrollments`/`ledgers` in seeder + live DB (found 3 users missing student, 4 students no enrollment, 21 no ledger) — backfill via repair script + update `StudentsAndFeesSeeder` to ensure idempotent `updateOrCreate` for all necessary info
- Scheduling: implement `scheduling_review.md` recs 1+2 — teacher/room conflict checks + `date_format:H:i` strict time in `schedulesStore` + `schedulesImport`, plus template helper note

### Executed — Student Accounts + Scheduling Recs (Aug 20)
- `check_students2.php` audit run (37 users, 3 missing student, 4 no enrollment, 21 no ledger, 0 no grades among active 30) — repaired via `repair_students.php` + `StudentsAndFeesSeeder` patch (ensure `updateOrCreate` for student/enrollment/ledger + `Student::generateStudentNumber` + `FeeSchedule` fallback)
- `PrincipalController.php:81` — added teacher conflict + room conflict checks and `date_format:H:i` strict time for manual + CSV import, updated `scheduling_review.md` verdict

---

## Prior Work (Before Aug 14 Session)
- **Inquiry 500 fix** — resolved
- **Admission form fixes** — resolved
- **Supabase Storage removed** — using local storage/BYTEA
- **BYTEA migration** — ran, uncommitted
- **Login password field** — fixed
- **Registrar verify function** — fixed
- **x-data display fix** (admissions-show.blade.php) — moved `@js()` to `<script type="application/json">` + `Alpine.data()` via `alpine:init`
- **Balance ₱0.00 fix** (CashierController) — balance computed from FeeSchedule
- **Case-insensitive search** (CashierController) — `LIKE` → `ILIKE`
- **Discount → percentage buttons** — None/30%/50%/100% preset buttons
- **IT Confirmations → "Onboarding"** — renamed across sidebar, pending-accounts, admin dashboard
- **Onboarding flow** (AdminController) — payment → pending → admin confirms → Cleared
- **Batch confirm for Onboarding** — select all + individual checkboxes
- **Admin sidebar reordered** — collapsible Settings with sub-items
- **Student dashboard simplified** — only stat cards + quick action buttons
- **Class Schedule page** — `/student/schedule`
- **Statement of Account page** — `/student/ledger`
