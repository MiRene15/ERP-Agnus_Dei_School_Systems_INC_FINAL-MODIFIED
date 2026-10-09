# Spec: Section Seeder Consistency

- **Status**: Implemented
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09 (original scope); re-approved 2026-10-09 with amended §6 SHS unscramble-heal edge case
- **Implemented**: 2026-10-09 — 5 slices across 4 seeders (canonical section list, unconditional class rename, strand-aware placement, scatter trim, SHS heal); all 4 success checks confirmed.

## 1. Why We Need This
Staff see different section names for the same grade depending on which screen they open — for example Grade 10 shows A and B in one list but Justice and Temperance in another. This happens because each starter-data routine keeps its own section list and the old-to-new rename only runs half the time. The fix makes saint names the one true list everywhere, so every dropdown, class list, and report matches.

## 2. Who Is Affected
* **Registrar (primary)** — picks sections at enrollment and reads class lists; gets one consistent list everywhere.
* **IT Admin** — runs the starter-data routines; gets a safe re-runnable setup.
* **Teachers** — see the same section names on class lists and grade sheets.
* **Principal / Directress** — see matching section names on reports.

## 3. Business Flow: Today vs After
- **As-is**: Starter data is loaded → sections table holds saint names (St. Agnes, Charity, Justice…) but class records still say A/B on older databases → new students all pile into the first section of each grade → SHS strands sometimes save with a trailing space ("STEM ") → the same grade reads differently per screen.
- **To-be**: Starter data is loaded → (1) one routine owns the saint-name list; (2) class records are renamed to saint names every time; (3) each new student is placed in the real section for their grade and strand; (4) leftover generic names are removed where safe so they never appear in a dropdown again.
- **Preserved**: Class schedules and teacher assignments stay attached to their renamed class; student grades, payment records, and history follow the rename — nothing is thrown away to fix a name.
- **Exceptions**: A class or section that still has linked students or grades is never deleted — it is renamed in place, or switched off from new picks when it is truly retired.

## 4. How It Should Work
1. IT Admin loads the starter data → sections are created only with saint names (St. Agnes/St. Clare … Charity/Hope … Justice/Temperance … STEM/ABM/HUMSS/GAS saint names).
2. Every class record for the current school year points at one of those active saint-name sections.
3. Every starter student is enrolled in the section matching their grade level, and for Grades 11–12 matching their strand prefix (STEM student → STEM section).
4. Registrar opens any enrollment, class-list, schedule, or report screen → the same saint names appear everywhere; no A, B, or STEM-A appears as a pickable option.
5. IT Admin re-runs the starter-data routines → nothing duplicates, nothing renames itself into a different section.

## 5. Look & Feel (UX)
- Where it lives: no new screen. Fixed inside the existing section dropdowns (enrollment, class lists, schedules, reports). The one primary action is unchanged: registrar picks the saint-name section from the dropdown.
- Key states: default (dropdown lists only saint names); empty (seeded data has no empty sections and no classes with zero students); error (a retired or unknown name can never be picked or saved); success (class list opens with students); permission-denied (unchanged — only roles that pick sections today can pick them).
- Plain-language labels; sensible defaults (current school year pre-selected). Each role sees the same names — registrar picks them, teachers and principal read them.
- Fewest clicks: no extra step is added; fixing the options removes the today's workaround of checking two lists to find the same class.

## 6. Business Rules
### Must always be true
- Saint names are the only active section names.
- Every active section has class records and an assigned adviser.
- Every starter SHS enrollment and application carries a strand that exactly matches its section prefix (no trailing spaces).
- Re-running the starter-data routines changes nothing when data is already correct and never creates duplicates.

### Must never happen
- A fresh load never creates A, B, or STEM-A style names.
- No active generic name is left pickable in any dropdown.
- No strand is saved with extra spaces.
- No class is left pointing at a section that does not exist or is switched off.

### Edge cases and what happens then
- Database still holding A/B classes with grades and schedules → rename the class in place; grades, schedules, and student links follow it.
- SHS strand saved as "STEM " with trailing space → trim and correct it where it sits.
- Starter routines run twice (or resume after interruption) → already-correct rows are left untouched; no duplicates.
- Extra old sections beyond the new count → remove for a clean list where they have no linked students, classes, or grades; anything still linked is switched off from new picks instead of deleted, so no history is orphaned.
- SHS enrollments left mismatched by the old positional rename (trimmed strand ≠ section prefix, e.g. ABM students reading under a STEM section) → move the enrollment to the least-loaded active section matching its strand for the same school year, carrying its class links, grades, assessments, attendance, and grade-unlock requests to the same-subject twin classes in the new section; already-correct rows are untouched so this is a no-op on healthy data. (Added 2026-10-09: Slice 5 Check 3 found 73 such rows; rotation pattern ABM→STEM / GAS→ABM / STEM→GAS with HUMSS unaffected proves positional-rename damage.)

## 7. Out of Scope
- Moving already-enrolled live students between sections.
- Changing schedules, rooms, teachers, or adviser assignments beyond filling an empty adviser slot.
- A new section-management screen.
- Fee, payment, grading, or report-calculation logic.

## 8. Success Checks
- [ ] Every section dropdown shows only saint names — no A, B, or STEM-A anywhere.
- [ ] Every active section opens a class list that has students in it.
- [ ] Every SHS starter student sits in the section matching their strand.
- [ ] Re-running the starter-data routines changes nothing and duplicates nothing.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: enrollment, class lists, schedules, section dropdowns, section-based reports (read-only effect — no layout change).
- Likely areas of the codebase (from code inspection):
  - `database/seeders/SubjectsAndSectionsSeeder.php` — single canonical `$sectionData` saint list; replace index-based rename (orderBy section_name + `$existing[$idx]`) with name-keyed lookup so re-runs cannot scramble Justice ↔ Temperance; deactivate (or delete-if-unlinked) extras.
  - `database/seeders/TeachersClassesSchedulesSeeder.php` — read active sections from `sections` table; make A/B → saint rename unconditional (today guarded by new-name-exists check); keep orphan-class cleanup and SHS strand regex (already trims via `\b`, keep it).
  - `database/seeders/StudentsAndFeesSeeder.php` lines ~290-292 — replace `->first()` section pick with strand-aware lookup + round-robin/balanced spread across the grade's sections; trim strand before save.
  - `database/seeders/StudentScatterSeeder.php` lines ~88-90 — `explode('-', section_name)[0]` needs `trim()` (today stores `"STEM "`); keep deficit-based fill and idempotent `updateOrCreate` keys.
  - `database/seeders/DatabaseSeeder.php` — order already correct (sections → classes → students → scatter); no change expected.
- Data/records touched: sections (name, is_active, adviser_id), classes (section text), enrollments (section_id, strand), admissions (strand).
- Repair placement: the unscramble-heal runs in `SubjectsAndSectionsSeeder.php` after the canonical list is ensured (it owns the saint list, so it owns the definition of "mismatched"); trailing-space trim of enrollment/admission strands rides in the same block.
- Roles/permissions involved: none changed — registrar picks, IT Admin runs, others read.

## 11. Approval
> Approved by user on 2026-10-09 (original scope).
> Amended 2026-10-09 with SHS unscramble-heal edge case (§6) + repair placement (§10) — re-approval required before the repair slice is implemented.
