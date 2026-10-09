# Spec: JHS Pure-Saint Sections

- **Status**: Approved
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09

## 1. Why We Need This
Grades 7 to 10 still use virtue names like Charity, Hope, Faith, and Justice. The school rule is purely saints everywhere. Parents and staff see two naming stories, and reports look inconsistent next to Kinder to Grade 6 and Senior High which already use saints. This fix gives Junior High 8 saint names so every grade reads the same way.

## 2. Who Is Affected
* **Registrar (primary)** — picks sections at enrollment; gets one saints-only list for Grades 7 to 10.
* **Teachers** — see the same saint names on class lists and grade sheets.
* **Principal / Directress** — see matching saint names on reports.
* **Cashier** — parent statements and section-based totals show the same saint names (read-only effect).
* **IT Admin** — runs the starter data; gets a safe re-runnable change.

## 3. Business Flow: Today vs After
- **As-is**: Starter data loads → Grade 7 holds Charity and Hope, Grade 8 Faith and Love, Grade 9 Wisdom and Courage, Grade 10 Justice and Temperance → class records and enrollments follow those virtue names.
- **To-be**: Starter data loads → Grade 7 holds St. Lorenzo Ruiz and St. Pedro Calungsod, Grade 8 St. Ignatius and St. Francis Xavier, Grade 9 St. Monica and St. Rita, Grade 10 St. Cecilia and St. Carlo Acutis → every old virtue class row is renamed in place to its saint twin → virtue names are retired from new picks.
- **Preserved**: Class schedules and teacher assignments stay attached to their renamed class. Student grades, payment records, and history follow the rename. Nothing is thrown away to fix a name.
- **Exceptions**: A class or section that still has linked students or grades is never deleted — it is renamed in place, or switched off from new picks when it is truly retired.

## 4. How It Should Work
1. IT Admin loads the starter data → Grades 7 to 10 sections are created only with the 8 new saint names.
2. Every class record for the current school year for Grades 7 to 10 points at one of those active saint sections.
3. Every starter student in Grades 7 to 10 sits in the saint section for their grade.
4. Registrar opens any enrollment, class-list, schedule, or report screen → the same 8 saint names appear everywhere; no Charity, Hope, Faith, Love, Wisdom, Courage, Justice, or Temperance appears as a pickable option.
5. IT Admin re-runs the starter data → nothing duplicates, nothing renames itself into a different section.

## 5. Look & Feel (UX)
- Where it lives: no new screen. Fixed inside the existing section dropdowns (enrollment, class lists, schedules, reports). The one primary action is unchanged: registrar picks the saint-name section from the dropdown.
- Key states: default (dropdown lists only saint names); empty (seeded data has no empty sections); error (a retired virtue name can never be picked or saved); success (class list opens with students); permission-denied (unchanged — only roles that pick sections today can pick them).
- Plain-language labels; sensible defaults (current school year pre-selected). Each role sees the same names — registrar picks them, teachers and principal read them.
- Fewest clicks: no extra step is added.

## 6. Business Rules
### Must always be true
- Saint names are the only active section names for Grades 7 to 10.
- Every active JHS section has class records and an assigned adviser.
- Re-running the starter data changes nothing when data is already correct and never creates duplicates.
- The 8 new names stay unique against all other grades.

### Must never happen
- A fresh load never creates Charity, Hope, Faith, Love, Wisdom, Courage, Justice, or Temperance.
- No active virtue name is left pickable in any dropdown.
- No class is left pointing at a section that does not exist or is switched off.
- No student, grade, or schedule history is orphaned by the rename.

### Edge cases and what happens then
- Database still holding A or B classes for Grades 7 to 10 → rename A/B straight to the new saint in one pass.
- Database already holding virtue classes with grades and schedules → rename the class in place to its saint twin; grades, schedules, and student links follow it.
- Both old and new rows exist for the same class (half-finished earlier run) → fold links into the surviving saint row, then remove the duplicate so the next run converges on one row.
- Extra virtue sections beyond the new list → remove for a clean list where they have no linked students or classes; anything still linked is switched off from new picks instead of deleted.
- Starter routines run twice (or resume after interruption) → already-correct rows are left untouched; no duplicates.

## 7. Out of Scope
- Changing the number of sections per grade (stays at 2 per JHS grade).
- Moving already-enrolled live students between sections except by rename.
- Changing schedules, rooms, teachers, or adviser assignments beyond filling an empty adviser slot.
- A new section-management screen.
- Fee, payment, grading, or report-calculation logic.

## 8. Success Checks
- [ ] Every Grades 7 to 10 dropdown shows only the 8 new saint names — no Charity, Hope, Faith, Love, Wisdom, Courage, Justice, or Temperance anywhere.
- [ ] Every active JHS section opens a class list that has students in it.
- [ ] Old virtue names never come back on a fresh load.
- [ ] Re-running the starter data changes nothing and duplicates nothing.

## 9. Open Questions (if any)
None — saint slate confirmed 2026-10-09 (Grade 7 St. Lorenzo Ruiz / St. Pedro Calungsod; Grade 8 St. Ignatius / St. Francis Xavier; Grade 9 St. Monica / St. Rita; Grade 10 St. Cecilia / St. Carlo Acutis).

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: enrollment, class lists, schedules, section dropdowns, section-based reports (read-only effect — no layout change). Sample text in principal schedule upload example also updates.
- Likely areas of the codebase (from code inspection):
  - `database/seeders/SubjectsAndSectionsSeeder.php` — canonical `$sectionData` Grades 7–10 to 8 saints; extend name-keyed legacy map with virtue → saint pairs (Charity → St. Lorenzo Ruiz, Hope → St. Pedro Calungsod, Faith → St. Ignatius, Love → St. Francis Xavier, Wisdom → St. Monica, Courage → St. Rita, Justice → St. Cecilia, Temperance → St. Carlo Acutis) plus keep A/B → saint for unmigrated DBs; deactivate (or delete-if-unlinked) virtue extras.
  - `database/seeders/TeachersClassesSchedulesSeeder.php` — same virtue → saint rename in `$legacySectionMap` with twin-fold dedupe; keep orphan-class cleanup reading active sections from the sections table.
  - `database/seeders/StudentScatterSeeder.php`, `StudentsAndFeesSeeder.php` — no change expected (both read active sections from the sections table); verify JHS picks follow new names.
  - `app/Http/Controllers/Portal/PrincipalController.php` + `resources/views/portal/principal/schedules-manage.blade.php` — update sample CSV row from `Grade 7, Charity` to `Grade 7, St. Lorenzo Ruiz`.
- Data/records touched: sections (name, is_active, adviser_id), classes (section text), enrollments (section_id follow via class rename).
- Parent spec: `section-seeder-consistency.md` (Implemented) — this spec is a follow-up child; it does not reopen the parent.
- Roles/permissions involved: none changed — registrar picks, IT Admin runs, others read.

## 11. Approval
> Approved by user on 2026-10-09.
