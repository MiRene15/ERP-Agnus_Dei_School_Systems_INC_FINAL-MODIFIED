# Strands → Electives + Directress Demographics — 2026-09-23

> **Request:** Change 'strands' to 'Electives', include demographics in Directress account
> **Status:** MDs updated first per instruction — code execution follows

---

## 1. Current State

- **Strand field:** `enrollments.strand` + `admissions.strand` (string nullable, no FK table)
- **Validation:** `in:Arts, Social Sciences, and Humanities,Business and Entrepreneurship` (2 electives, required only for Grade 11/12)
- **Seeder values:** Legacy `STEM/ABM/HUMSS/GAS` still in `StudentsAndFeesSeeder.php` (diverges from validation — will keep DB column but display as Elective)
- **136 occurrences** of `strand` across codebase — most are internal (`strand` column, `$byStrand` var, `LIKE "$strand%"`)
- **User-facing labels (9 blades):** `Strand:` label in COR, report card, admissions-results, dashboard; `By Strand (SHS)` in demographics
- **Demographics:** Already in Directress — `DirectressController.php:44 demographics()` + route `directress.demographics` + sidebar `sidebar-directress.blade.php:1` first link `Demographics` + `demographics.blade.php` with 6 charts (By Grade/Section/Year/Strand + Payments Monthly/Yearly). No code missing — just verify route accessible and rename Strand → Elective.

---

## 2. Changes — Strands → Electives

### Keep DB column as `strand` (no migration rename) — only rename display labels

| File | Change |
|------|--------|
| `portal/student/admission-apply.blade.php:116` | Label `SHS Elective *` already correct — keep; ensure options still show electives |
| `portal/student/cor.blade.php:75` | `Strand:` → `Elective:` |
| `portal/student/partials/report-card-results.blade.php:5` | `Strand:` → `Elective:` |
| `portal/registrar/report-cards/print.blade.php:45` | `Strand:` → `Elective:` |
| `portal/registrar/partials/report-cards-show-results.blade.php:14` | same |
| `portal/registrar/partials/admissions-results.blade.php:27` | Column header `Strand` → `Elective` (keep `?? '—'`) |
| `portal/registrar/partials/admissions-show-results.blade.php:29` | `Strand` label → `Elective` |
| `portal/registrar/partials/dashboard-results.blade.php:26` | inline ` — strand` → ` — elective` |
| `portal/directress/demographics.blade.php:8,28` | `and strand` → `and elective`; `By Strand (SHS)` → `By Elective (SHS)`; `No Strand` → `No Elective`; loop var still `$byStrand` but display as Elective |
| `PromotionalWebsite/program-offerings.blade.php` + `academics.blade.php` | CSS class `.strand-card` → keep or rename to `.elective-card` + display cards title `Strand` → `Elective` |
| `TeachersClassesSchedulesSeeder.php` comment | `strand names` → `elective names` |
| `StudentsAndFeesSeeder.php` comment | same |

**Not renamed (internal):** `$byStrand`, `where('strand')`, `LIKE "$strand%"`, `strand` column name — avoids migration + breaks.

### Validation
- Keep `in:Arts, Social Sciences, and Humanities,Business and Entrepreneurship` — already electives.
- No change to `StudentAdmissionController.php` validation except maybe comment update.

---

## 3. Changes — Demographics in Directress

- **Already present:** `DirectressController.php:44 demographics()` computes `$byGrade, $bySection, $byYear, $byStrand, $total, $paymentsByMonth...`
- **Route:** Verify `routes/web.php` has `Route::get('/directress/demographics', [DirectressController::class, 'demographics'])->name('directress.demographics')` inside `role:8` group.
- **Sidebar:** Already first link at `sidebar-directress.blade.php:1` — ensure `active` class correct.
- **Action:** Rename display only (`Strand` → `Elective` as above); verify page loads for Directress role; add dark-mode classes if missing.

---

## 4. Verification

- [ ] Admissions apply shows `Elective` label and dropdown for Grade 11/12
- [ ] COR + report card show `Elective: Arts...` instead of `Strand:`
- [ ] Registrar admissions list column header `Elective`
- [ ] Directress Demographics page accessible, charts render, labels say `By Elective (SHS)`
- [ ] No DB migration needed — column stays `strand`
- [ ] `php -l` on modified PHP files; `npm run build` not needed (no tailwind change)

---

## 5. Color/Reference

- Same colors/tokens as `dark_mode_audit.md`
- Internal var `$byStrand` kept for backwards compat — display renamed only
