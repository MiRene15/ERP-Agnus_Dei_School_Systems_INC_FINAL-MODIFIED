# Spec: Admission Submit Redirect Fix

- **Status**: Approved
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09

## 1. Why We Need This

New families fill in the 6-step admission form, press Submit, and land back on an empty form with no explanation. They never reach the requirements upload, so the registrar never sees the application and enrollment stalls. This fix makes Submit keep their work, explain any problem, and take them straight to uploading requirements on success.

## 2. Who Is Affected

- **New-student applicants** — gain a submit that keeps their typed answers, tells them exactly what to fix, and moves them to uploads on success.
- **Registrar** — gains a steady queue of Pending applications with gender shown; approval work does not change.
- **IT admin** — gains clearer error logs for failed submits.
- **Cashier, teachers, librarian, nurse, principal/directress** — no visible change.

## 3. Business Flow: Today vs After

- **As-is**: applicant fills 6 steps → presses Submit Application → page returns to an empty-looking form, no application number, no upload page. Registrar sees nothing new. Family retries or gives up.
- **To-be**:
  1. Applicant fills 6 steps and presses Submit Application.
  2. If something is missing or invalid, the form stays on the step with the problem, all typed answers kept, with a plain message under the field.
  3. If everything is valid, the application becomes Pending, an application number is shown, and the applicant lands directly on the Upload Requirements block of the Application Status screen.
  4. Applicant uploads PSA Birth Certificate, Form 138, and Good Moral Certificate (plus optional files), sees the Uploaded list, then follows the existing Proceed-to-school step. Registrar reviews as today.
- **Preserved**: 6-step draft saving, gender-required-on-submit rule, registrar approve/reject/verify gates, audit trail entries (Draft Saved, Submitted, Requirements Uploaded), file size and type limits, one-application-per-student rule, enrollment-open/closed gate.
- **Exceptions**: transfers follow the same flow; enrollment closed mid-submit shows "Enrollment is closed"; double-click or refresh creates only one Pending record; over-size files are refused with a plain message while other work is kept.

## 4. How It Should Work

1. Applicant completes Steps 1–6 and presses Submit Application once (button shows Submitting… and ignores extra taps).
2. System checks all answers. If anything needs fixing, it opens the right step, keeps every typed answer, and shows a plain message under each problem field.
3. If all answers pass, system saves the student details and marks the application Pending with an application number (ADM-YYYY-XXXXX).
4. System takes the applicant directly to the Upload Requirements block on the Application Status screen and shows "Application submitted! Your application number is … Upload your requirements next."
5. Applicant picks files and presses Upload Selected; uploaded files appear in the Uploaded Documents list with Under Review status.
6. When the three required files are present, Proceed becomes active and follows the existing on-site registrar step.

## 5. Look & Feel (UX)

- Where it lives: existing Admission Application screen (Dashboard / Admission Application) and existing Application Status screen. One primary action per view: Next, Submit Application, Upload Selected.
- What the user sees first: applicants see the step they left off with values intact; on success they see the Upload Requirements block in view (page opens there, block highlighted); registrar sees Pending with gender as today.
- Key states:
  - Default: steps show saved/draft values, current school year pre-filled.
  - Empty: Status with no application shows "No Application Yet" + Apply Now button.
  - Error: red/plain message under the field ("Please choose…", "Pick an elective", "File too large — max 5MB"); page-level errors use the existing red box.
  - Success: green box with application number + "Upload your requirements next."
  - Permission-denied: students only see their own form (as today).
- Plain-language labels; fewer clicks: no re-typing after a failed submit; Step 6 data saved before the final check so nothing typed is lost.

## 6. Business Rules

### Must always be true
- A failed submit keeps every typed answer on screen.
- Every blocked submit names the field in plain words.
- A valid new-student submit creates (or flips draft to) exactly one Pending record and lands on Upload Requirements.
- Empty LRN never blocks a new/Kinder submit.
- Grade 11/12 submit requires an SHS elective choice.
- Double taps, double clicks, or refresh never create a second Pending record.
- Gender remains required at final Submit; drafts can save without it.
- Registrar Approve stays blocked when gender is empty.

### Must never happen
- Family loses typed work after pressing Submit.
- Family sees a blank form with no reason after pressing Submit.
- Two Pending applications exist for the same student from one submit.
- An admission is submitted or approved without gender.
- File uploads bypass the 5MB-per-file and PDF/JPG/PNG limits.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Empty LRN (Kinder/new, no number yet) | Submits fine |
| Grade 11/12, no elective picked | Stays on Step 1 with "Pick an elective" |
| File over 5MB | Refused with plain message; other answers/uploads kept |
| Double-click Submit / refresh during submit | Second action ignored; one Pending only |
| Enrollment closed mid-submit | Blocked with "Enrollment is currently closed" |
| No application yet, opens Status | Shows "No Application Yet" + Apply Now |
| Already Pending, opens Apply | Shows "Application Already Submitted" + View Status link |

## 7. Out of Scope

- Fee assessment, payment, or on-site registrar verification changes.
- Cleanup of old drafts or old gender records.
- New parent login, SMS, or reminder emails.
- Report or export changes.

## 8. Success Checks

- [ ] New-student submit with all valid answers lands directly on Upload Requirements with the application number shown.
- [ ] Submit with a missing required field stays on that step, keeps values, shows a plain message.
- [ ] Kinder/new with empty LRN submits fine.
- [ ] Grade 11/12 with no elective is blocked on Step 1.
- [ ] Double-click Submit creates only one Pending record.
- [ ] Status with nothing yet shows "No Application Yet → Apply Now".
- [ ] New Pending appears in the registrar queue with gender shown.

## 9. Open Questions (if any)

None — edge handling and scope locked in interview; nice-to-haves below parked for follow-up.

Nice-to-haves parked: auto-scroll + highlight the Upload block on landing, upload progress bar, draft-saved indicator.

## 10. Technical Notes (for developers)

*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages: `portal/student/admission-apply.blade.php` (6-step form + step JS), `portal/student/admission-status.blade.php` + `portal/student/partials/admission-status-results.blade.php` (Status + Upload Requirements block).
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `app/Http/Controllers/Portal/StudentAdmissionController@store` — success path must end at `student.admission.status` with upload block in view; failure path must return with input kept and per-field messages for all required fields (today only 4 fields render messages).
  - `app/Http/Controllers/Portal/StudentAdmissionController@create + saveDraft` — form init should prefer flashed input over saved draft so failed submits keep Step 6 values; Step 6 values should persist before final check.
  - `resources/views/portal/student/admission-apply.blade.php` — `submitAll()` submit call from the form root (today `this.$refs.form` on the root may miss — verify and point at the owning form element); single-tap lock + single-use submit reference per `safe-actions-one-submission.md`; show messages for `first_name, last_name, date_of_birth, legacy_lrn` and others, not just the 4 wired today.
  - Status landing: after success, open Status at the Upload Requirements anchor and highlight it; keep `ajaxTable` Status load working when arriving with a success message.
  - Routes: `student.admission.create / store / draft / status / requirements` (see `routes/web.php` ~lines 317–323, role 7 + verified gate).
- Data/records touched: `students` (personal + family + emergency fields), `admissions` (Draft → Pending, `application_number` ADM-YYYY-XXXXX, `draft_data` cleared on submit), `requirements` (Under Review rows), activity log (Submitted / Requirements Uploaded).
- Roles/permissions involved: applicant Role 7 (own records only), registrar Role 2 (queue + approve gate), IT admin (logs). Cashier/teacher/librarian/nurse/directress read-only or untouched.

## 11. Approval

> Approved by user on 2026-10-09.
