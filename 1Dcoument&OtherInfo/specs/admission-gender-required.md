# Spec: Admission Gender Required

- **Status**: Implemented
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09

## 1. Why We Need This

New families cannot apply. Every public inquiry crashes with a database error about empty gender, so no pre-admission account is created — the case of Mikyle Valencia on Oct 9 is one of many. At the same time, gender reports show an 'Unknown' slice because some active students have no gender saved. This spec unblocks applying and puts gender where it belongs: on the admission application the student fills in after login.

## 2. Who Is Affected

- **Applicants / families** — can submit the inquiry again with only first name, last name, and personal email; then answer gender once inside the admission application.
- **Registrar** — application queue flows again; sees gender per applicant and can fix a typo before approving.
- **Directress / principal** — gender-split reports become true again; 'Unknown' disappears when no empty records remain.
- **IT admin** — applies the small data rule change behind the scenes.
- **Cashier, teachers, librarian, nurse** — no visible change.

## 3. Business Flow: Today vs After

- **As-is**: family submits inquiry (first/last/email) → system tries to save a student record with no gender → database rejects it because gender is marked required for every record → family sees "Error: null value in column gender", no account created, nothing for the registrar to work with. Separately, older student records given a random Male/Female during the Oct 8 change may be wrong, and any active student with empty gender lands in 'Unknown' on reports.
- **To-be**:
  1. Family submits inquiry (first/last/email only) → pre-admission account saves with gender left empty.
  2. Student logs in → opens the existing Admission Application → Step 2 Personal Information asks Gender (required to submit; draft can save without it).
  3. Student submits → gender saves on the student record + application becomes Pending.
  4. Registrar opens the application → sees gender, fixes a typo if needed → approves only when gender is present.
  5. Gender reports count Male / Female / Non-binary / Prefer not to say; 'Unknown' shows only while empty records exist and hides at zero.
- **Preserved**: bot trap, send limits, no-duplicate-application rule, one-email-per-applicant rule, credentials + verification link email after signup, document checks, holds, and locked-school-year rules that gate approval.
- **Exceptions**: transfers and walk-ins follow the same gender rule; in-flight drafts keep their data and only ask for gender; failed inquiries that crashed (nothing saved) simply re-apply.

## 4. How It Should Work

1. Parent submits the public inquiry form with first name, last name, and a fresh personal email.
2. System creates the pre-admission account even though gender is empty, and emails credentials + verification link as today.
3. Student logs in and opens Admission Application.
4. In Step 2 Personal Information, student picks Gender: Male, Female, Non-binary, or Prefer not to say. Picking Non-binary or Prefer not to say shows a short optional box: "Describe in your own words (optional)".
5. Student can save a draft without gender, but final Submit is blocked until gender is chosen.
6. On submit, gender saves on the student record and the application becomes Pending.
7. Registrar opens the application, sees gender, corrects it if the family reports a mistake (in person or by email — there is no parent login), then approves.
8. If gender is somehow still empty, Approve is blocked with a plain message.
9. Gender reports show the four answers with correct counts; any leftover 'Unknown' links to a fix list for the registrar and disappears at zero.

## 5. Look & Feel (UX)

- **Where it lives:** the existing Admission Application screen (Dashboard / Admission Application), Step 2 Personal Information, right after the name and birthdate block. The public inquiry form does not change. One primary action per screen stays: Apply (public), Submit (admission), Approve (registrar).
- **Key states:** default empty shows "Select…"; draft resume shows previously typed data with gender empty; validation error under the field reads "Please choose the option that fits best — Prefer not to say is okay."; success continues to the next step; registrar view shows gender as text with an Edit action; permission-denied: students only see their own form.
- **Plain-language labels:** "Gender *" with options Male, Female, Non-binary, Prefer not to say. No technical terms, no orientation terms in this list.
- **Fewer clicks, less typing:** gender asked once, carried forward; free-text box appears only when needed; current school year and names pre-filled as today.
- **What each role sees first:** applicants see one extra dropdown in a familiar step; registrar sees gender before the Approve button; directress sees clean Male/Female/Non-binary/Prefer-not-to-say slices.

## 6. Business Rules

### Must always be true
- Inquiry saves with first name, last name, and personal email only; it never asks for or blocks on gender.
- Admission Submit requires gender: Male, Female, Non-binary, or Prefer not to say.
- A short free-text "describe in your own words" is optional and only shown for Non-binary / Prefer not to say.
- Draft saves never discard work for missing gender; only final Submit blocks.
- Registrar can view and correct gender before approving.
- Approval is blocked when gender is still empty.
- Gender reports count only answered values; 'Unknown' appears only while empty records exist and hides at zero with a fix list.
- Sexual orientation (e.g. bisexual, gay, lesbian) is never collected in this required gender question; that topic stays with guidance under separate privacy rules.

### Must never happen
- A family sees a database error or a crash when applying.
- Gender is silently guessed, defaulted, or randomly assigned for a new applicant.
- An admission is submitted or approved without gender.
- Orientation terms appear as gender choices.
- A child's gender answer is shown to roles who do not need it beyond the admission queue and aggregate reports.
- Bot protection, duplicate rules, email honesty rules, holds, or locked-year gates are weakened.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Saved draft from before this change (no gender) | Draft kept; gender shows empty on resume; Submit blocked until chosen |
| Old records given random Male/Female on Oct 8 | Left as-is; registrar corrects individually when the student/family reports it in person or by email |
| Failed inquiry that crashed (e.g. Mikyle, nothing saved) | Family re-submits normally once fixed; no hidden auto-creation |
| Registrar walk-in with no personal email | Same gender rule applies; no bypass |
| Double-click Submit / concurrent enrollment | Existing guards stand; no duplicate account or application |
| Active student still has empty gender | Counted in 'Unknown' with a fix list for registrar until corrected |

## 7. Out of Scope

- Mass correction campaign for old randomly-assigned genders (fix-on-report only in v1; campaign is a follow-up).
- Separate confidential identity/orientation module with guidance.
- Preferred name / pronouns fields.
- Changing the official DepEd Male/Female export mapping.
- A parent login or student self-correction screen after submit.
- Changing inquiry email-provider, duplicate, or attempt-limit rules.

## 8. Success Checks

- [ ] New inquiry with first/last/personal email only → account created, no gender crash.
- [ ] Login → Admission Application Step 2 shows Gender dropdown with four choices + optional free-text box.
- [ ] Submit without gender → blocked with plain message ("Prefer not to say is okay").
- [ ] Submit with each choice → Pending application saves; registrar queue shows the chosen gender.
- [ ] Registrar tries Approve with empty gender → blocked; with gender → approves.
- [ ] Directress + registrar gender reports show correct slices; 'Unknown' at zero/hidden.
- [ ] Old saved draft resumes with data intact, only gender empty.

## 9. Open Questions (if any)

None — all decisions made in interview (gender at admission not inquiry; four choices per inclusivity ask with no orientation mixed in; registrar-only correction since no parent portal; Unknown fix included; out-of-scope list closed).

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages: `PromotionalWebsite/inquiry` (unchanged, must not validate gender); `portal/student/admission-apply.blade.php` Step 2 Personal Information (add required Gender + conditional free-text; front-end step array at ~line 415 `2: [...]` gains gender); `portal/student/admission-status`; `portal/registrar/admissions-show` (+ `partials/admissions-show-results` — display + Edit); gender report partials `portal/directress/partials/student-stats-results` + `portal/registrar/partials/reports-results`.
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `app/Http/Controllers/PromotionalWebsite/InquiryController@store` lines 32-75 validation (no gender — keep) + lines 94-110 `Student::create` inside `DB::transaction` (today missing gender → 23502 crash; must succeed with gender empty → requires relaxing `2026_10_08_000002_add_gender_to_students_table` NOT NULL back to nullable for pre-admission stage).
  - `app/Http/Controllers/Portal/StudentAdmissionController@saveDraft` lines 69-98 (add optional gender + gender_detail to draft rules, normalize, carry in `draft_data`) + `@store` lines 143-179 (add required `gender in:Male,Female,Non-binary,Prefer not to say` + nullable `gender_detail max:100`, then include in `$student->update` lines 185-208).
  - `app/Http/Controllers/Portal/RegistrarAdmissionController@approve` lines 135-227 (add pre-check: if `$admission->student->gender` empty → back with plain error before section/subject validation; add registrar Edit path for `students.gender/gender_detail`).
  - `app/Models/Student.php` `$fillable` already has `gender` (add `gender_detail` if free-text stored on student; else store on admission — decide at build, keep one source).
  - Gender charts: `app/Services/StudentStatsService@studentStatsData` line 36-37 + `app/Http/Controllers/Portal/DirectressController@demographics` line 53 + line ~670-671 second stats block (all `groupBy(fn($s)=>$s->gender ?? 'Unknown')` — extend to group `gender_detail` under Non-binary/Prefer-not-to-say display, keep `?? 'Unknown'` fallback + hide-at-zero + fix-list link in the two report partials above).
  - Seeders `StudentsAndFeesSeeder` + `StudentScatterSeeder` use Male/Female randoms — extend seed pools to include new options at low weight so reports exercise all slices.
- Data/records touched: `students.gender` (nullable at pre-admission, required from admission submit on), optional detail text (one new free-text column or admission field), `admissions` (`draft_data` gains gender keys, `Pending` only when gender present), `enrollments` (Active with gender present → no Unknown), activity log (gender corrections logged).
- Roles/permissions involved: applicants (role 7, own form only), registrar (role 2, view + correct + approve gate), directress/principal (read-only stats), IT admin (data rule change). Cashier/teacher/librarian/nurse untouched.

## 11. Approval

> Approved by user on 2026-10-09.

## 12. Implementation Note

- Implemented 2026-10-09: inquiry unblocked (gender nullable), gender required at admission submit with registrar approve gate, 4-choice Step 2 UI, Unknown hidden at zero with fix link. User confirmed all 7 success checks pass; pending `php artisan migrate` on their DB to apply the nullable rule.
