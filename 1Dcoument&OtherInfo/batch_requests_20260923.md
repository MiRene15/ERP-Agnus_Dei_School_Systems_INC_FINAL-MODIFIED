# Batch Requests — 2026-09-23

> **Source:** User batch of 16 items (admin verification, promotion, cashier, reports, schedules, sections, seeders, report card, grade table)
> **Status:** MDs updated first per instruction — code execution follows

---

## 1. Admin Verification Not Showing in Audit Logs

- **Issue:** Admin does Verify (requirement check) but no entry appears in Audit Logs page.
- **Root cause to check:** `RegistrarAdmissionController.php:verifyRequirement` / `verifyAll` — `log_activity()` may use wrong event key, wrong subject, or missing `causer_id` (admin vs superadmin middleware). Also `audit-logs-results.blade.php` color map may hide event (gray default) and pagination `withQueryString()` may filter it out.
- **Fix:** Verify `log_activity($requirement, 'Requirement Verified', ...)` and `log_activity($admission, 'All Requirements Verified', ...)` actually persist; ensure `AdminController.php:auditLogs` returns all events; add distinct color for `Requirement Verified`; test with admin account.

## 2. Grade Level by Ranks, Not Randomized

- **Issue:** Grade levels appear in random/hash order instead of Kinder → Grade 12.
- **Current:** `PromotionController.php:index` does `$enrollments->groupBy(...)->sortKeys()` — sorts alphabetically, which puts Grade 10 before Grade 2. Same pattern in other dashboards.
- **Fix:** Add rank map `Kinder=0, Grade 1=1, ... Grade 12=12` and sort by rank: `->sortBy(fn($_, $k) => $rank[$k] ?? 99)` in promotion + any other grouped grade views.

## 3. Promotion — Per-Level Batch, Top Button, Qualified-Only, Fee Carryover

- **Issue:** Batch is global, button at bottom (must scroll), promotes anyone, leftover balance handling unclear.
- **Fix:**
  - Per-level batch: each grade card gets its own `selectedIds` + Batch Promote button (scoped to that grade).
  - Also keep global "All" batch at top for bulk.
  - Move batch button to **top-right corner** (fixed or card-header) so no scrolling needed.
  - Qualified-only: batch only includes `qualified` students (`GWA >= passing && failCount==0`); skip not-qualified with error message.
  - Leftover tuition: already handled in `PromotionController.php:carryFees()` — `balance` carried as `carried_over_balance` and added to new `total_assessed/balance`. Verify it runs for promote/retain and show carried amount in success message.

## 4. Cashier Payments Tab — Remove Floating Search Modal

- **Issue:** Floating search modal on `cashier/payments` adds clutter.
- **File:** `resources/views/portal/cashier/payments.blade.php` — has a search modal/overlay.
- **Fix:** Remove the floating modal; keep inline search bar in the table header instead.

## 5. Financial View — Show Library Fees

- **Issue:** Financial view doesn't show library borrowing/return fees (late fees, lost book charges).
- **Files:** `CashierController.php:studentFinancial`, `portal/cashier/partials/student-financial-results.blade.php`, `StudentLedger` / `LibraryTransaction`
- **Fix:** Query `LibraryTransaction` for student where `fees_assessed=true`; show total library fees + breakdown (late days × rate); include in balance if not yet paid.

## 6. Breakdown List — Own Section, Full Table

- **Issue:** Breakdown is minimal list under tuition — wants own full visible section/table.
- **File:** `portal/cashier/partials/student-financial-results.blade.php`
- **Fix:** Create dedicated "Fee Breakdown" table: columns Tuition / Misc / Library Fees / Discount / Carried Balance / Total Assessed / Total Paid / Balance. Full rows per term + summary row. Not collapsible, always visible.

## 7. Collections Report → Reports Tab (Collections + Receivables)

- **Issue:** Wants `Reports` tab with two collapsible tabs: Collections Report + Receivables Report (receivables = outstanding balances).
- **Files:** `portal/cashier/reports` or `collections-report.blade.php`, `CashierController.php:collectionsReport`, new `receivablesReport` method; `DirectressController.php:cashierReports`
- **Fix:** Rename/create `Reports` page with two collapsible sections (Alpine `x-data` toggle). Collections = date-range + paid list (existing). Receivables = all students with `balance > 0`, grouped by grade/section, exportable. Same template style. Add nav link `Reports` instead of `Collections Report`.

## 8. Principal Schedules — Refine CRUD

- **Issue:** Principal's schedule management needs full CRUD refinement.
- **Files:** `PrincipalController.php`, `portal/principal/schedules/*` or `schedules.blade.php`
- **Fix:** Review index/create/edit/delete; ensure grade/section/teacher/subject/day/time validation; fix dark mode; add AJAX table if missing; confirm store/update/destroy log audit; test overlapping schedule prevention.

## 9. Actual Section Names for All Sections

- **Issue:** Sections have generic names (e.g., "Section A/B") — wants real names like St. Agnes, St. Catherine, etc. or descriptive.
- **Files:** `database/seeders/SubjectsAndSectionsSeeder.php`, `SectionsSeeder`, `Section` model
- **Fix:** Replace generic section_name with actual names (e.g., St. Agnes, St. Monica, St. Clare, etc. or Honesty, Charity — pick theme consistent with Agnus Dei). Ensure 1-2 sections per grade (Kinder–Grade 12). Update enrollments to match.

## 10. Statement of Accounts — Add Library Fees

- **Issue:** Statement of Accounts doesn't include library fees.
- **File:** `portal/cashier/partials/receipt-print.blade.php`, `portal/student/statement` or `ledger`, `CashierController.php`
- **Fix:** Same query as #5 — pull `LibraryTransaction` fines; add row "Library Fees: ₱X" to statement; include in total balance.

## 11. Advisers/Teachers All Assigned — Edit Seeders

- **Issue:** Not all sections/classes have advisers/teachers assigned and connected.
- **Files:** `TeachersClassesSchedulesSeeder.php`, `SubjectsAndSectionsSeeder.php`, `SystemRolesAndStaffSeeder.php`
- **Fix:** Ensure every `Section.adviser_id` is set (valid teacher user), every `Classes.teacher_id` is set and matches subject/grade, every `Schedule` links class+teacher. Cross-check 1:1 section→adviser, class→teacher.

## 12. 50–100 Students — Edit Seeders

- **Issue:** Need 50–100 students (currently ~42).
- **File:** `database/seeders/StudentsAndFeesSeeder.php`
- **Fix:** Expand array to 75 students (diverse: grade distribution ~5-6 per grade, varied balances, discounts ESC/honor/sibling, with withdrawn/graduated, with library fees, across all strands). Include realistic PH names.

## 13. Report Card — Align General Average & Remarks in Column

- **Issue:** General average and remarks not column-aligned.
- **File:** `portal/registrar/report-cards/print.blade.php`
- **Fix:** Ensure Final column + Remarks column are proper table columns; General Average row aligns under Final column (centered, bold); Remarks (Passed/Failed) aligns under Remarks column.

## 14. Grades Accurately Shown in Report Cards

- **Issue:** Report card grades may not match teacher-entered grades (averaging bug, missing term, wrong class).
- **File:** `ReportCardController.php`, `report-cards/print.blade.php`, `Grades` model
- **Fix:** Verify `ReportCardController.php:show` eager-loads correctly, groups by `class_id` + averages 3 terms or shows each term; ensure `final_grade` from `grades` table is used directly (not recomputed). Fix sort by subject name. Test with seeded grades.

## 15. Remove Grade Table Tab in Teacher Role

- **Issue:** Wants grade-table tab removed (not the intended workflow).
- **Files:** `routes/web.php` (remove `teacher.grade-table` routes), `TeacherController.php` (keep model but remove method or hide route), `portal/partials/sidebar-teacher.blade.php` (remove link), `portal/teacher/grade-table.blade.php` + partial (delete or decommission), `portal/teacher/assessments.blade.php` (remove "Switch to Table View" button)
- **Fix:** Remove grade-table entries: sidebar link, routes, button. Keep assessment flow (per-student grade assessment + computed grades).

## 16. Grade Assessment — Teacher Can Search Student

- **Issue:** In grade assessment, teacher wants search for student.
- **File:** `portal/teacher/grade-assessment*.blade.php`, `TeacherController.php:gradeAssessment`
- **Fix:** Add search input (name/LRN) filtering the class student list (Alpine filter or server-side `?search=`). Already has `ajaxTable` on some — extend to grade assessment list.

---

## Execution Order

1. **09 + 11 + 12** — Sections + Advisers + 75 students (seeders — must re-seed)
2. **02 + 03** — Grade rank + Promotion per-level
3. **01** — Audit logs verify
4. **15 + 16** — Remove grade-table, add search
5. **04 + 05 + 06 + 10** — Cashier payments modal, financial/library fees, breakdown table, statement
6. **07** — Reports tab (collections + receivables)
7. **08 + 13 + 14** — Principal schedules, report card alignment/accuracy

## MD Updates (this step)

- [x] Create `batch_requests_20260923.md` (this file)
- [ ] Update `CHANGELOG.md` — add Unreleased entry for these 16 items
- [ ] Update `update_sessions_log.md` — add Session 47
- [ ] Update `dark_mode_audit.md` / `polish_suggestions.md` if affected
