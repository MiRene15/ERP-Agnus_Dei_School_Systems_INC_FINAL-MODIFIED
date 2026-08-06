# System Audit — Issues & Improvements

> **Status note (2026-08-06):** Items #1–#4 below were fixed in code. #1–#3 were fixed in the 2026-07-24 critical-fixes batch (Session 13); #4 was fixed during feature work on 2026-08-06. The "Search Bars & Filters" section was implemented in the 2026-07-29 batch (Session 14). Details kept below for the historical record.

## Critical (Will Cause Errors)

### 1. Enrollment model missing `grades()` relationship — FIXED 2026-07-24
**File:** `app/Models/Enrollment.php:36`

`PrincipalController@grades()` does `Enrollment::with('grades')` and `grades.blade.php:44` calls `$enrollment->grades->firstWhere(...)`. The `grades` relationship does not exist on the `Enrollment` model. This will throw a `BadMethodCallException` at runtime.

**Fix (applied):** `Enrollment.php:36-39` now defines `grades()` hasMany → `Grade::class`.

---

### 2. Grade matching uses subject_id instead of class_id — FIXED 2026-07-24
**File:** `resources/views/portal/principal/grades.blade.php:44`
```php
$enrollment->grades->firstWhere('class_id', $subject->id)
```

The `Grade` table uses `class_id` (FK to `classes.id`), but the view iterates over `Subject` models and uses `$subject->id` (which is `subjects.id`). `Subjects` and `Classes` are different tables — a class is an instance of a subject in a specific section/school year. The lookup will never match.

**Fix (applied):** The view now resolves the class through `$enrollment->subjects->firstWhere('subject_id', $subject->id)` and matches grades by `class_id` on that class (see `grades.blade.php:56-57`).

---

## High (Poor UX or Data Issues)

### 3. Missing eager load in Principal schedules — FIXED 2026-07-24
**File:** `app/Http/Controllers/Portal/PrincipalController.php:36`
```php
$classes = Classes::with('subject', 'teacher')->where(...)
```

The `schedules.blade.php:53` accesses `$class->schedules->firstWhere(...)`, but `schedules` is not in the eager load. This causes an N+1 query — one extra query per class rendered.

**Fix (applied):** `schedules()` now eager-loads `'subject', 'teacher', 'schedules'`.

---

### 4. Graduation fee assign doesn't filter by grade level — FIXED 2026-08-06
**File:** `app/Http/Controllers/Portal/DirectressController.php` — `graduationFeesAssign()` method

The method fetches ALL active enrollments regardless of grade level. For example, when assigning a Grade 12 graduation fee, it shows Grade 7 students in the list. The `assign.blade.php` empty-state message also says "No active enrollments found for this grade level" — contradicting the unfiltered query.

**Fix (applied):** `graduationFeesAssign()` now filters via `whereHas('section', fn($q) => $q->where('grade_level', $graduationFee->grade_level))` (see `DirectressController.php:176-182`).

---

## Moderate

### 5. Admin routes still contain moved functionality
**File:** `routes/web.php:108-145`

The admin `role:1` group still has routes for:
- Schedule Management (`/admin/schedules/*`)
- Fee Schedule Management (`/admin/fees/*`)
- Promotion/End-of-Year (`/admin/promotion/*`)
- Announcements (`/admin/announcements/*`)

The sidebar was stripped (removed links), but admin users can still access these URLs directly. This could lead to accidental edits or confusion.

**Options:**
- **Remove routes** from admin group (clean separation of concerns)
- **Keep as legacy** but add a deprecation notice or permission check
- **Leave as-is** since admin is super-admin (per plan: admin = technical/IT)

---

### 6. UserController includes roles 8 and 9
**File:** `app/Http/Controllers/Admin/UserController.php:37`
```php
$users = User::with('role')->whereNotIn('role_id', [7])->...
```

Directress (8) and Principal (9) accounts appear in the admin's Staff Account list. Admin can edit, toggle status, or reset passwords for these accounts. While admin is IT/technical, this might be a concern if role separation is strict.

**Options:**
- Exclude roles 8 and 9: `whereNotIn('role_id', [7, 8, 9])`
- Keep as-is (IT needs to manage all accounts)
- Add a confirmation dialog when modifying leadership accounts

---

## Low

### 7. No bulk delete for Principal schedules
Principal can add/remove individual schedule slots, but there's no UI to clear all schedules for a class at once. Minor UX issue.

### 8. TeachersSeeder vs Directress UI integration
Teachers created via the Directress UI (CRUD) won't have class or schedule assignments until the admin manually sets them up through the admin schedules/classes management. The two systems work independently.

### 9. Announcement `admin_id` field naming
The `Announcement` model uses `admin_id` as the creator FK, but now Principal can also create announcements. The column name is misleading. Functionally it works since it just stores `auth()->id()`, but future developers might be confused.

---

## Feature Request: Search Bars & Filters

The following pages would benefit from search bars and/or filter controls for easier navigation:

### High Priority

| Page | Current State | Proposed Filters |
|---|---|---|
| **Admin: Staff Accounts** (`admin/users/index`) | Plain list, no search | Search by name/email, filter by role (dropdown), toggle show inactive |
| **Admin: Subjects** | Table list, no search | Search by name/code, filter by grade level |
| **Admin: Sections** | Table list, no search | Search by section name, filter by grade level, filter by adviser |
| **Admin: Schedules** | Table list, no search | Search by subject/teacher, filter by grade level, filter by term |
| **Directress: Teachers** | Already has basic table | Search by name/email, filter by department/status |
| **Directress: Fee Schedules** | Grouped by grade level | Filter by school year, search grade level |
| **Directress: Graduation Fees** | Grouped by grade level | Filter by school year |
| **Principal: Student Grades** | Grade level tabs | Add search by student name, filter by section |
| **Principal: Schedules** | Grade level tabs | Add search by subject/teacher, filter by day |
| **Principal: Announcements** | Paginated table | Search by title, filter by type (announcement/event), filter by published/draft |

### Medium Priority

| Page | Current State | Proposed Filters |
|---|---|---|
| **Registrar: Admissions** | Table list | Search by applicant name/number, filter by status/grade level |
| **Registrar: Withdrawals** | Table list | Search by student name, filter by status |
| **Registrar: Report Cards** | No search | Search by student name, filter by grade level/section |
| **Cashier: Payments** | Student-specific | Search by student name/number, filter by clearance status |
| **Librarian: Book Loans** | Status tabs | Search by student/book title, filter by overdue |
| **Librarian: Books** | Already has search | Add filter by publisher/year/availability |
| **Nurse: Clinic Logs** | Table list | Search by student name, filter by date range/incident type |
| **Student: Admission Status** | Single status view | N/A — single student view |

### Search Bar Implementation Pattern

Each search/filter bar should follow this standard pattern:

```html
<form method="GET" class="mb-4">
    <div class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search..." 
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <select name="filter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All</option>
            ...
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white"
                style="background: var(--navy);">Search</button>
        @if(request()->anyFilled(['search', 'filter']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100">Clear</a>
        @endif
    </div>
</form>
```

The controller must handle the query parameters:
```php
$query = Model::query();
if (request('search')) {
    $query->where(function($q) {
        $q->where('name', 'like', '%'.request('search').'%')
          ->orWhere('email', 'like', '%'.request('search').'%');
    });
}
if (request('filter')) {
    $query->where('field', request('filter'));
}
$results = $query->paginate(20)->withQueryString();
```
