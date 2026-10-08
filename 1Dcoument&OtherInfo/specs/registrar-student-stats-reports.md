# Spec: Registrar Student Statistics Reports

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — registrar Students single via shared StudentStatsService (directress delegates, same counts), fenced registrar routes + audited export, sidebar entry + single-tab views; verified visually by user (all 5 checks pass).
- **Parent**: `role-reports-hub.md` (child 3 of 3 — depends on parent + child-1 fencing pattern)

## 1. Why We Need This
The registrar admits every student but cannot pull her own enrollment counts. She tallies by level, section, and strand by hand when the directress or principal asks "how many in Grade 7?" Her own report answers that in one tap, matching exactly what the directress sees on the Student Statistics tab.

## 2. Who Is Affected
* **Registrar** — only viewer; pulls live enrollment counts, exports for filing.
* **Directress** — unchanged; her Students tab stays the reference truth and must match the registrar counts.
* **IT Admin** — sets the permission once, checks exports in the audit trail.
* Untouched: nurse, librarian, principal, cashier, teachers, students, parents.

## 3. Business Flow: Today vs After
- **As-is**: Registrar opens Admissions Queue / Sections & Subjects and counts. Directress opens Reports → Student Statistics and sees counts by grade, section, year, gender, strand.
- **To-be**:
  1. Registrar opens Reports → Student Statistics only → reads live counts.
  2. She exports the breakdown to CSV when filing is needed.
- **Preserved**: Admissions Queue, Report Cards, Sections & Subjects stay exactly as today; directress Students logic stays the single source of truth.
- **Exceptions**: No active enrollments → plain "no active enrollments" note, never a fake row. Opening another role's report shows "not allowed for your role."

## 4. How It Should Work
1. Registrar signs in and taps Reports in her left menu.
2. The page shows Student Statistics only: totals plus breakdowns by grade, section, school year, gender, strand, with Export CSV.
3. Tapping Export downloads the breakdown the screen shows.

## 5. Look & Feel (UX)
- Lives in the registrar menu + one page. One primary action: Export CSV (no date filter, same as directress Students tab — counts are live Active enrollments).
- Same shell as the directress Students tab so it feels familiar.
- Key states: default (counts loaded), loading, empty ("no active enrollments"), error, denied ("not allowed for your role").
- Plain language: Student Statistics, Export CSV — same words as directress.
- Fewest clicks: menu → report is one tap. Peak-safe: counts stay the cheap grouped reads the directress uses.

## 6. Business Rules
### Must always be true
- The registrar counts always match the directress Students tab at the same moment (same Active-enrollment source).
- Rows show only enrollment counts the registrar already works with — no new student PII beyond what Admissions already shows.
- Every Export is recorded with who took it and when.
- The page is read-only; admitting or promoting a student still happens only in Admissions/Promotion.

### Must never happen
- The registrar must never see Clinic, Library, cashier reports, or the five-tab page — including by guessing a web address.
- No other role must reach the registrar Students page or its Export.
- Directress students logic must not be duplicated; the registrar reads through the same counts path.
- A report must never change, create, or delete an enrollment or student record.

### Edge cases and what happens then
- Start of school year with no Active enrollments → empty note; Export downloads headers only.
- Saved page from before → refresh shows the new menu; nothing to reconcile.

## 7. Out of Scope
- Admissions, Report Cards, Sections & Subjects, promotion workflow changes.
- Clinic, Library, cashier tabs; directress page changes; principal views; scheduled emails.

## 8. Success Checks
- [ ] Registrar sees Reports and opens Student Statistics; guessing nurse/librarian addresses is denied.
- [ ] Counts match the directress Students tab at the same moment.
- [ ] Export downloads the breakdown and appears in the audit trail.
- [ ] Empty enrollments shows the plain empty note.
- [ ] Nurse/librarian/principal sign in normally with no change.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: registrar sidebar + one new page.
- Likely areas (from code inspection): new registrar routes under the registrar guard (same fencing pattern proven in child 1); reuse of the directress students read (`DirectressController::studentStatsReport` ~lines 515-541: Active enrollments grouped by grade/section/year/gender/strand) through the shared read path — do not duplicate the query; directress shell `portal/directress/reports.blade.php` students block (~lines 117-128, no date filter) + `partials/student-stats-results.blade.php` as-is; `sidebar-registrar.blade.php` gains the Reports entry; export mirrors `exportStudentStatsReport` with per-role audit.
- Data/records touched: read-only over enrollment/student counts. No schema change.
- Roles/permissions involved: registrar only (+ directress reference, IT Admin fencing). Server guard on page + export.

## 11. Approval
> Approved by user on 2026-10-08.
