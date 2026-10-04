# Spec: Date Limits

- **Status**: Approved (re-approved 2026-10-04 — filter boxes cap at today)
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04

## 1. Why We Need This
Staff can save impossible dates today: a birth date tomorrow, an incident in 1980, a book return before it was borrowed. These bad dates pollute class lists, clinic and library records, and money reports. One shared guard stops new bad dates everywhere at once.

## 2. Who Is Affected
* **Registrar** — primary. Birth dates drive age checks and class lists; guards keep admissions clean.
* **Teachers** — attendance dates stay today or earlier.
* **Nurse** — incident dates stay within real school history.
* **Librarian** — borrow and return dates stay ordered and real.
* **Cashier, directress, principal, IT admin** — report ranges and lookups behave; no new screens for them.

## 3. Business Flow: Today vs After
- **As-is**: most date boxes accept anything. A birth date of tomorrow, an incident in 1980, or a report starting after it ends all save quietly. Only attendance blocks the future.
- **To-be**:
  1. Staff picks a date — blocked days can't be chosen.
  2. Staff saves — the same rule checks again and shows a plain message under the box when blocked.
  3. Admissions included: birth dates must be in the past, back to 1950.
- **Preserved**: approvals, audit trail, receipts, grade locks, and the library's borrow-before-return order all stay. Announcements can still be dated in the future.
- **Exceptions**: transfers, refunds, corrections, and late entries follow the same guards; old rows already saved are left alone in v1.

## 4. How It Should Work
1. Registrar opens admissions — birth date box only allows 1950-01-01 through today.
2. Teacher marks attendance — date box only allows 1987-01-01 through today (as today).
3. Nurse logs an incident — date box only allows 1987-01-01 through today.
4. Librarian records borrow and return — borrow box only allows 1987-01-01 through today, and return (the due date) must be on or after borrow and at most 3 weeks out.
5. Any staff runs a report with From and To — To must be on or after From; both boxes cap at today so advance dates can't be picked.
6. Blocked dates show a plain message under the box, e.g. "Birth date can't be in the future." or "End date can't be before start date."

## 5. Look & Feel (UX)
- Where it lives: the same boxes as today (admissions form, attendance, clinic log, borrow form, all report filters). No new screens. One job per box: pick a real date.
- Key states:
  - Default: today pre-filled for attendance and incidents; birth left empty.
  - Blocked pick: day greyed out, can't be chosen.
  - Error: plain message under the box naming the fix.
  - Success: saves as today, no extra popups.
  - Permission-denied: unchanged.
- Blocked days are greyed out; messages use everyday words; fewer clicks — no second screen to fix a date.

## 6. Business Rules
### Must always be true
- Birth dates are between 1950-01-01 and today, never tomorrow.
- Attendance, incident, and borrow dates are between 1987-01-01 (school founding) and today. Return (due) dates are on or after borrow and at most 3 weeks out.
- Return date is on or after borrow date. Report To is on or after From. Report From and To never accept future dates.
- Announcements may be dated in the future — excluded from past-only guards.

### Must never happen
- A future birth, incident, borrow, or attendance date is saved.
- A return due date more than 3 weeks out is saved.
- A date before the floor (1950 for births, 1987 for school records) is saved through the form.
- An end date before its start date is saved.
- Guards slow down enrollment-day saving or block money and grade work.

### Edge cases and what happens then
- Teacher born before 1987 → allowed, births go back to 1950.
- Transferee with old paper record → school-record dates before 1987 are refused with a message to see the registrar.
- Typed year like 0024 → refused by the floor with a plain message.
- Report From after To → refused with "End date can't be before start date."
- Existing bad rows → left untouched in v1.

## 7. Out of Scope
- Kinder age-cutoff enforcement (noted for later, see below).
- Auto-fixing or migrating old bad rows.
- Changing announcements to past-only.
- New reports or exports.

## 8. Success Checks
- [ ] Enter tomorrow as a birth date in admissions — refused with a plain message.
- [ ] Enter 1980 as an incident date — refused with a plain message.
- [ ] Enter a return date before its borrow date — refused with a plain message.
- [ ] Enter a return due date more than 3 weeks out — refused with a plain message.
- [ ] Run any report with From after To — refused with a plain message.
- [ ] Try a future date in any report filter — day can't be picked (capped at today).

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages:
  - `portal/student/admission-apply` (birth box, no min/max today) plus draft/submit paths.
  - `portal/teacher/attendance` (already capped: `max=today` in blade, `before_or_equal:today` in `TeacherController:271` — copy this pattern).
  - `portal/nurse/create-log` + `portal/nurse/logs` filters; `portal/librarian/borrow` + `visits` filters (`history` has search/status only, no dates); `portal/cashier/reports` + `collections-report`; `portal/directress/reports` (collections + clinic tabs); `portal/admin/audit-logs` filters; principal announcement create/edit (excluded — future allowed).
- Likely areas of the codebase (files, routes, tables) — fill from code inspection:
  - Admission birth: `StudentAdmissionController.php:78` (`nullable|date`) and `:152` (`required|date`); blade `admission-apply.blade.php:166`, age JS `:403-404`.
  - Clinic: `NurseController.php:109` (`required|date`); blade `nurse/create-log.blade.php:35`.
  - Library: `LibrarianController.php:298-299` (order rule exists: `return_date after_or_equal:borrow_date`); blades `librarian/borrow.blade.php:96,102`; existing 1970 placeholder guards (`LibrarianController:25`, `LibraryTransaction:56`) stay untouched.
  - Announcements (excluded): `PrincipalController.php:455,481` (`required|date`); schedule times already use `after:start_time` pattern (`:91,194,318`).
  - Filters: `date_from`/`date_to` pairs in cashier, directress, nurse, librarian, admin views listed above — add from≤to pairing.
- Data/records touched: birth, attendance, incident, borrow/return, and filter ranges only; counts and money logic untouched; no search-word store.
- Roles/permissions involved: no permission change; registrar primary, teachers, nurse, librarian, cashier covered.

## 11. Approval
> Approved by user on 2026-10-04 (re-approved for filter cap at today).
