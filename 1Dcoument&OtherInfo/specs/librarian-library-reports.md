# Spec: Librarian Library Reports

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — librarian Library single via shared LibraryReportService (directress delegates, same totals), fenced librarian routes + audited export, sidebar entry + single-tab views; verified visually by user (all 5 checks pass).
- **Parent**: `role-reports-hub.md` (child 2 of 3 — depends on parent + child-1 fencing pattern)

## 1. Why We Need This
The librarian circulates books every day but cannot pull her own library totals. She lists overdue and popular titles by hand when asked. Her own report answers that in one tap, matching exactly what the directress sees on the Library tab.

## 2. Who Is Affected
* **Librarian** — only viewer; pulls her own borrowing totals, recent loans, popular titles, exports for filing.
* **Directress** — unchanged; her Library tab stays the reference truth and must match the librarian totals.
* **IT Admin** — sets the permission once, checks exports in the audit trail.
* Untouched: nurse, registrar, principal, cashier, teachers, students, parents.

## 3. Business Flow: Today vs After
- **As-is**: Librarian opens Borrowing & Returns / History and counts. Directress opens Reports → Library and sees totals, recent 10, popular 5.
- **To-be**:
  1. Librarian opens Reports → Library only → reads totals, recent loans, popular titles.
  2. She exports the full list to CSV when filing is needed.
- **Preserved**: Catalog, Borrowing & Returns, History stay exactly as today; directress Library logic stays the single source of truth.
- **Exceptions**: No loans yet → plain "nothing borrowed yet" note, never a fake row. Opening another role's report shows "not allowed for your role."

## 4. How It Should Work
1. Librarian signs in and taps Reports in her left menu.
2. The page shows Library only: totals on top, recent loans and popular titles below, Export CSV.
3. Tapping Export downloads the full transaction list the screen summarizes.

## 5. Look & Feel (UX)
- Lives in the librarian menu + one page. One primary action: Export CSV (no date filter, same as directress Library tab).
- Same shell as the directress Library tab so it feels familiar.
- Key states: default (totals loaded), loading, empty ("nothing borrowed yet"), error, denied ("not allowed for your role").
- Plain language: Library, Export CSV — same words as directress.
- Fewest clicks: menu → report is one tap.

## 6. Business Rules
### Must always be true
- The librarian totals, recent 10, and popular 5 always match the directress Library tab at the same moment.
- Rows show only what the librarian already sees in Borrowing & Returns / History — no new student fields.
- Every Export is recorded with who took it and when.
- The page is read-only; circulating a book still happens only in Borrowing & Returns.

### Must never happen
- The librarian must never see Clinic, Student Statistics, cashier reports, or the five-tab page — including by guessing a web address.
- No other role must reach the librarian Library page or its Export.
- Directress library logic must not be duplicated; the librarian reads through the same totals path.
- A report must never change, create, or delete a loan or book record.

### Edge cases and what happens then
- Library with no loans → empty note; Export downloads headers only.
- Saved page from before → refresh shows the new menu; nothing to reconcile.

## 7. Out of Scope
- Catalog, Borrowing & Returns, History changes; fines workflow.
- Clinic, Student Statistics, cashier tabs; directress page changes; principal views; scheduled emails.

## 8. Success Checks
- [ ] Librarian sees Reports and opens Library; guessing nurse/registrar addresses is denied.
- [ ] Totals, recent 10, and popular 5 match the directress Library tab at the same moment.
- [ ] Export downloads the full list and appears in the audit trail.
- [ ] Empty library shows the plain empty note.
- [ ] Nurse/registrar/principal sign in normally with no change.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: librarian sidebar + one new page.
- Likely areas (from code inspection): new librarian routes under the librarian guard (same fencing pattern proven in child 1); reuse of the directress library read (`DirectressController::libraryReports` ~lines 360-397: book/loan totals, overdue count, recent 10, popular 5) through the shared read path — do not duplicate the query; directress shell `portal/directress/reports.blade.php` library block (~lines 104-115, no date filter) + `partials/library-reports-results.blade.php` as-is; `sidebar-librarian.blade.php` gains the Reports entry; export mirrors `exportLibraryReports` with per-role audit.
- Data/records touched: read-only over book and loan records. No schema change.
- Roles/permissions involved: librarian only (+ directress reference, IT Admin fencing). Server guard on page + export.

## 11. Approval
> Approved by user on 2026-10-08.
