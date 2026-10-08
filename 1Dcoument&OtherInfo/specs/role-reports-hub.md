# Spec: Role Reports Hub (Parent)

- **Status**: Approved
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08

## 1. Why We Need This
The directress sees all reports in one place, but the nurse, librarian, and registrar have no Reports at all. Each of them already does the daily work behind one of those reports. Giving each role their own single report means they can answer their own questions without asking the office, and without seeing data that is not theirs.

## 2. Who Is Affected
* **Nurse (clinic)** — sees Clinic visits she already logs; no counting by hand.
* **Librarian** — sees Library borrowing she already circulates; no manual lists.
* **Registrar** — sees Student Statistics for enrollment she already processes.
* **Directress** — unchanged; her five-tab page stays the reference truth all three singles must match.
* **IT Admin** — sets who sees what once, checks the audit trail.
* Untouched: principal, cashier, teachers, students, parents.

## 3. Business Flow: Today vs After
- **As-is**: Nurse logs visits but cannot pull the clinic list herself. Librarian circulates books but cannot pull the library list. Registrar admits students but cannot pull the enrollment counts. Only the directress can open these three lists.
- **To-be**:
  1. Nurse opens Reports → sees Clinic only → picks dates → Generate → Export if needed.
  2. Librarian opens Reports → sees Library only → Export if needed.
  3. Registrar opens Reports → sees Student Statistics only → Export if needed.
- **Preserved**: The directress page, her numbers, her filters and exports, approvals, balances, health-note privacy, and audit records — all unchanged.
- **Exceptions**: A period with nothing in it shows a plain "nothing in this period" note, never a fake row. A role opening another role's page is denied with a plain "not allowed" note.

## 4. How It Should Work
1. Each role signs in and sees one new Reports item in their left menu.
2. Opening it shows exactly one report: Clinic for nurse, Library for librarian, Student Statistics for registrar.
3. Filters and Export work exactly like the matching directress tab.
4. Numbers always match the directress tab for the same dates.

## 5. Look & Feel (UX)
- One menu item per sidebar, one page per role, one primary action (Generate for clinic; Export for all three).
- Same page shell as the directress tabs so staff already know it: same title area, same date boxes for clinic, same Export button style.
- Key states per page: default, loading, empty ("nothing in this period — try wider dates"), error, denied ("not allowed for your role").
- Plain language throughout: Clinic, Library, Student Statistics — the same words the directress sees.
- Fewest clicks: menu → report is one tap; Generate/Export is the only action on the page.

## 6. Business Rules
### Must always be true
- One role sees exactly one report: nurse sees Clinic only, librarian sees Library only, registrar sees Student Statistics only.
- Each single always shows the same rows and totals as the matching directress tab for the same dates and filters.
- Access is checked on the server for every page and every Export, not just by hiding menu items.
- Every Export is recorded in the audit trail with who took it and when.
- All three pages are read-only; nothing is created, changed, or deleted from a report.

### Must never happen
- A role must never see another role's report, cashier reports, or the full five-tab page — including by guessing a web address.
- Clinic health notes must never reach librarian, registrar, or any non-clinic role.
- Numbers on a single must never differ from the directress tab for the same dates.
- No new report logic that only one role uses; the directress path stays the single source of truth.

### Edge cases and what happens then
- No rows in range → plain empty note with what to do next (widen dates), never a blank page.
- End date before start date → plain "end can't be before start" note; no report runs.
- Saved page from before the change → refresh shows the new menu; nothing to reconcile.
- Wide date range at enrollment peak → page must still return promptly; same cheap grouped reads the directress uses.

## 7. Out of Scope
- Cashier tabs (Collections, Receivables) for any of these three roles.
- New report types, new filters, scheduled emails, parent-facing copies.
- Changes to the directress page, principal views, or any other menu.
- Anything that writes data from a report.

## 8. Success Checks
- [ ] Nurse sees Reports and opens Clinic only; librarian and registrar pages are denied to her.
- [ ] Librarian sees Reports and opens Library only; other two are denied to her.
- [ ] Registrar sees Reports and opens Student Statistics only; other two are denied to her.
- [ ] Each single matches its directress tab row-for-row for the same dates.
- [ ] Each Export downloads and is recorded in the audit trail.
- [ ] Directress five-tab page works exactly as before.
- [ ] Principal and cashier see no change.

## 9. Open Questions (if any)
None for the parent — single-tab hardcoding and server-side fencing confirmed by you on 2026-10-08. School-specific wording for empty/denied notes defaults to the plain sentences above unless you override in a child.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: nurse, librarian, registrar sidebars + one new page each; directress hub untouched.
- Likely areas (from code inspection): `routes/web.php` directress block (~lines 355-368) as the reference; new role routes under each role's own guard; reuse of `DirectressController` read methods (`clinicReport`, `libraryReports`, `studentStatsReport`) through a shared read path; reuse of `portal/directress/reports.blade.php` tab shells + `partials/*results` views as single-tab pages; sidebar partials `sidebar-nurse`, `sidebar-librarian`, `sidebar-registrar`.
- Data/records touched: read-only over clinic logs, book/loan records, enrollment/student counts — same reads the directress uses. No schema change.
- Roles/permissions involved: nurse, librarian, registrar (each exactly one report); directress (reference); IT Admin (fencing + audit). Server guard on every page + export.

## 11. Children (independent gates — one at a time)
| # | Child spec | Role → Report | Depends on | Status |
|---|-----------|---------------|-----------|--------|
| 1 | `nurse-clinic-reports.md` | Nurse → Clinic | Parent | Not started |
| 2 | `librarian-library-reports.md` | Librarian → Library | Parent + child-1 pattern | Not started |
| 3 | `registrar-student-stats-reports.md` | Registrar → Student Statistics | Parent + child-1 pattern | Not started |

Each child gets its own Scope, Business Rules, Success Checks, and Approval. `/exec-spec` runs one child at a time. No code without its child approved.

## 12. Approval
> Approved by user on 2026-10-08.
