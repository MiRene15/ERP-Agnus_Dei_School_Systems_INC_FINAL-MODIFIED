# Student Admission Bugs & Navbar Checkmarks Analysis

## Three Issues Reported

1. **Requirements upload missing** -- Where do students upload requirements?
2. **Student submission not working properly** -- Multiple bugs found
3. **Add checkmarks on navbar** -- Show which categories are filled

---

## Issue 1: Requirements Upload

**Status: Fully implemented, but flow may be confusing.**

The upload is NOT on the application form itself. The flow is:

```
Submit Application (6-step form) 
  --> Redirects to Admission Status page
    --> Upload Requirements section appears here (only when status = Pending)
```

The upload form is in `resources/views/portal/student/admission-status.blade.php`. It shows after the student submits their application. Required documents:
- PSA Birth Certificate (required)
- Form 138 / Report Card (required)
- Good Moral Certificate (required)
- ESC Grant Certificate (optional)
- Other (optional)

**No bug here -- the upload exists and works.** The user may not have navigated to the status page after submitting.

---

## Issue 2: Student Submission Bugs

### Bug 2A: Draft Discard Button is Broken (HIGH)

**File:** `resources/views/portal/student/admission-apply.blade.php:35-41`

The "Discard Draft" button sends `_step=0`, but the `saveDraft()` method validates `_step` as `required|integer|min:1|max:6`. Step 0 fails validation, so the draft is **never discarded**.

**Fix:** Add a dedicated `discardDraft` route/method that deletes the draft admission record.

### Bug 2B: `submitAll()` Only Validates Step 6 (HIGH)

**File:** `resources/views/portal/student/admission-apply.blade.php:443-455`

The final submit button only checks required fields on step 6 (Previous School). Steps 1-5 are never validated client-side before submission. Students can submit incomplete applications, causing server-side validation failures with no clear guidance on which step is incomplete.

**Fix:** Validate all 6 steps before allowing final submission.

### Bug 2C: Alpine.js Breaks on Apostrophes (MEDIUM)

**File:** `resources/views/portal/student/admission-apply.blade.php:367-393`

If a student's name contains an apostrophe (e.g., "O'Brien"), the Blade `{{ }}` output breaks the JavaScript:
```javascript
last_name: 'O'Brien',  // SYNTAX ERROR
```
This prevents the entire admission form from loading.

**Fix:** Use `@js()` directive instead of `{{ }}` for JavaScript value interpolation.

### Bug 2D: Dashboard 500 May Persist (MEDIUM)

**File:** `storage/logs/laravel.log:5285`

The `$selectedTerm` undefined variable error on the student dashboard was previously fixed, but the compiled view cache may be stale. If students still see a 500 on their dashboard, running `php artisan view:clear` would resolve it.

### Bug 2E: PostgreSQL Student Number Generation (MEDIUM)

**File:** `app/Models/Student.php:67-70`

The `FOR UPDATE` clause with aggregate functions is not allowed on PostgreSQL. If the production Supabase database still runs old code, the Registrar cannot approve admissions (student number generation fails).

**Status:** Fix exists in code (`pg_adavisory_xact_lock` for pgsql), but may not be deployed to Render.

### Bug 2F: Seeder Invalid `application_type` (LOW)

**File:** `database/seeders/StudentsAndFeesSeeder.php:124`

The seeder inserts `application_type = 'Honor'` which violates the PostgreSQL check constraint. This only affects test data seeding, not production.

---

## Issue 3: Add Checkmarks on Navbar

**File to modify:** `resources/views/portal/partials/sidebar-student.blade.php`

The sidebar already has access to `$student`, `$pendingAdmission`, and `$draftAdmission`. Add checkmark icons next to each admission step showing completion status:

| Step | Checkmark When |
|------|---------------|
| Application Details | `application_type` is set |
| Personal Information | `first_name`, `last_name`, `date_of_birth` are filled |
| Address | `permanent_address` is filled |
| Family Background | `father_name` or `mother_name` is filled |
| Emergency Contact | `emergency_contact_name` and `number` are filled |
| Previous School | `previous_school` is filled |

Use the same checkmark pattern already used in `admission-status.blade.php`:
```html
<svg width="16" height="16" viewBox="0 0 16 16" fill="#27ae60">
    <path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/>
</svg>
```

---

## Files to Modify

| File | Change |
|------|--------|
| `admission-apply.blade.php` | Fix discard button, fix submitAll validation, fix apostrophe escaping |
| `StudentAdmissionController.php` | Add `discardDraft()` method |
| `routes/web.php` | Add discard draft route |
| `sidebar-student.blade.php` | Add checkmark icons for completed categories |
| `portal/layouts/app.blade.php` | Add CSS for checkmark icons |

---

## Awaiting Signal to Execute Fixes
