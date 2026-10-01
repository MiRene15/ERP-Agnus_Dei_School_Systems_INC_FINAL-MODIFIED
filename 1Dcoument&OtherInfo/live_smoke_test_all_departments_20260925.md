# Live System Test — All Departments — 2026-09-25 (Session 54)

**Goal:** Live-test every department (public site, auth, Admin, Registrar, Cashier, Teacher, Librarian, Nurse, Student, Directress, Principal, API) against the **live Supabase DB** to confirm everything works as intended.

**Method:** full HTTP smoke test — a real local server (`php artisan serve`, same live DB as production) driven by a scripted browser-equivalent client:

1. Real login per role (`POST /login` with seeded credentials, CSRF token from the form, session cookie jar).
2. Every portal GET route per role (detail pages with real record ids from the DB).
3. AJAX list endpoints (`?ajax=1`) with filter params.
4. CSV/export endpoints.
5. Write flows (create → read → update → delete) with full cleanup afterwards.
6. API (`/api/*`) with a real Sanctum token (issued via `POST /api/auth/token`, revoked after).
7. Assertions per request: HTTP status as expected, body free of error markers
   (`SQLSTATE`, `Whoops`, `Server Error`, `TypeError`, `Class "..." not found`, `Undefined`, `allowed memory size`).

**Harness:** `scripts/live_smoke/` (`dump_accounts.php` + `run_smoke.php`), committed so the run is repeatable.

## Test matrix

| # | Department | Login | Routes covered | Writes (cleaned up) |
|---|-----------|-------|----------------|---------------------|
| 0 | Public site | — | `/`, 11 promo pages, `/login`, `/register`, `/forgot-password`, `/inquiry` | 1 inquiry row (deleted after) |
| 1 | Admin (1) | admin@agnusdei.local | dashboard, pending-accounts, users CRUD pages, student-accounts, subjects, settings, audit-logs, 3 exports, promotion | settings save (same values) |
| 2 | Registrar (2) | registrar@agnusdei.local | dashboard, admissions list+detail, withdrawals, report-cards list+detail+print, sections CRUD | temp section create → dup-guard check → delete |
| 3 | Cashier (3) | cashier1@agnusdei.local | dashboard, payments, search, payment form, financial, receipt, collections+export, reports, receivables+export, discounts | 1 payment ₱100 → receipt → reverted (ledger restored, payment deleted) |
| 4 | Teacher (4) | 1st teacher w/ classes | dashboard, classes, class detail, assessments, schedule, class-list, students, grade-assessment (list+student), computed-grades | — (grade POSTs tested previously) |
| 5 | Librarian (5) | library@agnusdei.local | dashboard, books CRUD, inactive-logs, loans, borrow/return forms, 3 search endpoints, visits, history | book create→edit→delete; borrow→return→row deleted; 1 visit clocked (deleted) |
| 6 | Nurse (6) | clinic@agnusdei.local | dashboard, logs, logs/create | 1 clinic log (deleted) |
| 7 | Student (7) | enrolled student w/ grades+payments | dashboard, admission apply/status, enrollment apply, withdrawal, report-card, COR, schedule, ledger | — |
| 8 | Directress (8) | directress@agnusdei.local | dashboard, demographics, school-years, fees CRUD pages, graduation-fees + assign/assigned, reports hub (5 tabs + tab params), 6 exports, legacy redirects | — |
| 9 | Principal (9) | principal@agnusdei.local | dashboard, schedules, manage, edit, template, **grades with all grade_level tabs**, announcements CRUD | announcement create→edit→delete; schedule conflict POST (rejected) + valid create→edit→delete |
| 10 | Profile | admin | `/profile` | — |
| 11 | API | token | `/api/me` + 9 role prefixes (26 endpoints incl. the session-53 relation fixes) | token revoked after |

**Regression focus (this batch's fixes):** section-level schedule conflict rejection, grade-tab filter persistence (`grade_level` param on `/principal/schedules` + `/principal/grades`), `schedulesManage` Edit Existing tab data load, `teacherClasses`/`principalSchedules` API relations, library chart render (CSS bar), duplicate-guard on sections.

## Results

**Final: 219 / 219 tests PASS** — run 2026-09-29 against the live Supabase DB via local `php artisan serve` (same DB as production). 4 iterative rounds until green:

| Round | Result | What it exposed |
|-------|--------|-----------------|
| 1 | 196 / 211 (15 fail) | 7 real app bugs + 8 harness/data issues |
| 2 | 208 / 217 (9 fail) | stale IDs (dump ran before cleanup) + harness URL/locate bugs |
| 3 | 215 / 217 (2 fail) | locate regex vs JSON-escaped hrefs (`\/sections\/33\/edit`) |
| 4 | **219 / 219 PASS** | — |

### Real bugs found and fixed (7)

1. **`/librarian/visits` → 500** — `LibraryVisit` had no `librarian()` relation → added `belongsTo(User, 'librarian_id')` (`app/Models/LibraryVisit.php`).
2. **`GET /api/student/grades` → 500** — `studentGrades` eager-loaded `class.subject` (phantom) → `schoolClass.subject` (`ApiController`).
3. **`GET /api/principal/grades` → 500** — same phantom `class.*` relations → `schoolClass.subject/teacher` + `enrollment.student` (`ApiController`).
4. **Cashier payments silently failing** — `processPayment` read `$data['ar_number']` unguarded → "Undefined array key" 500 whenever the field was empty → now `($data['ar_number'] ?? null) ?: …` (`CashierController` ~L283).
5. **Admin collections export timeout** — died at 30 s on Supabase (no results) → `set_time_limit(120)` in `collections()` + `streamCsv()` (`ExportController`). Export now completes in ~62–67 s.
6. **Public inquiry lost when mail fails** — `Mail::send` (Resend; SSL failure from this host) ran inside the DB transaction → rolled back the whole inquiry → moved after commit with try/catch + `Log::warning` (`InquiryController::store`).
7. **Section duplicate error invisible** — create/edit/index blades had no error flash → added `session('error')` + `$errors->any()` blocks (`resources/views/portal/registrar/sections/*`). Duplicate-section rejection now visibly reports the clash.

### Harness issues found and fixed (test-tooling only)

- Redirect-guard routes (`/student/admission|enrollment/apply`) assert designed 302s; requirement views skipped (0/45 seeded rows have `file_content` — data gap, noted below).
- Row locators read `?ajax=1` / JSON search endpoints (`sections`, `announcements`, `books`, `loans`, API schedule paging) instead of raw HTML (rows only render via ajax).
- `extractIdNear` unescapes JSON `\/` hrefs + widened search window; loan-return URL corrected to `/librarian/loans/{id}/return`.
- Report-card key from `$acc['student']['enrollment_id']`.
- Mandatory run order: `cleanup.php` → `dump_accounts.php` → `run_smoke.php` (fresh IDs snapshot).

### Coverage & assertions (all green)

Public site (15 pages + inquiry write), 9 role logins + role-redirects, Admin (16 pages + 3 exports), Registrar (13 pages + report-card print + sections create/dup-guard/patch/delete), Cashier (12 pages + receipt + ₱100 payment → receipt `RCP-20260929-0001` → reverted), Teacher (14 pages incl. `?ajax=1`), Librarian (15 pages + book CRUD + borrow/return cycle + visits fix), Nurse (4 pages + clinic-log write), Student (9 pages), Directress (26 pages incl. 5 report tabs + 6 exports + legacy redirects), Principal (28 pages incl. all 7 grade tabs + 3 conflict rejections + schedule create/patch/delete + announcement CRUD), API (9 Sanctum tokens × ~26 endpoints incl. session-53/54 relation fixes). Every response additionally asserted free of `SQLSTATE` / `Whoops` / `Server Error` / `TypeError` / `Undefined` markers.

### Notes

- **`/register`** is intentionally commented out in `routes/auth.php` (302 by design); `requirements/{id}/view` returns 404 when `file_content` is NULL — **0/45 seeded requirements have content** (seed-data gap, not a code bug; cleanup & harness handle gracefully).
- Admin collections export takes ~62–67 s (Supabase round-trips) — within the raised 120 s limit.
- Manual browser testing ran in parallel during the session (1 pre-admission inquiry `ray.ramos@…`, 2 cashier payments ₱1,500) — cleanup only touches `SMOKE`-marked rows and ledger-1 test payments, so live data was preserved (baseline counts: users 195, students 167, payments 205).
- After every round: cleanup verified **"no smoke-test rows remain"** (sections/announcements/schedules/books/loans/clinic logs/inquiries/test payment reverted/9 API tokens deleted; ledger 1 restored from snapshot).

## Later runs (route map changed by the role reform — session-54 record above kept intact)

| Date | Result | What changed |
|------|--------|--------------|
| 2026-09-30 | **231/231 PASS** | Reform Phases 1+2: removed admin routes asserted 404 (`pending-accounts`, `promotion`, `subjects`); subjects/promotion moved to Registrar; 2-step discounts; promotion handoff; grade unlocks; announcement awareness (+12 tests) |
| 2026-10-01 | **235/235 PASS** | Reform Phases 3+4: registrar fee-assignment, cashier refunds, teacher attendance, holds-aware student report-card (fixed harness student carries a real overdue library hold → block + reasons asserted) |
| 2026-10-01 | **237/237 PASS** | Reform follow-ups: principal teacher-assignments + subject-approvals pages |

Route-map deltas vs the session-54 matrix above: Admin lost `pending-accounts`, `subjects/*`, `promotion/*` (IT confirmation, payment confirm, promotion, subject powers removed); `registrar/subjects/*` added (moved from Admin); `registrar/promotion` (propose), `principal/promotion` (approve), `directress/promotion` (sign-off) replaced `admin/promotion`; `discount-requests` (registrar/cashier file, directress approves) + cashier apply-only discounts replaced direct discount edits; `registrar/grade-unlocks` + `teacher/grade-unlocks`; `directress/announcements` (acknowledge); `registrar/fee-assignment`; `cashier/refunds` + payment void; `teacher/classes/{id}/attendance`; `nurse/logs/{id}/close`; `principal/teacher-assignments`; `principal/subject-approvals`; directress one-by-one grad-fee assign removed (404). Per-run results live in `scripts/live_smoke/results.json`.
