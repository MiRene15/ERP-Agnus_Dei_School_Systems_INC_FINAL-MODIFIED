# Spec: Registrar Admission Checklist Status Visible

- **Status**: Implemented
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09
- **Implemented on**: 2026-10-09 — server-rendered checklist fallback + parent-shell Alpine components (lazy init) + named approval block + calm/idempotent verify paths + fetch one-tap locks + JSON Accept headers. All 7 success checks passed.

## 1. Why We Need This
Registrars open an application to approve it and get stopped by "1 requirement still pending" — but the page does not show which document is pending. The count and the green/gray labels stay blank, so staff must guess or press Verify All without seeing what they verified. This slows enrollment and creates audit risk. After this fix, every document shows its state clearly and approval succeeds once all are verified.

## 2. Who Is Affected
- **Registrar** — sees `2/3 verified`, sees which doc is `Under Review`, verifies 1-by-1 or Verify All with confidence, then approves.
- **Principal / Directress** — no screen change; gains a cleaner queue and safer approvals.
- **IT Admin** — applies a view-only change; no new access rules.
- **Applicants / families** — no change to applying or uploading.

## 3. Business Flow: Today vs After
- **As-is**: registrar opens Application Review → sees Verify All button but blank statuses/count → tries Approve & Enroll → blocked by "1 requirement still pending" → guesses which doc or verifies blindly.
- **To-be**:
  1. Registrar opens Application Review → sees `2/3 verified` and each doc labeled green `Verified` or gray `Under Review`.
  2. Registrar opens (`View`) and verifies the pending doc (1-by-1 `Verify`) or presses `Verify All`.
  3. List updates to `3/3 verified`.
  4. Registrar presses `Approve & Enroll` → enrollment succeeds.
- **Preserved**: all-docs-verified gate before approval; Verify, Verify All, and Approve are still recorded in the activity log; gender → requirements → holds → locked-year → capacity order stays; locked years still block; one-tap double-submit protection stays.
- **Exceptions**: transfers, walk-ins, late uploads, and corrections follow the same list — any `Under Review` doc blocks approval until verified.

## 4. How It Should Work
1. Registrar opens Admissions Queue and clicks an application number (e.g. ADM-2026-00164).
2. In the left column under Applicant Information, the Requirements Checklist shows `X/3 verified` in plain text.
3. Each row shows the document name (e.g. Form 138 Report Card), its file link (`View`), and its state (`Verified` in green or `Under Review` in gray) with a `Verify` or `Unverify` button.
4. Registrar clicks `Verify` on the pending row (or `Verify All` at the top) — the row turns green and the count updates.
5. Registrar fills Assign Section + Assign Subjects and clicks `Approve & Enroll` — approval succeeds when all show `Verified`.
6. If 1 is still pending, the block message names it (e.g. "Form 138 (Report Card) is still Under Review — 2/3 verified").

## 5. Look & Feel (UX)
- Where it lives (screen/menu) and the one primary action: same card, same place — Application Review detail, left column. Primary action stays `Approve & Enroll`.
- Key states: default list with counts; empty (`No requirements uploaded yet` unchanged, with what-to-do-next); error (approval block names the pending doc); success (green dot + `3/3 verified`); permission-denied unchanged (students see only their own).
- Plain-language labels; fewer clicks; sensible defaults: labels stay `Requirements Checklist`, `Verify All`, `Verify/Unverify`, `View`, `Approve & Enroll`. Current school year context kept.
- What the user sees first, per role if roles see different things: registrar sees the list + count + Verify actions first; other roles see no change.

## 6. Business Rules
### Must always be true
- Every uploaded document shows `Verified` or `Under Review` in plain text even if buttons/scripts fail to load.
- The header always shows the true count (`X/Y verified`) matching the stored states.
- Approval stays blocked while any document is not `Verified`.
- The approval block names the pending document(s), not just the count.
- Verify, Verify All, gender fixes, and Approve/Reject are still recorded with who did it.
- Gate order stays: gender → requirements → holds → locked year → capacity.

### Must never happen
- Approval succeeds with a pending document.
- Verify All hides what was verified — the list must show the result.
- Holds, gender, or locked-year rules are weakened to make approval pass.
- A second tap on Verify/Approve creates a duplicate verification or enrollment.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| No documents uploaded | Keep `No requirements uploaded yet` — approval gate sees 0 pending from docs (other gates still apply) |
| Verify All with nothing pending | Calm note `Already verified` — no error, no duplicate log spam |
| Double-tap Verify / Approve | Second tap ignored; one verification, one enrollment |
| Gender still missing | Gender block shows first with plain message before requirements are re-checked |
| Hold (library/clinic/finance) present | Holds block shows after requirements clear, with what-to-clear message |
| Locked school year | Locked-year block shows with year named; no enrollment into it |
| Full subject capacity | Capacity message names the subject + limit; no partial enrollment |

## 7. Out of Scope
- Changing which documents are required or the applicant upload flow.
- Approval email wording or resend rules.
- Bulk verify across multiple applications.
- New reports, filters, or mass correction campaigns.
- Preferred name/pronouns or separate identity modules.

## 8. Success Checks
- [ ] Open ADM-2026-00164 → header shows `2/3 verified` and the pending row shows `Under Review` + `Verify`.
- [ ] Click `View` on each row → the correct file opens in a new tab.
- [ ] Click `Verify` on the pending row → row turns green, count becomes `3/3 verified`.
- [ ] With 1 pending, Approve is blocked and the message names the document (not just "1 pending").
- [ ] With `3/3 verified` + section + subjects, Approve & Enroll succeeds and the student becomes enrolled.
- [ ] Reload the page with scripts blocked → statuses and count are still readable.
- [ ] Activity log shows who verified and who approved.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
  - `portal/registrar/admissions-show` (shell with AJAX list loader)
  - `portal/registrar/partials/admissions-show-results` (checklist card + approve form)
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `resources/views/portal/registrar/admissions-show.blade.php` line 19 (list loader) + skeleton block
  - `resources/views/portal/registrar/partials/admissions-show-results.blade.php` lines 76-136 (checklist: header count line 81, per-row dot lines 98-104, status text lines 107-109, Verify button lines 120-123, Verify All form lines 83-89, empty state lines 133-135) + JSON data blocks lines 6-14 + page script lines 248-307 (component setup that never runs inside injected HTML)
  - `resources/js/app.js` lines 78-233 (shared list loader using injected HTML; injected scripts do not run)
  - `app/Http/Controllers/Portal/RegistrarAdmissionController.php`: `show()` lines 69-94 (loads student + requirements select), `verifyRequirement()` lines 96-114, `verifyAll()` lines 116-133, `approve()` lines 135-233 (gender gate lines 141-145, requirements gate lines 147-150 to extend with names, holds lines 152-159, locked-year lines 161-164, section/subjects validation lines 166-182, enroll lines 186-207)
  - `routes/web.php` lines 186-194 (registrar admissions index/show/approve/gender/reject/verify-all/resend/verify-requirement)
  - `app/Models/Admission.php` (`requirements()` has-many) + `app/Models/Requirement.php` (fields: admission_id, document_type, file fields, status)
  - `app/helpers.php`: `school_year_locked()` line 57, `log_activity()` line 7, `active_school_year()` line 20
- Data/records touched:
  - `requirements.status` (`Verified` vs `Under Review`) + `document_type` display; `admissions.status` (`Pending` → `Approved By Registrar`); `students.status` → enrolled + student_number; `enrollments` + subject links; activity log rows for verify/verify-all/approve.
- Roles/permissions involved:
  - Registrar (role 2) — only role with Verify/Approve actions on this screen; other roles unchanged.

## 11. Approval
> Approved by user on 2026-10-09.

## 12. Implementation
> Implemented on 2026-10-09: checklist fallback + AJAX-safe Alpine + named block + calm Verify All + one-tap fetch locks + JSON headers. Verified: 7/7 success checks passed.
