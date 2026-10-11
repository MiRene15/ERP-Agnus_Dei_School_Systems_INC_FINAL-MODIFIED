# Spec: Seed Data Foundations

- **Status**: Implemented
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11
- **Implemented**: 2026-10-11 — Slices 1–4 implemented (SettingsSeeder locks/readiness, FeeSchedulesSeeder ×4 years, TeachersClassesSchedulesSeeder ×4 years, sections/order verified no-change). Acceptance checks NOT run — no verification output was provided; closed at the user's explicit direction without confirmed passes. Re-run the Phase 5 checks before relying on this in production.

## 1. Why We Need This
Students, payments, and grades have nowhere to attach for 2024-2025, 2025-2026, or 2027-2028 because sections, classes, schedules, and fee tables only exist for the current year. This builds that backbone for all four years so later children can fill students and money on top.

## 2. Who Is Affected
* **Registrar + IT Admin (primary)** — pick and build saint sections, class lists, and schedules per year.
* **Cashier** — fee tables exist per grade and term per year to bill against.
* **Principal / Teachers (read-only)** — reports and class lists read the same sections and teachers per year.

## 3. Business Flow: Today vs After
- **As-is**: Starter data ensures sections, classes, and fees for the active year only. Other years show blanks.
- **To-be**:
  1. JHS saints rename runs first so virtue names never enter history.
  2. For 2024-2025, then 2025-2026, then current, then 2027-2028: saint sections ensured → classes built per section and subject → teachers and rooms assigned without clashes inside that year → fee tables built per grade and term.
  3. 2024-2025 and 2025-2026 locked; current left unlocked; 2027-2028 marked ready.
- **Preserved**: Class schedules and teacher assignments stay attached when a rename happens. Locked years refuse edits with a plain message. Audit keeps who and when.
- **Exceptions**: Linked old rows are renamed, never deleted. Wrong-strand SHS rows are moved to the matching strand section that year with class links carried over. Orphan classes with no active section are removed so students never see double grades.

## 4. How It Should Work
1. IT Admin runs starter data → JHS sections become the 8 saints (St. Lorenzo Ruiz, St. Pedro Calungsod, St. Ignatius, St. Francis Xavier, St. Monica, St. Rita, St. Cecilia, St. Carlo Acutis).
2. Each of the four years gets full sections, classes with teachers/rooms/schedules, and fee tables.
3. Registrar opens section dropdowns, class lists, schedules, fee tables → switches year → sees full correct rows per year.
4. IT Admin re-runs → nothing duplicates, nothing renames into a different section.

## 5. Look & Feel (UX)
- Where it lives: no new screen. Same section dropdowns, class lists, schedules, fee tables. One action unchanged: registrar picks the section.
- Key states: default (current year pre-selected, full); history (2024/2025 full, locked); ready (2027-2028 full backbone, plain "enrollment not yet open" where students would be); error (locked-year edit refused plainly); permission-denied (unchanged roles).
- Plain labels, fewest clicks (year picker only). Room, teacher, and adviser shown where they already appear.

## 6. Business Rules
### Must always be true
- Saint names are the only active sections for Grades 7 to 10 in all four years.
- Every active section has classes, an adviser, and fee rows for its year.
- A class is identified by subject + grade + section + school year + term — re-runs find the same row.
- Schedules are clash-free inside each year for teacher, section, and room.

### Must never happen
- A fresh build creates virtue or generic (A/B/STEM-A) names as pickable options.
- A class points at a missing or switched-off section.
- A locked year accepts new sections, classes, schedules, or fees.
- A re-run duplicates a section, class, schedule, or fee row.

### Edge cases and what happens then
- Old A/B or virtue class rows exist → renamed to the saint twin in that year's key; if the twin already exists, links fold into the survivor and the duplicate goes away.
- Class with no active section that year → removed so no double grades.
- SHS enrollment under the wrong strand section → moved to the least-loaded matching strand section that year; links carried to same-subject twin classes.
- Interrupted run → finished years untouched on resume.

## 7. Out of Scope
- Student, admission, enrollment, payment, grade, attendance, library, and clinic rows (Children 2-4).
- Changing how many sections each grade has.
- Changing rooms, teachers, or advisers beyond filling an empty slot.
- A new section-management screen.
- Room-capacity tuning, SHS term naming polish, adviser-load balancing view — already implemented, noted only.

## 8. Success Checks
- [ ] Every year's section dropdowns show saints-only for Grades 7-10 — no virtue or generic names pickable.
- [ ] Every active section in each year opens a class list with teachers, rooms, and schedules, no clashes.
- [ ] Fee tables exist per grade and term for all four years.
- [ ] 2024-2025 and 2025-2026 refuse edits as locked; current allows work; 2027-2028 reads ready.
- [ ] Re-running changes nothing and duplicates nothing.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: enrollment section dropdowns, class lists, schedules, fee tables, year pickers (no layout change).
- Likely areas of the codebase (from code inspection):
  - `Actual_Website/ERP_Agnus_Dei_School_Systems_INC/database/seeders/SubjectsAndSectionsSeeder.php:165-179,184-235` — extend `$sectionData` + name-keyed migrate/ensure/retire loop to 4 years; JHS entries become 8 saints with virtue→saint legacy pairs.
  - `.../seeders/TeachersClassesSchedulesSeeder.php:42,65-68,90-165` — `active_school_year()` → year loop; `$legacySectionMap` JHS becomes virtue→saint; twin-fold dedupe + orphan cleanup scoped per `school_year`.
  - `.../seeders/FeeSchedulesSeeder.php:12-52` — `updateOrCreate` per grade×term×each of 4 years.
  - `.../seeders/SettingsSeeder.php:15-16` + `app/helpers.php:44-92` — set `locked_school_years=2024-2025,2025-2026`, announced 2027-2028; enforcement already in `DirectressController.php:156-177`, `FeeAssignmentController.php:109-110`.
- Data/records touched: settings (locked/announced years), sections (name, is_active, adviser), classes (subject/grade/section/year/term, teacher, room), schedules, fee schedules.
- Parent: `seed-data-connected-history.md` (Approved 2026-10-11). Depends on `jhs-pure-saint-sections.md` (Approved) running first. Next: `seed-data-students-promotions.md`.
- Roles/permissions involved: none changed.

## 11. Approval
> Approved by user on 2026-10-11.
