# Role Implementation Plan — Agnus Dei School ERP

## Overview

Currently all "back-office" functions live under `role_id: 1` (Admin). The School Directress and Principal are also assigned role 1. This plan separates them into dedicated roles and redistributes functionality so Admin keeps only **technical/IT** responsibilities.

---

## Current Role Definitions

| ID | Role | Current Scope |
|---|---|---|
| 1 | Admin (Technical) | Everything: users, subjects, sections, schedules, fees, promotion, announcements, settings, IT confirmations, exports |
| 2 | Registrar | Admissions, withdrawals, report cards |
| 3 | Cashier | Payments, ledgers |
| 4 | Teacher | Classes, grades, assessments, schedule |
| 5 | Librarian | Books inventory |
| 6 | Nurse | Clinic logs |
| 7 | Student | Admission, enrollment, grades, COR |

---

## Proposed New Role Definitions

### Admin / IT (role 1) — Technical Only

| Feature | Action |
|---|---|
| Staff Account Management (`/admin/users/*`) | **STAY** — IT manages system accounts |
| Subjects CRUD (`/admin/subjects/*`) | **STAY** — IT manages master data |
| Sections CRUD (`/admin/sections/*`) | **STAY** — IT manages master data |
| School Settings (`/admin/settings`) | **STAY** — active school year, system config |
| IT Confirmations (`/admin/pending-accounts`) | **STAY** — clearance workflow |
| Exports (`/admin/exports/*`) | **STAY** — data export |
| Schedule Management (`/admin/schedules/*`) | **MOVE to Principal** — principal owns scheduling |
| Fee Schedule Management (`/admin/fees/*`) | **MOVE to Directress** — directress owns fees |
| Promotion / End-of-Year (`/admin/promotion/*`) | **MOVE to Registrar** — registrar processes promotions |
| Announcements (`/admin/announcements/*`) | **MOVE to Principal** — principal owns announcements/events |

### School Directress (new role — id: 8)

| Feature | Action |
|---|---|
| Fee Schedule Management | Build new controller + views (moved from Admin) |
| Graduation Fee Management | New feature — see details below |
| Teacher CRUD | New feature — manage teacher profiles, assignments |
| Enrollment / Section Statistics | Dashboard read-only analytics |

### Principal (new role — id: 9)

| Feature | Action |
|---|---|
| Schedule Management (per grade & per teacher) | Build new controller + views (moved from Admin) |
| View Student Grades (read-only) | New feature — filtered by grade level |
| School Event Announcements | Build new controller + views (moved from Admin) |

---

## Library Enhancements (role 5 — stays)

| Change | Details |
|---|---|
| Add `price` column to `books` table | New migration — decimal field |
| Book Edit / Update | Add edit route, controller method, view |
| Book Delete | Add delete route, controller method |
| Book Loan Management UI | Build borrow (student select + book select) and return workflows using existing `library_transactions` table |
| Overdue Tracking | Highlight overdue transactions on dashboard |

---

## Database Changes Needed

### New Migrations

1. **Add roles** — insert `Directress` and `Principal` into `roles` table
2. **Add `price` to books** — `ALTER TABLE books ADD COLUMN price decimal(10,2) nullable`
3. **Graduation fees table** (new):

   ```sql
   graduation_fees
   - id
   - grade_level         varchar(20)      — e.g. Grade 6, Grade 12
   - school_year         varchar(20)
   - graduation_fee      decimal(10,2)
   - other_fees          decimal(10,2)    — toga, etc.
   - created_at
   - updated_at
   ```

   Or optionally: add `graduation_fee` + `other_fees` columns to existing `fee_schedules` table.

4. **(Optional) Graduation fee assignments** — if directress needs to apply fees to specific groups of students:

   ```sql
   student_graduation_fees
   - id
   - student_id          FK → students
   - enrollment_id       FK → enrollments
   - graduation_fee_id   FK → graduation_fees
   - amount              decimal(10,2)
   - paid                boolean default false
   - created_at
   - updated_at
   ```

### Seed Updates

- Update `SystemRolesAndStaffSeeder` to add `Directress` (role 8) and `Principal` (role 9) with default accounts
- Ensure Directress and Principal users are assigned their new role IDs

---

## Route / Middleware Changes

| File | Change |
|---|---|
| `routes/web.php` | Add `Route::middleware(['role:8'])` group for Directress routes |
| `routes/web.php` | Add `Route::middleware(['role:9'])` group for Principal routes |
| `routes/web.php` | Remove the moved routes from `role:1` group |
| `routes/web.php` | Update `/dashboard` match statement to include roles 8 and 9 |
| `routes/web.php` | Add library loan management routes to `role:5` group |

## Controller Changes

| New Controller | Role | Methods |
|---|---|---|
| `Portal\DirectressController` | 8 | `index` (dashboard), `fees` (fee schedule CRUD), `graduationFees`, `teachers` (teacher CRUD) |
| `Portal\PrincipalController` | 9 | `index` (dashboard), `schedules` (schedule builder), `grades` (read-only), `announcements` |
| `Portal\LibrarianController` | 5 | Extend existing: `editBook`, `updateBook`, `destroyBook`, `borrowForm`, `storeBorrow`, `returnForm`, `storeReturn` |

## View / Blade Changes

| File | Change |
|---|---|
| `sidebar-admin.blade.php` | Remove: Schedules, Fees, Promotions, Announcements links |
| New: `sidebar-directress.blade.php` | Links: Dashboard, Fee Schedules, Graduation Fees, Teachers |
| New: `sidebar-principal.blade.php` | Links: Dashboard, Schedules, Student Grades, Announcements/Events |
| `sidebar-librarian.blade.php` | Add: Loans/Borrowing, Return Book links |
| `app.blade.php` (layout) | Update role name mapping for roles 8 and 9 |

## Implementation Order

1. Run new migrations (add roles, book price, graduation fees tables)
2. Update seeder to assign correct roles to Directress & Principal
3. Create `DirectressController` + views + sidebar
4. Create `PrincipalController` + views + sidebar
5. Update `routes/web.php` — add new route groups, remove from admin
6. Update `routes/web.php` — add library loan routes
7. Implement library loan management in `LibrarianController`
8. Update sidebar-admin.blade.php — remove moved links
9. Re-seed / test all roles

## Files That Do NOT Change

- `CheckRole.php` middleware (already supports variable roles)
- `FeeSchedule`, `Book`, `LibraryTransaction` models (minor fillable updates only)
- Auth controllers
- Promotional website routes
