# Spec: Student Gender Data Quality

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-09

## 1. Why We Need This
Seeded and active students need truthful gender data so the Directress Student Statistics report and every roster show a real breakdown. New inquiries are blank on purpose until the family fills in the admission form — that blank is intentional, not bad data. This spec keeps starter and report data clean without touching the admission flow.

## 2. Who Is Affected
- **All roles** — every roster with gender becomes accurate.
- **Directress / principal** — Student Statistics report shows Male / Female / Non-binary / Prefer not to say, with "Unknown" only while genuine blanks remain.
- **Registrar** — sees the fix list for any active student still missing gender and corrects it one by one.
- **Applicants / families** — no change; they answer gender once inside the admission form.
- **IT Admin** — runs the starter data; no new one-time backfill that guesses gender.

## 3. Business Flow: Today vs After
- **As-is**: This spec said Female / Male / Others and "never blank" — but the live admission flow uses four answers and leaves new inquiries blank on purpose. Seeders already save the four answers. The contradiction confuses anyone reading both specs.
- **To-be**: Starter data saves every seeded student with Male / Female / Non-binary / Prefer not to say. New inquiries save blank on purpose. Active students who somehow remain blank appear under "Unknown" with a fix link for the registrar — nobody's gender is guessed. Reports show the four real slices.
- **Preserved**: Starter-data structure, existing data relationships, all other starter behavior, and the full admission gender flow in `admission-gender-required.md` (inquiry → admission form → registrar approve gate).
- **Exceptions:** None beyond what the admission spec already covers (drafts, walk-ins, failed inquiries, double-submit).

## 4. How It Should Work
1. Load starter data → every seeded student is saved with Male, Female, Non-binary, or Prefer not to say (plus optional free-text detail for the last two).
2. New family inquires → account saves with gender empty (intentional, untouched by this spec).
3. Student submits admission form → gender saves; registrar can correct a typo before approving.
4. Open the Directress Student Statistics report → breakdown shows the four real values; any leftover "Unknown" links to the registrar fix list and hides at zero.

## 5. Look & Feel (UX)
- N/A — backend data quality. Visible effect only: rosters and the Student Statistics + registrar reports show real gender slices, and "Unknown" appears only with a fix link while blanks exist.

## 6. Business Rules
### Must always be true
- Every seeded student has gender = Male, Female, Non-binary, or Prefer not to say (capitalized exactly like this).
- The free-text "describe in your own words" is optional and only for Non-binary / Prefer not to say.
- New pre-admission accounts may be blank — that blank is allowed and never auto-filled by this spec.
- Re-running starter data never blanks a gender and never creates duplicates.
- Gender reports count only answered values; "Unknown" shows only while blanks exist, hides at zero, with a fix link.

### Must never happen
- No new applicant's gender is guessed, defaulted, or randomly assigned.
- The old value "Others" is never saved for new records.
- Starter data must not overwrite a registrar-corrected gender with a random one.
- Existing data relationships must not break.

### Edge cases and what happens then
- Students created before this change with random Male/Female → left as-is; registrar corrects individually when the family reports it (per admission spec).
- Active student still blank → counted in "Unknown" with fix list until the registrar corrects it; no silent backfill.
- Students with no login account → gender still lives on the student record and follows the same four-value rule when seeded.
- Re-running starter data → already-filled rows left untouched.

## 7. Out of Scope
- Admission flow itself (inquiry → form → approve gate) — owned by `admission-gender-required.md`.
- Mass correction campaign for old randomly-assigned genders (fix-on-report only).
- Separate confidential identity / orientation module, preferred name / pronouns fields.
- Changing the official DepEd Male/Female export mapping.
- A parent login or student self-correction screen after submit.
- Admission status mix, starter-routine wiring, changing admission workflow logic, changing report queries beyond the gender slices, changing starter-data structure.

## 8. Success Checks
- [ ] After loading starter data, every seeded student has gender = Male, Female, Non-binary, or Prefer not to say.
- [ ] New inquiry with name + email only saves with empty gender and no crash.
- [ ] Directress Student Statistics report shows the four real slices; "Unknown" only with a fix link, hidden at zero.
- [ ] Re-running starter data does not blank any gender and creates no duplicates.
- [ ] No record contains the old value "Others".

## 9. Open Questions (if any)
None — four-value list and inquiry-blank rule confirmed by implemented `admission-gender-required.md` (2026-10-09). Seeder 47/47/3/3 split kept as-is unless you want it tuned.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: Directress Student Statistics report (`portal/directress/partials/student-stats-results`), registrar reports (`portal/registrar/partials/reports-results`), registrar admission detail (`partials/admissions-show-results`).
- Likely areas of the codebase (from code inspection):
  - `Student.php` — `gender` + `gender_detail` in `$fillable`.
  - `StudentsAndFeesSeeder.php` (~line 237) — already saves 4-value split 47/47/3/3.
  - `StudentScatterSeeder.php` (~line 136) — same 4-value split.
  - Prior data rules: `2026_10_08_000002_add_gender_to_students_table` (old backfill Male/Female + NOT NULL) superseded by `2026_10_09_000001_make_gender_nullable_add_detail_to_students_table` (nullable + widens to 20 for "Prefer not to say" + adds `gender_detail` 100).
  - Admission flow (owned by other spec, do not change here): `StoreAdmissionRequest`, `StudentAdmissionController` (draft + submit), `RegistrarAdmissionController` (approve gate + `updateGender` route), `StudentStatsService`, `DirectressController` demographics grouping (`?? 'Unknown'`).
- Data/records touched: students table (gender + gender_detail for seeded rows only; pre-admission blanks intentional).
- Roles/permissions involved: none new — data only; admission permissions owned by other spec.

## 11. Approval
> Approved by user on 2026-10-09.

## 12. Implementation Note
- Implemented 2026-10-09: seeders preserve registrar-corrected gender on re-run (Slice 1); report Unknown behavior verified role-appropriate as-is, no view change (Slice 2); all 5 success checks user-confirmed pass.
