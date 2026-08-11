# Change Requests — Agnus Dei School ERP

Logged: 2026-08-06. Four requested changes/adjustments, each with current state, problem, and proposed implementation.

---

## Implementation Log

**Status: Items 1–33 DONE** — 2026-08-06 (updated 2026-08-11)

| # | Request | Status | Files Modified |
|---|---------|--------|----------------|
| 1 | Cashier dashboard vs. process payments split | ✅ Done | `CashierController.php`, `dashboard.blade.php`, `payments.blade.php` (new), `sidebar-cashier.blade.php`, `web.php` |
| 2 | Deactivate/archive books in Library Holdings | ✅ Done | `books.blade.php`, `web.php` (no model changes needed) |
| 3 | Librarian type-ahead student search (name + LRN + student no.) | ✅ Done | `LibrarianController.php`, `borrow.blade.php`, `web.php` |
| 4 | AJAX + skeleton loading across views | ✅ Done | `books.blade.php`, `loans.blade.php`, `payments.blade.php`, `borrow.blade.php`, `LibrarianController.php`, `web.php` |
| 5 | AJAX + skeleton loading **everywhere** (all roles) | ✅ Done | `resources/js/app.js`, all portal controllers + views + partials (see section 5) |
| 6 | Librarian module cleanup + overdue pricing | ✅ Done | `sidebar-librarian.blade.php`, `books.blade.php`, `return-form.blade.php`, `loans.blade.php`, `inactive-logs.blade.php`, `web.php`, `LibrarianController.php` |
| 7 | Cashier/Librarian/Registrar multi-module improvements | ✅ Done | (see section 7) |
| 8 | Database seeders — fix bugs + ensure realistic interconnected data | ✅ Done | (see section 8) |
| 9 | UI polish + collapsible filters + migration check | ✅ Done | (see section 9) |
| 10 | Cashier sidebar cleanup + COR fee display + admin audit logs | ✅ Done | (see section 10) |
| 11 | Student seeders: fill all student info (randomized) | ✅ Done | `StudentsAndFeesSeeder.php` |
| 12 | Student dashboard: stats + buttons only | ✅ Done | `student/dashboard.blade.php`, `StudentController.php` |
| 13 | Student grades: per-term filter | ✅ Done | `student/dashboard.blade.php`, `StudentController.php` |
| 14 | Student class schedule: column layout (Time \| Subject-Teacher \| Mon–Sat) | ✅ Done | `student/dashboard.blade.php` |
| 15 | Student application status: hide when fully enrolled | ✅ Done | `sidebar-student.blade.php` |
| 16 | COR/report card: verify and fix | ✅ Done | `student/cor.blade.php`, `student/report-card.blade.php`, `ReportCardController.php` |
| 17 | x-collapse fix: replace with x-show + CSS transitions | ✅ Done | `books.blade.php`, `audit-logs.blade.php`, `subjects-index-results.blade.php`, `sidebar-librarian.blade.php` |
| 18 | Cashier receipt printing per payment | ✅ Done | `CashierController.php`, `receipt-print.blade.php` (new), `student-financial.blade.php`, `web.php` |
| 19 | Cashier auto-discount from admission data | ✅ Done | `CashierController.php`, `payment.blade.php`, `StudentsAndFeesSeeder.php` |
| 20 | Bug fixes: print receipt undefined var, collections report AJAX, report card Section import | ✅ Done | `CashierController.php`, `ReportCardController.php` |
| 21 | Critical: scripts after @endsection silently dropped (6 files) | ✅ Done | `payments.blade.php`, `borrow.blade.php`, `loans.blade.php`, `books.blade.php`, `visits.blade.php`, `student/dashboard.blade.php` |
| 22 | "Upload" label → "View" on receipt link | ✅ Done | `student-financial.blade.php` |
| 23 | phpMyAdmin config — pma controluser not found | ✅ Done | `D:\xampp\phpMyAdmin\config.inc.php` |
| 24 | Book catalog: display serial number | ✅ Done | `librarian/books.blade.php` |
| 25 | Cashier payments search: fix missing LRN, grade level, balance | ✅ Done | `CashierController.php` (`searchStudents()`) |
| 26 | Report card print: remove remarks and signatures | ✅ Done | `report-cards/print.blade.php`, `report-cards/show.blade.php` |
| 27 | Fee structure: K-10 yearly, SHS per term | ✅ Done | `cor.blade.php`, `payment.blade.php`, `student-financial.blade.php` |
| 28 | Fee assessment: move from report card to COR only | ✅ Done | `report-cards/show.blade.php`, `report-cards/print.blade.php` |
| 29 | Switch database from MySQL to PostgreSQL (Supabase) + Docker/Render prep | ✅ Done | `Dockerfile`, `.dockerignore`, `BackupDatabase.php`, `RegistrarAdmissionController.php`, `2026_06_24_201535...`, `2026_06_25_100000...` |
| 30 | Render HTTPS — TrustProxies middleware for SSL-terminated deployment | ✅ Done | `bootstrap/app.php` |
| 31 | Show password option on the login page | ✅ Done | `auth/login.blade.php` |
| 32 | `first_login_at` must persist to DB on first login | ✅ Done | `User.php` |
| 33 | Student dashboard HTTP 500 on login | ✅ Done | `portal/student/dashboard.blade.php` |

---

## 1. Cashier: Dashboard vs. "Process Payments" must be two separate tabs

### Problem

The global sidebar link **"Dashboard"** and the cashier sidebar's **"Process Payments"** both pointed to `cashier.dashboard` — the same page mixing stats and student search.

### What was implemented

- **Dashboard** (`GET /cashier/dashboard`): pure stats overview — Today's Collection, Receipts Issued Today, Collections Report link, and a Recent Payments table (last 5). No search form.
- **Process Payments** (`GET /cashier/payments`): dedicated student search page with AJAX type-ahead (debounced `.300ms`), skeleton loading during fetch, LRN search support, and results table with "Process Payment" and "Financial View" actions.
- `CashierController@index` simplified to only pass stats data (removed `$students`/`$search`).
- `CashierController@payments` added — handles search + renders the payments view.
- `sidebar-cashier.blade.php` updated: "Process Payments" link now points to `cashier.payments` with a new dollar-bill icon.

**Files changed:**
- `routes/web.php` — added `Route::get('/cashier/payments', ...)`
- `app/Http/Controllers/Portal/CashierController.php` — `index()` simplified, `payments()` added
- `resources/views/portal/cashier/dashboard.blade.php` — rewritten as stats-only with recent payments table
- `resources/views/portal/cashier/payments.blade.php` — **new file** — AJAX search with skeleton loading
- `resources/views/portal/partials/sidebar-cashier.blade.php` — link updated to `cashier.payments`

---

## 2. New Book Loan: librarian types student name + LRN / student number

### Problem

`borrow.blade.php` used a plain `<select>` dropdown populated with every enrolled student — slow to load and hard to scan with many students.

### What was implemented

- Replaced the dropdown with a **text input + hidden `student_id` field** backed by AJAX autocomplete.
- Search matches `first_name`, `last_name`, `student_number`, and `legacy_lrn` (LRN).
- 300ms debounce on keystroke; results appear in a dropdown showing name, student number, and LRN.
- Selected student displayed as a removable chip with name + student number.
- JSON endpoint `GET /librarian/students/search` added to `LibrarianController` and `routes/web.php`.

**Files changed:**
- `routes/web.php` — added `Route::get('/librarian/students/search', ...)`
- `app/Http/Controllers/Portal/LibrarianController.php` — `searchStudents(Request $request)` added
- `resources/views/portal/librarian/borrow.blade.php` — rewritten with Alpine.js AJAX autocomplete

---

## 3. Inactive / archived books — what was missing + fix

### Problem

The backend for deactivating books was ~80% complete (`is_active`, `inactive_reason`, `inactive_at`, `deactivated_by` on `Book`; `deactivateBook()`/`reactivateBook()`/`inactiveBooks()` on controller; routes for deactivate/reactivate/inactive-logs; inactive-logs.blade.php view with Reactivate button). But **the Deactivate button and Active/Inactive filter were missing from the Library Holdings UI** — books could only be hard-deleted.

### What was implemented

- **Active / Inactive / All dropdown** added to `books.blade.php` filter bar, wired to the existing `request('active')` param.
- **Deactivate button** per row (for active books) opens a modal prompting for a reason (`inactive_reason` required), then calls `PATCH /librarian/books/{book}/deactivate`.
- **Reactivate button** shown for inactive books, calls existing `librarian.books.reactivate`.
- **Status column** added to the table: green "Active" / red "Inactive" badge.
- **Row highlighting**: inactive books have a gray background.
- Delete button retained with a stricter confirmation prompt ("Permanently delete? This cannot be undone.") — hard delete still available for books that were never borrowed.
- Modal implemented with Alpine.js, shared event listener pattern.

**Files changed:**
- `resources/views/portal/librarian/books.blade.php` — rewritten as AJAX with deactivate modal, active filter, status column

---

## 4. AJAX JavaScript with skeleton loading

### What was implemented

**Skeleton loading** uses the existing `.skelly` / `.sk-line-*` classes from `layouts/app.blade.php:76-88` (shimmer animation, dark-mode aware).

**AJAX pattern** — Alpine.js `x-data` components with `fetch()`:

| View | Search Endpoint | Skeleton |
|------|----------------|----------|
| `cashier/payments.blade.php` | `GET /cashier/search` (existing) | `.skelly` shimmer lines during fetch |
| `librarian/borrow.blade.php` | `GET /librarian/students/search` (new) | Spinner icon + result dropdown |
| `librarian/books.blade.php` | `GET /librarian/books/search` (new) | `.skelly` shimmer lines during fetch |
| `librarian/loans.blade.php` | `GET /librarian/loans/search` (new) | `.skelly` shimmer lines during fetch |

**New JSON endpoints added:**
- `GET /librarian/students/search` — student autocomplete (name, student_number, legacy_lrn)
- `GET /librarian/books/search` — paginated books with filters (search, publisher, availability, active)
- `GET /librarian/loans/search` — paginated loans with filters (search, overdue, status, date range)

**Pattern:**
```javascript
function componentManager() {
    return {
        items: [],
        loading: true,
        // ... filters, pagination ...
        async performSearch() {
            this.loading = true;
            try {
                const response = await fetch(`/endpoint?${params.toString()}`);
                const data = await response.json();
                this.items = data.data;
            } catch (e) {
                this.items = [];
            } finally {
                this.loading = false;
            }
        }
    }
}
```

Skeleton HTML uses the same `.skelly` classes so dark mode stays consistent.

---

## 5. AJAX + Skeleton Loading Everywhere (all roles)

### Request

After item 4, AJAX + skeleton loading existed on only 4 views (cashier payments, librarian borrow/books/loans). The user asked: **"please update mds of putting ajax to everywhere."** — convert every remaining list/search page across all roles to the AJAX + skeleton pattern.

### Survey (completed 2026-08-08)

All list/search pages still used plain `<form method="GET">` (full page reload) and Laravel `->links()->withQueryString()` pagination. Full inventory:

| Role | Views still on full-reload |
|------|----------------------------|
| Admin | `audit-logs`, `users/index`, `sections/index`, `subjects/index`, `pending-accounts`, `promotion/index` |
| Registrar | `admissions-index`, `withdrawals-index`, `report-cards/index` |
| Cashier | `collections-report`, `discounts`, `student-financial` |
| Teacher | classes/grades/schedule/assessments (detail/action pages — **not** converted) |
| Librarian | `inactive-logs`, `visits` |
| Nurse | `logs` |
| Directress | `teachers/index`, `fees/index`, `graduation-fees/index` |
| Principal | `grades`, `schedules`, `announcements/index` |

Not converted by design (detail/action forms, not search/list): admin `pending-accounts`, `promotion/index`; cashier `student-financial`; teacher views; directress `graduation-fees/index`.

### Implementation pattern (DRY, shared helper)

- **`resources/js/app.js`** — new global Alpine component `ajaxTable(url, initialFilters)`:
  - state `filters`, `loading`, `html`; `reload()` builds `URLSearchParams` from `filters` + appends `ajax=1`, fetches JSON `{ html: '<rendered partial>' }`, swaps into `x-html` container.
  - auto-resets `page` when a real filter changes; `reset()` clears all filters; `handlePaginationClick($event)` intercepts pagination links inside the injected HTML (delegated), sets `filters.page`, re-fetches, scrolls to results.
  - skeleton shown while `loading` via existing `.skelly`/`.sk-card`/`.sk-line-*` classes (dark-mode aware).
- **Controller** (per list method): add `Request $request` param, add `$request->query->remove('ajax');` as the **first line** (before `paginate()`/`withQueryString()`, so pagination links never carry `ajax=1`), and return:
  ```php
  if ($request->boolean('ajax')) {
      return response()->json([
          'html' => view('portal.{role}.partials.{page}-results', compact('...'))->render(),
      ]);
  }
  return view('portal.{role}.{page}', compact('...'));
  ```
- **Partial** `portal/{role}/partials/{page}-results.blade.php` — contains ONLY the results table (rows + empty state + inline action forms) and pagination `<div class="p-4">{{ $collection->links() }}</div>` (plain `links()`, no `withQueryString()`); grouped layouts (report-cards by grade level, directress fees by grade) preserved.
- **View** — one outer `x-data="ajaxTable('{{ route('...') }}', { field: '{{ request('field') }}', ... })"` wraps the filter form AND results; `<form>` → `@submit.prevent="reload()"`; text inputs `x-model="filters.x" @input.debounce.300ms="reload()"`; selects/dates `@change="reload()"`; Clear → `@click="reset()"`; results replaced by 5-row `.skelly` skeleton + `<div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>`.

### Progress — ALL DONE

- Shared helper `ajaxTable` added to `resources/js/app.js`.
- **Librarian:** `inactive-logs` + `visits` converted (also fixed the broken clock-in autocomplete stub in `visits.blade.php` — now wired to `GET /librarian/students/search` showing name + student no. + LRN).
- **Cashier:** `collections-report` (date range filters, no pagination — `->get()`), `discounts` (search filter, `->paginate(20)`).
- **Admin:** `audit-logs` (user_id/event/date range/search, `->paginate(25)`), `users/index` (search/role/status, `->paginate(20)`), `sections/index` (search/grade_level, `->get()`), `subjects/index` (search/grade_level, `->get()`).
- **Registrar:** `admissions-index` (search/status/grade_level), `withdrawals-index` (search/status), `report-cards/index` (search/grade_level, grade-grouped tables preserved).
- **Nurse:** `logs` (search/incident_type/date range).
- **Directress:** `teachers/index` (search/status), `fees/index` (school_year, grade-grouped cards).
- **Principal:** `grades` (search/school_year, grade-level tabs preserved), `schedules` (search/school_year/day), `announcements/index` (search/type/status).

All controllers passed `php -l`. No routes/layout/model changes beyond what is listed above. Not converted by design: detail/action pages (admin pending-accounts/promotion, cashier student-financial, teacher views, directress graduation-fees).

---

## Files changed summary

| File | Change |
|------|--------|
| `routes/web.php` | Added 4 routes: `cashier.payments`, `librarian.students.search`, `librarian.books.search`, `librarian.loans.search` |
| `resources/js/app.js` | Shared `ajaxTable` Alpine component (AJAX + skeleton + delegated pagination) |
| `app/Http/Controllers/Portal/CashierController.php` | Simplified `index()`, added `payments()`, AJAX on `collectionsReport()` + `discounts()` |
| `app/Http/Controllers/Portal/LibrarianController.php` | Added `searchStudents()`, `searchBooks()`, `searchLoans()`; AJAX on `inactiveBooks()` + `visits()` |
| `app/Http/Controllers/Portal/AdminController.php` | AJAX on `auditLogs()` |
| `app/Http/Controllers/Admin/UserController.php` | AJAX on `index()` |
| `app/Http/Controllers/Admin/SectionController.php` | AJAX on `index()` |
| `app/Http/Controllers/Admin/SubjectController.php` | AJAX on `index()` |
| `app/Http/Controllers/Portal/RegistrarAdmissionController.php` | AJAX on admissions list |
| `app/Http/Controllers/Portal/WithdrawalController.php` | AJAX on withdrawals list |
| `app/Http/Controllers/Portal/ReportCardController.php` | AJAX on report-cards list |
| `app/Http/Controllers/Portal/NurseController.php` | AJAX on `logs()` |
| `app/Http/Controllers/Portal/DirectressController.php` | AJAX on `teachers()` + `fees()` |
| `app/Http/Controllers/Portal/PrincipalController.php` | AJAX on `grades()` + `schedules()` + `announcements()` |
| `resources/views/portal/cashier/dashboard.blade.php` | Rewritten: stats only, recent payments table |
| `resources/views/portal/cashier/payments.blade.php` | **New file**: AJAX student search with skeleton loading |
| `resources/views/portal/cashier/collections-report.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/cashier/discounts.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/partials/sidebar-cashier.blade.php` | Updated "Process Payments" link |
| `resources/views/portal/librarian/borrow.blade.php` | Rewritten: AJAX student type-ahead with skeleton loading |
| `resources/views/portal/librarian/books.blade.php` | Rewritten: AJAX search, deactivate modal, active filter, status column |
| `resources/views/portal/librarian/loans.blade.php` | Rewritten: AJAX search with skeleton loading, client-side pagination |
| `resources/views/portal/librarian/inactive-logs.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/librarian/visits.blade.php` | Rewritten: AJAX with skeleton, fixed clock-in autocomplete |
| `resources/views/portal/admin/audit-logs.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/admin/users/index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/admin/sections/index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/admin/subjects/index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/registrar/admissions-index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/registrar/withdrawals-index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/registrar/report-cards/index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/nurse/logs.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/directress/teachers/index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/directress/fees/index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/principal/grades.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/principal/schedules.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/principal/announcements/index.blade.php` | Rewritten: AJAX with skeleton |
| `resources/views/portal/*/partials/*-results.blade.php` | 17 new partials (one per converted view) with table + pagination |

**No model changes** — all existing `is_active`, `inactive_reason`, `inactive_at`, `deactivated_by` fields on `Book` already worked; no SoftDeletes needed.

---

## 1. Cashier: Dashboard vs. "Process Payments" must be two separate tabs

**Current state**

- The global sidebar link **"Dashboard"** (`route('dashboard')`) redirects role 3 to `/cashier/dashboard` (`routes/web.php:92`).
- The cashier sidebar's first link is labeled **"Process Payments"** but it ALSO points to `cashier.dashboard` (`resources/views/portal/partials/sidebar-cashier.blade.php:1-4`).
- Result: "Dashboard" and "Process Payments" are currently the same page — `CashierController@index` → `resources/views/portal/cashier/dashboard.blade.php`, which mixes stats (Today's Collection, Receipts Issued Today, Collections Report) with the student-search/payment-start workflow.

**Problem**

There is no dedicated Process Payments page distinct from the dashboard, so the two tabs look identical.

**Proposed implementation**

- Add a dedicated route + controller method, e.g. `GET /cashier/payments` → `CashierController@payments` → view `portal.cashier.payments`. Move the student search (currently `dashboard.blade.php:51-109`) onto this page.
- Keep `CashierController@index` → `dashboard.blade.php` as a pure stats overview (collection cards + recent payments/activity), no search form.
- Update `sidebar-cashier.blade.php`:
  - "Dashboard" → `route('cashier.dashboard')` (stats overview).
  - "Process Payments" → `route('cashier.payments')` (search student, start payment).
- Keep existing `cashier.payment` (per-student payment form), `cashier.search` (JSON endpoint), `cashier.student-financial`, and `cashier.collections-report` as-is.

**Files to touch**

- `routes/web.php` (cashier group, ~line 148)
- `app/Http/Controllers/Portal/CashierController.php` (`index`, new `payments`)
- `resources/views/portal/cashier/dashboard.blade.php` (strip search section)
- `resources/views/portal/cashier/payments.blade.php` (new view, moved search)
- `resources/views/portal/partials/sidebar-cashier.blade.php`

---

## 2. New Book Loan: librarian types student name + LRN / student number

**Current state**

`resources/views/portal/librarian/borrow.blade.php:26-36` renders a plain `<select>` populated with every student (`LibrarianController@borrowForm` → `Student::with('user')->orderBy('last_name')->get()`). With many students this is slow and hard to scan.

**Problem**

Librarian can't quickly find a student by typing their name or LRN/student number.

**Proposed implementation**

- Replace the dropdown with a **text search input** + hidden `student_id` field, backed by an AJAX autocomplete:
  - Search matches `first_name`, `last_name`, `student_number`, and `legacy_lrn` (LRN field on `app/Models/Student.php:26`).
  - Debounce ~250ms on input, show a results list (name + student no. + LRN), click to select.
  - Show the selected student as a removable chip, keep the hidden `student_id` required for validation.
- Add a JSON route, e.g. `GET /librarian/students/search` → `LibrarianController@searchStudents` (mirrors `CashierController@searchStudents`, `CashierController.php:44-66`).
- `storeBorrow` validation stays `student_id => required|exists:students,id` (`LibrarianController.php:228-234`).
- Apply the same type-to-search pattern to Library Visits clock-in (`visits.blade.php`) for consistency.

**Files to touch**

- `routes/web.php` (librarian group, ~line 184)
- `app/Http/Controllers/Portal/LibrarianController.php` (`borrowForm`, new `searchStudents`)
- `resources/views/portal/librarian/borrow.blade.php` (replace select with autocomplete)

---

## 3. Inactive / archived books — where is it, and what's missing

**Current state (what already exists)**

The feature is ~80% built in the backend but **not exposed in the main book list UI**:

- Model: `Book` has `is_active`, `inactive_reason`, `inactive_at`, `deactivated_by` (`app/Models/Book.php:22-26`).
- Controller: `deactivateBook()`, `reactivateBook()`, `inactiveBooks()` all exist (`LibrarianController.php:127-175`).
- Routes: `librarian.books.deactivate`, `librarian.books.reactivate`, `librarian.inactive-logs` (`routes/web.php:179-181`).
- View: `resources/views/portal/librarian/inactive-logs.blade.php` exists (table of deactivated books with reason, date, who, Reactivate button) and is linked in the sidebar (`sidebar-librarian.blade.php:5-8`).
- Borrow dropdown already excludes inactive books (`LibrarianController.php:221`).

**What's missing (the actual bug)**

- **There is no "Deactivate" button in Library Holdings.** `resources/views/portal/librarian/books.blade.php:72-80` only shows **Edit** and **Delete** per row. The `active` filter (`LibrarianController.php:57-61`, `request('active') === 'inactive'`) has no matching control in the `books.blade.php` search form (lines 26-40).
- "Delete" is currently a **hard delete** (`LibrarianController@destroyBook` → `$book->delete()`, line 120-124). No archived/soft-delete copy exists.

**Proposed implementation**

- Add a **Deactivate** action per row in `books.blade.php` (form → `librarian.books.deactivate` with a required `inactive_reason` prompt). Hide it for already-inactive rows.
- Add an **Active / Inactive / All** dropdown to the `books.blade.php` filter bar wired to the existing `request('active')` param.
- Replace hard delete with **archive** (soft-delete) so records are never lost: add `deleted_at` (soft deletes) or repurpose `is_active=false` + reason as the archive state, and keep audit via `log_activity` (already used at `LibrarianController.php:158,172`).
- Optionally surface an archived count on the librarian dashboard (`dashboard.blade.php`) and a link into Inactive Logs.

**Files to touch**

- `resources/views/portal/librarian/books.blade.php` (Deactivate button + active filter)
- `app/Http/Controllers/Portal/LibrarianController.php` (`destroyBook` → soft/archive)
- `app/Models/Book.php` (SoftDeletes if applicable)
- `resources/views/portal/librarian/inactive-logs.blade.php` (add archived/soft-deleted books)

---

## 4. AJAX JavaScript with skeleton loading

**Current state**

- Alpine.js + axios are already available (`package.json:12,14`), CSRF meta is set (`layouts/app.blade.php:6`).
- The layout already ships skeleton styles `.skelly`, `.sk-card`, `.sk-line-*`, `.sk-title` with a shimmer animation and dark-mode variants (`layouts/app.blade.php:76-88, 154-157`), plus a full-page skeleton on first load (`x-data loading`, lines 434-447).
- Only one AJAX endpoint exists today: `CashierController@searchStudents` (JSON). All list/search pages (cashier dashboard, library holdings, loans, inactive logs) are full-page form GETs.

**Proposed implementation**

- **Cashier Process Payments** (new page from item #1): type-ahead student search via `cashier.search` JSON with debounce + skeleton rows while waiting (`.skelly` shimmering table rows instead of the current full page reload). Show skeleton immediately on keystroke, swap in results, and show an empty-state when no match.
- **Librarian New Book Loan** (item #2): student autocomplete through the new JSON endpoint; skeleton suggestion list while the request is in flight.
- **Library Holdings / Loans / Inactive Logs / Visits**: convert the search + filter forms to AJAX (Alpine `$fetch` or axios GET with `X-CSRF-TOKEN`) and render skeleton rows (`.skelly`) during fetch, replacing the full page re-render. Keep the pagination links server-rendered or upgrade to simple "load next" links.
- Provide reusable Alpine components (e.g. `x-data="ajaxSearch(url, ...)"`) or a small shared JS helper in `resources/js/app.js` so skeleton + fetch logic is DRY across all screens.
- Keep the existing page-load skeleton (already in the layout) and make sure the AJAX skeletons use the same `.skelly` classes so dark mode stays consistent.

**Files to touch**

- `resources/views/portal/cashier/payments.blade.php` (new) and `dashboard.blade.php`
- `resources/views/portal/librarian/borrow.blade.php`, `books.blade.php`, `loans.blade.php`, `inactive-logs.blade.php`, `visits.blade.php`
- `resources/js/app.js` (shared AJAX + skeleton helper)
- `routes/web.php` + `app/Http/Controllers/Portal/LibrarianController.php` (new JSON search route)

---

## 6. Librarian Module Cleanup + Overdue Pricing

### Requests

1. **Deactivate button not visible** — user reports only seeing a Cancel option when trying to deactivate a book. Investigate and fix the modal/event dispatching.
2. **Rename "Library Holdings" → "Catalog"** — sidebar label update.
3. **Inactive Logs as sub-tab under Catalog** — restructure sidebar so Inactive Books appears nested under Catalog, not as a standalone link.
4. **Overdue pricing in return form** — when a book is overdue, show the book price, potential damage fees (Minor ₱50 / Major ₱200 / Lost = book price), and total estimated late fee so the librarian can see the full picture before processing the return.
5. **Remove Library Visits** — delete the Library Visits sidebar link, routes, and views. Keep the controller methods intact (may be needed later) but remove from the UI.
6. **Rename "Book Loans" → "Borrowing & Returns"** — more accurate label for the loan management section.

### What was implemented

- **Deactivate fix**: moved the `deactivateModal()` Alpine component registration into the page via `@push('scripts')` to ensure it's available when the page loads; verified the modal event dispatch (`open-deactivate`) and both buttons (Deactivate + Cancel) render correctly.
- **Sidebar restructured** (`sidebar-librarian.blade.php`):
  - "Library Holdings" → "Catalog" (book icon)
  - "Inactive Logs" → indented sub-link under Catalog with smaller font/padding
  - "Book Loans" → "Borrowing & Returns" (clipboard icon)
  - "Library Visits" removed entirely
- **Return form** (`return-form.blade.php`): added a "Fee Estimate" panel below the overdue indicator showing:
  - Book Price: ₱X.XX (used for lost-book fee)
  - Late Fee: X days × ₱5.00 = ₱X.XX
  - Damage Fees: Minor ₱50.00 / Major ₱200.00 / Lost = book price
  - Total if Returned Lost: book price + late fee
- **Routes**: visits routes kept in `web.php` (controller methods untouched) but removed from sidebar.

**Files changed:**
- `resources/views/portal/partials/sidebar-librarian.blade.php` — restructured, visits removed
- `resources/views/portal/librarian/return-form.blade.php` — fee estimate panel added
- `resources/views/portal/librarian/books.blade.php` — deactivate modal confirmed working, minor JS cleanup

---

## 7. Cashier / Librarian / Registrar Multi-Module Improvements

### 7A. Cashier — Dashboard Recent Payments + Financial View + Auto-Discount + Payment Plan Lock + Fully Paid State

#### Current State
- **Dashboard** (`dashboard.blade.php`): Shows 3 stats cards (Today's Collection, Receipts Issued, Collections Report link) and a "Recent Payments" table (last 5). This is **already working** — no change needed here.
- **Financial View** (`student-financial.blade.php`): Two-column layout — left has student info + fee summary (per-term breakdown, total assessed, discount, total paid, balance); right has payment history table + "Process Payment" button. Currently works but could show more detail (no per-term visual breakdown, no payment plan status clarity).
- **Discounts**: Applied manually by cashier during payment (discount type + amount fields) or via Manage Discounts page. No auto-implementation from registrar/enrollment.
- **Payment Plan**: A simple string field (`full` or `installment`) on `StudentLedger`. Selected during payment, can be changed on every payment. No lock mechanism.
- **Fully Paid State**: No special handling — all options remain visible even when balance = 0.

#### Problems
1. Dashboard already has recent payments — verify it's working correctly (user may be seeing cached/stale version).
2. Financial view needs a clearer payment plan indicator and per-term visual structure.
3. Discounts should auto-apply from enrollment/registration data (scholarship, honor, ESC, sibling) — not require manual cashier input.
4. Payment plan should be set once at first payment and locked — cannot be changed afterward unless student re-enrolls.
5. When fully paid (balance = 0 and payment plan = full), hide payment-related options and show only financial overview.

#### Proposed Changes

**Dashboard** (`dashboard.blade.php`):
- Verify recent payments table renders. If user reports it's missing, check that `$recentPayments` is passed correctly and the table HTML exists.

**Financial View** (`student-financial.blade.php`):
- Add payment plan badge (locked icon if locked, colored badge for full/installment)
- Add per-term visual cards showing: Term name, Tuition, Misc, Paid, Balance
- Show discount details (type + amount) with label
- Add "Fully Paid" green banner when balance = 0
- Conditionally hide "Process Payment" button when fully paid

**Process Payment** (`payment.blade.php`):
- Lock payment plan after first payment: if `$ledger->payment_plan` is set and `$ledger->payments->count() > 0`, show payment plan as read-only text (not dropdown)
- Auto-apply discount during first payment creation: check `$student->scholarship` for ESC/shs tuition waiver, check for honor student status, check for sibling enrollments
- Hide discount fields if discount already applied on ledger

**Auto-Discount Logic** (in `CashierController::processPayment()`):
- On first payment (no existing ledger):
  - If `$student->scholarship === true` and grade 11-12 (SHS): set `discount_type = 'esc'`, `discount_applied = total_tuition` (waive tuition)
  - Check for sibling discount: if another active enrollment exists with same guardian/parent, apply sibling discount
  - These are suggestions — cashier can override before submitting
- Discount fields pre-filled but editable on first payment; hidden on subsequent payments if already set

**Fully Paid State**:
- In `student-financial.blade.php`: if `$ledger->balance <= 0 && $ledger->payment_plan === 'full'`, show "Fully Paid" banner, hide "Process Payment" button
- In sidebar or navigation: no change needed ( Payments search still useful for looking up students)

#### Files to Touch
- `resources/views/portal/cashier/dashboard.blade.php` — verify recent payments table
- `resources/views/portal/cashier/student-financial.blade.php` — payment plan badge, per-term cards, fully-paid state, hide process button when paid
- `resources/views/portal/cashier/payment.blade.php` — lock payment plan after first payment, auto-fill discount, hide discount fields when already set
- `app/Http/Controllers/Portal/CashierController.php` — auto-discount logic in `processPayment()`, pass additional data to views

---

### 7B. Librarian — Collapsible Sub-Tab, Main Catalog Page, Enhanced Search Filters

#### Current State
- **Sidebar** (`sidebar-librarian.blade.php`): 3 links — "Catalog" (books), "Inactive Books" (indented sub-link), "Borrowing & Returns" (loans). The "Inactive Books" sub-link is always visible (not collapsible).
- **Catalog page** (`books.blade.php`): Shows active books by default with 4 filters (search text, publisher, availability, active status). Uses custom `booksManager()` Alpine.js with JSON fetch. Works but filters are limited.
- **Inactive logs** (`inactive-logs.blade.php`): Separate page, uses `ajaxTable` component.

#### Problems
1. Sub-tab (Inactive Books) should be collapsible — only shows when Catalog parent is clicked/expanded.
2. Need a "main catalog" view above inactive books — the current books.blade.php IS the main catalog but the user wants it more prominent.
3. Search filters need more detail and must actually work (current filters work but are limited).

#### Proposed Changes

**Sidebar** (`sidebar-librarian.blade.php`):
- Make "Catalog" a collapsible parent: clicking toggles visibility of child links
- Child links: "All Books" (main catalog, `librarian.books`), "Inactive Books" (`librarian.inactive-logs`)
- Use Alpine.js `x-data="{ open: true }"` with `x-show` for child visibility
- Arrow icon rotates on toggle
- Default state: collapsed (children hidden), expands on click

**Main Catalog** (rename `books.blade.php` title to "Book Catalog"):
- The current `books.blade.php` IS the main catalog — keep it, just enhance filters

**Enhanced Search Filters** (`books.blade.php`):
- Add more filter fields to the search form:
  - **Serial Number** (text input) — separate from general search
  - **Year Published** (range: from/to or dropdown)
  - **Price Range** (min/max)
  - **Category/Genre** if field exists (check Book model)
- Update `searchBooks()` controller method to accept and apply these new filters
- Ensure all filters work end-to-end (test each one)

**Controller Changes** (`LibrarianController::searchBooks()`):
- Add conditions for: `serial_number` (exact or LIKE), `year_published` (>= and <=), `price` (>= and <=)
- Keep existing filters working

#### Files to Touch
- `resources/views/portal/partials/sidebar-librarian.blade.php` — collapsible sub-tab with Alpine.js
- `resources/views/portal/librarian/books.blade.php` — enhanced filter form, updated title
- `app/Http/Controllers/Portal/LibrarianController.php` — `searchBooks()` with new filter parameters

---

### 7C. Registrar — Report Cards Immediate Display + Detailed Filters

#### Current State
- **Report Cards Index** (`report-cards/index.blade.php`): Already displays all active enrollments for current school year immediately (no student-search gate). Uses `ajaxTable` component with 2 filters: text search and grade level dropdown.
- **ReportCardController::index()**: Queries active enrollments for `active_school_year()`, supports `search` and `grade_level` params, groups by grade level, returns HTML partial via AJAX.

#### Problems
1. User reports needing to search a student first — this contradicts the code which loads all students immediately. Possibly a UX issue or the user is confused by the layout.
2. Filters are limited — only text search and grade level. Need more detailed filtering options.

#### Proposed Changes

**Report Cards Index** (`report-cards/index.blade.php`):
- Ensure students display immediately on page load (verify `ajaxTable` init loads data without requiring search input)
- Add more filter fields:
  - **Section** (dropdown, dynamically populated from sections for current school year)
  - **School Year** (dropdown — allow viewing past years if data exists)
  - **Student Status** (Active/All)
  - **Adviser** (dropdown — filter by section adviser)
- Keep existing text search and grade level filters

**ReportCardController::index()**:
- Accept new params: `section_id`, `school_year`, `adviser_id`
- Apply filters to enrollment query
- Pass additional filter options to view (sections list, school years list, advisers list)

**Report Cards Results Partial** (`partials/report-cards-results.blade.php`):
- Verify it renders correctly with all enrollments on initial load

#### Files to Touch
- `resources/views/portal/registrar/report-cards/index.blade.php` — enhanced filter form
- `resources/views/portal/registrar/report-cards/partials/report-cards-results.blade.php` — verify rendering
- `app/Http/Controllers/Portal/ReportCardController.php` — new filter params, pass filter options to view

---

## 8. Database Seeders — Fix Bugs + Ensure Realistic Interconnected Data

### Current State

7 seeders in `database/seeders/`, called by `DatabaseSeeder.php` in this order:

| # | Seeder | Tables | Records |
|---|--------|--------|---------|
| 1 | `SystemRolesAndStaffSeeder` | roles, users, settings | 9 roles, 8 staff users, 2 settings |
| 2 | `TeachersClassesSchedulesSeeder` | users, teachers, sections, subjects, classes, schedules | 20 teachers, ~30 sections, ~130 subjects, hundreds of classes+schedules |
| 3 | `FeeSchedulesSeeder` | fee_schedules | 39 records (13 grades × 3 terms) |
| 4 | `AnnouncementsTableSeeder` | announcements | 4 records |
| 5 | `StudentsAndFeesSeeder` | users, students, admissions, enrollments, enrollment_subject, student_ledgers, payments | 13 students, ~17 payments |
| 6 | `GradesAssessmentsSeeder` | assessments, grades | ~273 assessments, ~91 grades |
| 7 | `LibraryAndClinicSeeder` | books, library_transactions, clinic_logs | 8 books, ~8 transactions, ~6 clinic logs |

### Problems Found

**BUG 1 (CRITICAL): Swapped role IDs in `LibraryAndClinicSeeder`**
- Line 31: `$librarianId = User::where('role_id', 6)` — role_id 6 is **Nurse**, not Librarian (should be 5)
- Line 32: `$nurseId = User::where('role_id', 7)` — role_id 7 is **Student**, not Nurse (should be 6)
- Result: all library transactions reference the Nurse as librarian; all clinic logs reference a Student as nurse

**BUG 2 (Minor): StudentsAndFeesSeeder silently skips students**
- Line 115: `if (!$section) continue;` — if no section exists for a grade, student is skipped with no warning

**BUG 3 (Minor): Potential name collision**
- Teacher "Maria Santos" and Student "Maria Santos" share the same name (emails differ: `.local` vs `.edu.ph`)

**General: No missing tables or broken FK chains** — execution order is correct, all dependencies satisfied.

### Proposed Changes

1. **Fix `LibraryAndClinicSeeder` role IDs**: change `role_id, 6` → `role_id, 5` for librarian; `role_id, 7` → `role_id, 6` for nurse
2. **Add more books**: currently only 8 — expand to ~20+ with varied genres, publishers, and year ranges
3. **Add more library transactions**: currently ~8 — expand to ~15+ with varied statuses (Borrowed, Returned, Overdue) and conditions
4. **Add more clinic logs**: currently ~6 — expand to ~10+ with varied incident types
5. **Add discount data**: some students should have `discount_type` and `discount_applied` on their ledgers (honor, sibling, esc) to test the auto-discount feature from item 7A
6. **Add a second payment for more students**: currently only every 3rd student gets a 2nd payment — add more to show varied payment histories
7. **Verify all FK relationships resolve correctly** after changes
8. **Add comments to seeder files** documenting what each block does and which other seeders it depends on

#### Files to Touch
- `database/seeders/LibraryAndClinicSeeder.php` — fix role IDs, expand data
- `database/seeders/StudentsAndFeesSeeder.php` — add discounts to some ledgers, more payments
- `database/seeders/DatabaseSeeder.php` — no change needed (order is correct)

---

## 9. UI Polish + Collapsible Filters + Migration Check

### Current State
- **Book catalog** (`books.blade.php`): 9 filter inputs across 3 rows (search, serial number, publisher, availability, year from, year to, price min, price max, active status). Dense and overwhelming.
- **Loans** (`loans.blade.php`): `x-data="loansManager()"` declared twice on separate divs — two independent Alpine instances, filter inputs and table are disconnected (bug).
- **Cashier discounts** (`discounts.blade.php`): Modal uses fragile `modal.__x.$data` Alpine internals.
- **Migrations**: All schema changes needed for current features already exist (discount_type, ar_number, receipt_file_path, serial_number, is_active, book deactivation fields, library transaction fee fields). No new migrations needed.

### Problems
1. Book catalog filters are too complex — 9 inputs in a dense grid, not user-friendly.
2. Loans page has a double `x-data` bug — filters and table don't share state.
3. Cashier discount modal uses fragile Alpine internals.

### Proposed Changes

### 9A. Book Catalog — Simplify + Collapsible Filters ✅ Done
- Keep only 2 filters visible by default: **Search** (text) + **Active Status** (select)
- "Advanced Filters" toggle button expands/collapses an advanced filter section
- Advanced section contains: Serial Number, Publisher, Availability, Year From/To, Price Min/Max
- Alpine.js `x-show` with `x-collapse` for smooth animation
- Clean visual separation between basic and advanced filters

**Files changed:**
- `resources/views/portal/librarian/books.blade.php` — collapsible filter layout with `showAdvanced` toggle, `x-collapse`

### 9B. Loans Page — Fix Double Alpine Bug ✅ Done
- Removed duplicate `x-data="loansManager()"` from the filter bar div
- Single `x-data` declaration on parent container wrapping both filters and table
- Filter inputs and table now share the same Alpine scope

**Files changed:**
- `resources/views/portal/librarian/loans.blade.php` — single `x-data="loansManager()"` on parent `<div>`

### 9C. Cashier Discounts — Fix Modal ✅ Done
- Replaced fragile `modal.__x.$data` Alpine internals with proper custom event dispatch
- Modal listens to `@open-discount-modal.window` and reads `$event.detail`
- `openDiscountModal()` function dispatches `CustomEvent('open-discount-modal', { detail: ... })`
- Discount type `<select>` hardcoded (removed dynamic `@foreach($discountTypes)`) for reliability

**Files changed:**
- `resources/views/portal/cashier/discounts.blade.php` — modal refactored with custom event pattern

### 9D. Migration Check ✅ Done
- No new migrations needed — all schema changes for features already exist in current migrations.

---

## 10. Cashier Sidebar Cleanup + COR Fee Display + Admin Audit Logs + Database Sync

### 10A. Cashier — Remove "Manage Discounts" Tab, Rename "Process Payments", Clean Dashboard

**Current state:**
- Sidebar has 3 links: "Process Payments", "Manage Discounts", "Collections Report"
- Dashboard shows 3 stats + Recent Payments table (user wants ONLY stats)

**Changes:**
1. **Remove "Manage Discounts" from sidebar** — discounts are applied internally during payment processing (auto-discount for scholarship/honor/sibling) or manually in the payment form. No separate management page needed.
2. **Rename "Process Payments" → "Payments"** — simpler label
3. **Remove Recent Payments table from dashboard** — keep only the 3 stat cards (Today's Collection, Receipts Issued, Collections Report link)
4. **Keep the discounts route/controller intact** — just remove from sidebar UI. Can be accessed via URL if needed for admin purposes.

**Files:**
- `resources/views/portal/partials/sidebar-cashier.blade.php` — remove Manage Discounts link, rename Process Payments
- `resources/views/portal/cashier/dashboard.blade.php` — remove Recent Payments section
- `app/Http/Controllers/Portal/CashierController.php` — `index()` no longer needs to fetch `$recentPayments`

---

### 10B. COR / Report Card — Show Fee Assessment Breakdown

**Current state:**
- `print.blade.php` (PDF): Shows ONLY grades — no fee information at all
- `show.blade.php`: Shows ONLY grades — no fee information

**Changes:**
- Add a **Fee Assessment** section to the COR/print view showing:
  - Per-term breakdown: Term Name | Tuition Fee | Misc. Fee | Total
  - Total Assessed
  - Discount Applied (if any)
  - Total Paid
  - Balance
- Place this below the grades table, before the signature lines
- Keep it compact for PDF layout (smaller font, tight spacing)
- Also add fee summary to `show.blade.php` (web view)

**New migration needed:**
- None — `fee_schedules` table already has `tuition_fee` and `misc_fee` per term. The COR just needs to display the data that already exists.

**Files:**
- `resources/views/portal/registrar/report-cards/print.blade.php` — add fee assessment table
- `resources/views/portal/registrar/report-cards/show.blade.php` — add fee summary section
- `app/Http/Controllers/Portal/ReportCardController.php` — `show()` and `print()` need to pass fee data (`$feeSchedules`, `$ledger`)

---

### 10C. Admin Audit Logs — Verify Results Table Loads

**Current state:**
- The page HAS both filters AND a results table via AJAX (`ajaxTable` component)
- Results partial exists at `portal.admin.partials.audit-logs-results`
- Controller passes `$logs`, `$events`, `$users` and supports `?ajax=1` JSON responses

**Issue:**
- The user reports "only a filter" — this suggests the AJAX table might not be loading on initial page load
- Possible cause: the `ajaxTable` component might require explicit initialization or the initial load might fail silently

**Changes:**
- Verify the `ajaxTable` component loads data on init (check `init()` method in `app.js`)
- Ensure the initial AJAX call fires when the page loads
- Add a fallback: if AJAX fails, show the server-rendered `$logs` directly
- Test the filter functionality (user/event/date range/search)

**Files:**
- `resources/views/portal/admin/audit-logs.blade.php` — verify AJAX init
- `resources/js/app.js` — check `ajaxTable` init behavior
- `app/Http/Controllers/Portal/AdminController.php` — verify data passing

---

### 10D. Database Sync Check

**Current schema status — all columns exist:**
- `student_ledgers.discount_type` ✅ (migration 2026_08_04_000003)
- `student_ledgers.payment_plan` ✅ (VARCHAR, migration 2026_06_24_201535)
- `payments.ar_number` ✅ (migration 2026_08_06)
- `payments.receipt_file_path` ✅ (migration 2026_08_06)
- `books.serial_number` ✅ (migration 2026_08_06)
- `books.is_active` ✅ (migration 2026_08_06)
- `books.inactive_reason`, `inactive_at`, `deactivated_by` ✅ (migration 2026_08_06)
- `library_transactions.condition_at_borrow`, `condition_at_return`, fees ✅ (migration 2026_08_06)
- `fee_schedules.term` ✅ (migration 2026_06_25_110000)

**No new migrations needed** — all schema changes for current features already exist.

**However**, verify:
- `fee_schedules` has `school_year` column (yes, from original migration)
- `graduation_fees` table exists (yes, from migration 2026_07_28)
- `student_graduation_fees` table exists (yes, from migration 2026_07_28)

---

## 11. Student Seeders — Fill All Information (Randomized)

### Current State
- `StudentsAndFeesSeeder.php` creates 13 students with `first_name`, `last_name`, `grade`, `strand`, `scholarship` only.
- Student model has many fillable fields that are empty: `middle_name`, `place_of_birth`, `citizenship`, `religion`, `current_address`, `legacy_lrn`, `previous_school_address`, `father_occupation`, `mother_occupation`, `emergency_contact_relationship`.

### Changes
- Fill ALL student fields with realistic randomized data (middle names, addresses, occupations, religions, citizenship, LRN, etc.)

**Files:**
- `database/seeders/StudentsAndFeesSeeder.php`

---

## 12. Student Dashboard — Stats + Buttons Only

### Current State
- Dashboard shows: Academic Standing card, Statement of Account card, Class Schedule table, Grades table, Payment History table — all inline on the dashboard.

### Problem
- Too much data on the dashboard. User wants only stats cards and quick-action buttons.

### Changes
- Replace the dense data sections with stat cards (Enrollment Status, Balance, Books Borrowed, etc.) and quick-action buttons (View Grades, View Schedule, View COR, View Report Card, etc.)
- Keep the payment reminder banner and welcome message

**Files:**
- `resources/views/portal/student/dashboard.blade.php`
- `app/Http/Controllers/Portal/StudentController.php` (simplify data passing)

---

## 13. Student Grades — Per-Term Filter

### Current State
- Grades are displayed in a flat table with ALL grading periods (1st Term, 2nd Term, 3rd Term) as columns. No way to filter by term.

### Changes
- Add a term filter dropdown (All Terms, 1st Term, 2nd Term, 3rd Term) above the grades table
- Default to "All Terms" showing the full table
- When a specific term is selected, show only that term's column
- Use Alpine.js for client-side filtering (no AJAX needed — grades are already loaded)

**Files:**
- `resources/views/portal/student/dashboard.blade.php`

---

## 14. Student Class Schedule — Column Layout

### Current State
- Schedule displayed as a flat list: Subject | Teacher | Day | Time | Room (one row per schedule entry)

### Problem
- Hard to visualize the weekly schedule. User wants a grid layout.

### Changes
- Display as a weekly timetable grid:
  ```
  Time | Subject - Teacher | Monday | Tuesday | Wednesday | Thursday | Friday | Saturday
  ```
- Each cell shows the subject name and teacher for that day/time slot
- Group by time slot, spread days across columns
- Use the existing `Schedule` model data (`day_of_week`, `start_time`, `end_time`)

**Files:**
- `resources/views/portal/student/dashboard.blade.php`

---

## 15. Student Application Status — Hide When Fully Enrolled

### Current State
- Sidebar always shows "Application Status" link for all students with a `student_number`.

### Problem
- User wants Application Status hidden once the student is fully enrolled (has an active enrollment).

### Changes
- In `sidebar-student.blade.php`, only show "Application Status" link when `$pendingAdmission` exists OR when there's no `$activeEnrollment`
- Once student has an active enrollment, hide the Application Status link

**Files:**
- `resources/views/portal/partials/sidebar-student.blade.php`

---

## 16. COR / Report Card — Verify and Fix

### Current State
- Student COR (`student/cor.blade.php`) shows: student info, enrolled subjects table, fee assessment summary, signatures.
- Student Report Card (`student/report-card.blade.php`) shows: grades table with all 3 terms.
- Registrar print view (`report-cards/print.blade.php`) has fee assessment table added in item 10B.

### Changes
- Verify COR renders correctly with all data (subjects, schedules, fees)
- Verify student report card renders correctly with grades
- Ensure both views handle missing data gracefully (no blade errors on null)
- Add `@if` guards for optional relationships

**Files:**
- `resources/views/portal/student/cor.blade.php`
- `resources/views/portal/student/report-card.blade.php`
- `app/Http/Controllers/Portal/StudentController.php`

---

## 24. Book Catalog — Display Serial Number

### Problem
Serial number exists in the database but is not displayed in the librarian's catalog table.

### Changes
- Added "Serial No." column after ISBN in `books.blade.php` table header and body
- Shows `book.serial_number` or "—" if empty

**Files:**
- `resources/views/portal/librarian/books.blade.php`

---

## 25. Cashier Payments Search — Fix Missing LRN, Grade Level, Balance

### Problem
The AJAX search endpoint (`searchStudents()`) only returned `id`, `first_name`, `last_name`, `student_number` via `select()`. The blade template accessed `legacy_lrn`, `enrollments`, and `ledger` but they were undefined — always showing "—", "N/A", and ₱0.00.

### Changes
- Removed `select()` restriction to return all student fields
- Added `legacy_lrn` to search WHERE clause (searchable by LRN)
- Added eager loading for `enrollments.section` and `ledger`
- All fields now properly populated in search results

**Files:**
- `app/Http/Controllers/Portal/CashierController.php` (`searchStudents()`)

---

## 26. Report Card Print — Remove Remarks and Signatures

### Problem
Registrar report card PDF had empty remarks textarea and signature blocks that were unnecessary.

### Changes
- Removed remarks textarea from print view
- Removed signature block (Student, Adviser, Registrar, Principal, Directress)
- Kept only the date line at the bottom
- Removed fee assessment section from both print and show views (fee assessment belongs in COR only)

**Files:**
- `resources/views/portal/registrar/report-cards/print.blade.php`
- `resources/views/portal/registrar/report-cards/show.blade.php`

---

## 27. Fee Structure — K-10 Yearly, SHS Per Term

### Problem
All grades displayed fees per-term (3 rows). K-10 should show one yearly total, while SHS (Grade 11-12) should show per-term breakdown.

### Changes
- COR view: K-10 shows single yearly row (sum of 3 terms), SHS shows 3 per-term rows
- Cashier payment view: K-10 shows "Tuition (Full Year)" and "Misc. Fee (Full Year)", SHS shows per-term lines
- Cashier student-financial view: K-10 shows single yearly card, SHS shows per-term cards
- All views use `$isSHS = in_array($grade_level, ['Grade 11', 'Grade 12'])` to determine display format

**Files:**
- `resources/views/portal/student/cor.blade.php`
- `resources/views/portal/cashier/payment.blade.php`
- `resources/views/portal/cashier/student-financial.blade.php`

---

## 28. Fee Assessment — Move from Report Card to COR Only

### Problem
Fee assessment was displayed in both the COR and report card views. User wants it in COR only.

### Changes
- Removed fee assessment section from registrar report card `show.blade.php`
- Removed fee assessment section from registrar report card `print.blade.php`
- Fee assessment remains in student COR view (`cor.blade.php`)
- Report card now shows only grades, student info, and date

**Files:**
- `resources/views/portal/registrar/report-cards/show.blade.php`
- `resources/views/portal/registrar/report-cards/print.blade.php`

---

## 29. Switch Database from MySQL to PostgreSQL (Supabase) + Docker/Render Prep

### Problem
Render has no native PHP runtime, so the app must run via Docker. User chose Supabase (PostgreSQL) as the managed database. Several code paths were MySQL-only and would break on Postgres.

### Changes
- **Dockerfile** — added `libpq-dev` + `postgresql-client`, swapped `pdo_mysql` extension for `pdo_pgsql` + `pgsql`; `postgresql-client` provides `pg_dump` for the backup command.
- **`BackupDatabase.php`** — now driver-aware: MySQL keeps the PDO dump (SHOW CREATE TABLE + batched INSERT); PostgreSQL uses `pg_dump --no-owner --no-privileges --clean --if-exists` with `PGPASSWORD`/`PGSSLMODE` env passed to `exec()`. Shared `prune()` keeps the last 30 backups. Daily 02:00 scheduled job no longer fatals on pg.
- **`RegistrarAdmissionController.php`** — `orderByRaw("FIELD(status, 'Pending') DESC")` (MySQL-only `FIELD()`) replaced with portable `CASE WHEN status = 'Pending' THEN 0 ELSE 1 END`.
- **Migration `2026_06_24_201535`** (payment_plan) — added pgsql branch: `DROP CONSTRAINT IF EXISTS student_ledgers_payment_plan_check`, `ALTER COLUMN ... TYPE VARCHAR(20)`, set default `'installment'` + NOT NULL. MySQL branch unchanged.
- **Migration `2026_06_25_100000`** (assessments type) — added pgsql branch: `DROP/ADD CONSTRAINT assessments_type_check` with the new value set. MySQL branch unchanged.
- **`Student::generateStudentNumber()` + `Admission` boot** — `lockForUpdate()` on an aggregate `count()` is rejected by Postgres (`FOR UPDATE is not allowed with aggregate functions`). On pgsql they now take a transaction-scoped `pg_advisory_xact_lock()` (auto-released at commit) and skip `lockForUpdate`; MySQL path unchanged.
- **New migration `2026_08_08_000000_widen_application_type_in_admissions.php`** — the seeders + cashier auto-discount write `application_type='Honor'/'Sibling'`, but the column's check/ENUM only allowed `New/Old/Transferee`. MySQL non-strict mode silently coerced these to `''`; Postgres rejects with a check violation. The new migration widens allowed values to `New, Old, Transferee, Honor, Sibling` on both engines.
- Verified portable: `CONCAT()` (PromotionController), `DATE()` (ApiController), `CASE` (semester→term migration) all work on both engines. Full grep sweep for `FIELD(|IFNULL|GROUP_CONCAT|DATE_FORMAT|DATEDIFF|UNIX_TIMESTAMP|STR_TO_DATE|IF(` found no other MySQL-only usages. `config/database.php` already had the `pgsql` connection with `DB_SSLMODE`.
- Execution verified on Supabase (PostgreSQL 17.6): local `php.ini` enabled `pdo_pgsql` + `pgsql` (XAMPP has the DLLs); `migrate:fresh --seed` + all 7 seeders complete; 41 migrations, 13 students, 13 enrollments/ledgers/admissions, 19 payments, 20 books, 13 library transactions, 10 clinic logs, 855 assessments, 285 grades, 4 published announcements (2 + 2 events).

**Files:**
- `Dockerfile`, `.dockerignore`
- `app/Console/Commands/BackupDatabase.php`
- `app/Http/Controllers/Portal/RegistrarAdmissionController.php`
- `app/Models/Student.php`, `app/Models/Admission.php`
- `database/migrations/2026_06_24_201535_fix_payment_plan_enum_in_student_ledgers.php`
- `database/migrations/2026_06_25_100000_update_assessment_type_enum.php`
- `database/migrations/2026_08_08_000000_widen_application_type_in_admissions.php`
- `D:\xampp\php\php.ini` and `D:\php\php.ini` (enabled pdo_pgsql/pgsql — both shipped the DLLs; the port-8000 dev server was running `D:\php\php.exe` which lacked the driver, causing "could not find driver"; stale `php artisan serve` processes killed + server restarted)
- `Actual_Website/ERP_Agnus_Dei_School_Systems_INC/.env` (DB block → Supabase pgsql)

### Status
All files pass `php -l`; full app lint sweep OK; app boots (`route:list`) and all 41 migrations instantiate (`migrate:status`). Deploy steps: set `DB_CONNECTION=pgsql` + Supabase credentials + `DB_SSLMODE=require` in Render env, then deploy Docker build (runs `php artisan migrate --force` on start).

---

## 30. Render HTTPS — TrustProxies middleware for SSL-terminated deployment

### Problem

Render terminates SSL at its load balancer and forwards the original request to Laravel over HTTP. Without telling Laravel about the proxy, all generated URLs (form actions, redirects, asset links) use `http://` instead of `https://`. The browser shows "The information you're about to submit is not secure" on the login form.

### What was implemented

Added `trustProxies` configuration in `bootstrap/app.php` middleware callback:
- `at: '*'` — trust all proxy IPs (Render's pool is dynamic)
- Headers forwarded: `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Port`, `X-Forwarded-Proto`

Laravel now reads the `X-Forwarded-Proto: https` header from Render's proxy and generates all URLs with `https://` scheme.

### Files modified

- `bootstrap/app.php` — added `$middleware->trustProxies(at: '*', headers: ...)` inside `withMiddleware` callback

### Render env (final)

```
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-northeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.sjnuvyiwszcjxixunlzi
DB_PASSWORD=Agnus2026!!
DB_SSLMODE=require
```

### Status

Tested on Render deploy — login form submits over HTTPS, no browser security warning. App boots, announcements visible on homepage.

---

## 31. Show password option on the login page

### Problem

The login form had only a `type="password"` field with no way to verify the typed value before submitting.

### What was implemented

Added a "Show password" checkbox + eye/eye-off toggle icon beside the password field in `auth/login.blade.php`. Toggling switches the input between `password` and `text`.

---

## 32. `first_login_at` must persist to DB on first login

### Problem

`first_login_at` stayed NULL even after a user logged in and completed the first-login password change (verified on Supabase: user 29 `first_login_at = NULL`, `last_login_at` populated). Root cause: `last_login_at`/`first_login_at` were missing from `App\Models\User::$fillable`, so `ForceChangePasswordController::update()`'s mass-assignment `update(['first_login_at' => now()])` silently dropped the value.

### What was implemented

Added `last_login_at` and `first_login_at` to `User::$fillable`. Verified: after completing the first-login flow, `first_login_at` is written to the DB.

---

## 33. Student dashboard HTTP 500 on login

### Problem

`POST /login` succeeded → `/force-change-password` → `/student/dashboard` returned HTTP 500:

```
Undefined variable $selectedTerm (View: .../portal/student/dashboard.blade.php)
```

Root cause: the grades table used PHP `@if($selectedTerm === 'all')` / `@php ... $selectedTerm ... @endphp` while `$selectedTerm` is an Alpine.js client-side variable (`x-data="{ selectedTerm: 'all' }"`) — Blade tried to evaluate it server-side and crashed.

### What was implemented

Rewrote the grades table so every term column is server-rendered and Alpine toggles visibility with `x-show="selectedTerm === 'all' || selectedTerm === @js($period)"`. No PHP reference to `$selectedTerm` remains. Verified: full student login flow returns HTTP 200 (reproduced with `juan.delacruz@agnusdei.edu.ph`).

---

1. Item #11 (student seeders) — foundation for testing all student features
2. Item #15 (hide application status when enrolled) — quick sidebar fix
3. Item #12 (student dashboard cleanup) — high impact UI change
4. Item #14 (class schedule column layout) — visual improvement
5. Item #13 (grades per-term filter) — feature enhancement
6. Item #16 (COR/report card verify) — verification pass
