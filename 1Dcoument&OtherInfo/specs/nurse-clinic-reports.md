# Spec: Nurse Clinic Reports

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — nurse Clinic single via shared ClinicReportService (directress delegates, same totals), fenced nurse routes + audited export with rows, sidebar entry + single-tab views; verified visually by user (all 8 checks pass).
- **Parent**: `role-reports-hub.md` (child 1 of 3 — depends on parent; proves server fencing first)

## 1. Why We Need This
The nurse logs every clinic visit but cannot pull her own visit list with totals. She counts by hand when the directress or principal asks "how many this month?" Her own report answers that in one tap, with the same totals the directress sees plus the visit rows she already works with.

## 2. Who Is Affected
* **Nurse** — only viewer; pulls her own clinic list, filters by dates, exports for filing.
* **Directress** — unchanged; her Clinic tab stays totals-only and must match the nurse totals for the same dates.
* **IT Admin** — sets the permission once, checks exports in the audit trail.
* Untouched: librarian, registrar, principal, cashier, teachers, students, parents.

## 3. Business Flow: Today vs After
- **As-is**: Nurse opens Consultation Logs and scrolls/counts. Directress opens Reports → Clinic and sees totals only.
- **To-be**:
  1. Nurse opens Reports → Clinic only → picks From/To → Generate.
  2. She reads totals (visits, patients, referrals, open cases, by grade, top symptoms) then the visit rows below.
  3. She exports the same range to CSV when filing is needed.
- **Preserved**: Consultation Logs stay exactly as today; directress totals logic stays the single source of truth; approvals and audit points unchanged.
- **Exceptions**: A range with no visits shows "nothing in this period — try wider dates," never a fake row. Opening another role's report shows "not allowed for your role."

## 4. How It Should Work
1. Nurse signs in and taps Reports in her left menu.
2. The page shows Clinic only: From/To boxes pre-filled month-to-date, Generate, Clear, Export CSV.
3. Tapping Generate shows totals on top and visit rows below for that range.
4. Tapping Export downloads the same range the screen shows.

## 5. Look & Feel (UX)
- Lives in the nurse menu + one page. One primary action: Generate; secondary: Export CSV.
- Same shell as the directress Clinic tab (same headings, same button style) so it feels familiar.
- Key states: default (month-to-date loaded), loading, empty ("nothing in this period — try wider dates"), validation error ("end can't be before start"), success (rows appear), denied ("not allowed for your role").
- Plain language: Clinic, From, To, Generate Report, Clear, Export CSV — same words as directress.
- Fewest clicks: menu → report is one tap; Generate runs on date change as well as button.

## 6. Business Rules
### Must always be true
- The nurse totals always match the directress Clinic tab for the same dates.
- Visit rows show only what the nurse already sees in Consultation Logs (student, date, symptoms, action taken) — no new health fields are exposed anywhere.
- From/To is always pre-filled month-to-date, never blank.
- An end date before the start date never runs a report; it shows the plain error note.
- Every Export is recorded with who took it and when.
- The page is read-only; logging a visit still happens only in Consultation Logs.

### Must never happen
- The nurse must never see Library, Student Statistics, cashier reports, or the five-tab page — including by guessing a web address.
- No other role must reach the nurse Clinic page or its Export.
- Directress totals logic must not be duplicated; the nurse reads through the same totals path.
- A report must never change, create, or delete a clinic record.

### Edge cases and what happens then
- No visits in range → empty note + suggestion to widen dates; Export downloads headers only.
- Wide range at peak (e.g. full school year) → still returns promptly on the grouped reads the directress uses.
- Saved page from before → refresh shows the new menu; nothing to reconcile.

## 7. Out of Scope
- Consultation Logs changes, new health fields, referrals workflow.
- Library, Student Statistics, cashier tabs.
- Directress page changes; principal views; scheduled emails.

## 8. Success Checks
- [ ] Nurse sees Reports and opens Clinic; guessing librarian/registrar addresses is denied.
- [ ] Default view is month-to-date with totals + rows.
- [ ] Changing From/To changes both totals and rows.
- [ ] End-before-start shows the plain error and runs nothing.
- [ ] Empty range shows the plain empty note.
- [ ] Export downloads the shown range and appears in the audit trail.
- [ ] Same dates in directress Clinic tab show the same totals.
- [ ] Librarian/registrar/principal sign in normally with no change.

## 9. Open Questions (if any)
None — nurse-rows-included confirmed by you on 2026-10-08 (option 1).

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: nurse sidebar + one new page.
- Likely areas (from code inspection): new nurse routes under the nurse guard; reuse of the directress clinic read (`DirectressController::clinicReport` ~lines 480-512: `ClinicLog` with student/section, date-bounded, grouped totals) through a shared read path — do not duplicate the query; directress shell `portal/directress/reports.blade.php` clinic block (~lines 75-102) + `partials/clinic-reports-results.blade.php` as the base, extended with the visit rows the nurse already renders in her logs; `sidebar-nurse.blade.php` gains the Reports entry; export mirrors `exportClinicReport` with per-role audit.
- Data/records touched: read-only over clinic visit records. No schema change.
- Roles/permissions involved: nurse only (+ directress reference, IT Admin fencing). Server guard on page + export; menu hiding alone is not the fence.

## 11. Approval
> Approved by user on 2026-10-08.
