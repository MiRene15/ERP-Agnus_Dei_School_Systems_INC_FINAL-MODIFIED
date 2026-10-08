# Spec: Admissions Queue Search Fix

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — search now matches student.first_name and student.last_name (case-insensitive ilike) alongside existing user name/email/application_number matches; verified by user.

## 1. Why We Need This
The registrar's dashboard shows recent applications with the student's first and last name. But the admissions queue search only looks in the user account's name and email fields — not the student's first and last name. So searching for an application by the name shown on the dashboard returns nothing.

## 2. Who Is Affected
* **Registrar** — only user; finds applications by name, email, or application number.
* Untouched: students, parents, other roles.

## 3. Business Flow: Today vs After
- **As-is**: Dashboard shows an application for "Juan Dela Cruz". Registrar searches "Juan" in admissions queue → no results, because the search looks in the user account name, not the student name.
- **To-be**: Registrar searches "Juan" → the application appears, because the search now matches the student's first and last name.
- **Preserved**: Ordering (pending first, then newest), status filter, grade level filter, school year filter, pagination — all unchanged.
- **Exceptions:** None — search fields only.

## 4. How It Should Work
1. Registrar opens the admissions queue.
2. Types a student's first name, last name, or full name in the search box.
3. The list shows matching applications, ordered pending-first then newest.

## 5. Look & Feel (UX)
- Same search box, same list, same filters — only what the search matches changes.
- Key states: all unchanged.
- Plain words: search matches the name as shown on the dashboard.

## 6. Business Rules
### Must always be true
- Search matches `student.first_name` and `student.last_name` (case-insensitive).
- Search still matches `student.user.name`, `student.user.email`, and `application_number`.
- Ordering stays: pending first, then newest by submission date.
- Status, grade level, and school year filters work independently and combined.

### Must never happen
- Search must never return fewer results than before for the same query.
- Ordering, filters, pagination, and the dashboard must not change.

### Edge cases and what happens then
- Student with no user record → still found by student name.
- Student with no first/last name → not found by name (but found by application number or email).
- Saved page from before → refresh shows the same list; nothing to reconcile.

## 7. Out of Scope
- Ordering changes (already correct for pending-first-then-newest).
- Filter changes, dashboard changes, other roles.

## 8. Success Checks
- [ ] Searching by student first name finds the application.
- [ ] Searching by student last name finds the application.
- [ ] Searching by full name finds the application.
- [ ] Searching by email or application number still works.
- [ ] Ordering is still pending-first then newest.
- [ ] Status, grade level, and school year filters still work.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screen: registrar admissions queue only.
- Likely areas (from code inspection): `RegistrarAdmissionController::index()` search block (~lines 27-35) — add `orWhereHas('student', ...)` with `first_name` and `last_name` `ilike` matches; no other changes.
- Data/records touched: none.
- Roles/permissions involved: registrar only; no permission change.

## 11. Approval
> Approved by user on 2026-10-08.
