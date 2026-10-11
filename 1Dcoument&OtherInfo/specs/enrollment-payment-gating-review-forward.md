# Spec: Enrollment Payment Gating — Review Forward

- **Status**: Implemented
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-12 (Step 7 amendment: uploads as Step 7 inside the stepper)
- **Implemented**: 2026-10-12 — Steps 1–7 apply flow with AJAX uploads; forward/return/bulk review actions; queue-count fix; forward transaction fix; dashboard Returned fix; stray-div fix (steps 3+ blank); application-number race fix (23505 on parallel draft creates).
- **Parked follow-up (2026-10-12, user: skip for now)**: auto draft save appears not to persist — uploads work, but form fields are lost on reload (no resumed values, no "saved draft" banner). Suspects: silent fetch failure in `autoSave()` (non-JSON error swallowed by `.catch`), draft-route 419/422, or the model save path. Repro: fill Steps 1–2 → Next (autosaves) → reload → expect resumed values + banner. Fix slice: surface autosave errors inline + verify resume. Does not block child 1's acceptance (submit/forward/return verified).
- **Post-implementation fix (2026-10-12)**: Cashier live search (`/cashier/search`) filtered `enrolled` only, so forwarded accounts never appeared — widened to `Awaiting Downpayment` + admission-state match (§8 check 3).
- **Parent**: `enrollment-payment-gating.md` (child 1 of 3 — builds first, usable alone)
- **Revision**: 2026-10-12 — Step 7 amendment (user): uploads move inside the application stepper as Step 7 (no page reload); status-page uploads block unchanged for Returned re-uploads. Pending re-approval.

## 1. Why We Need This

Applications stall between "submitted" and "reviewed" today. Families upload requirements but can't tell what's missing, and Registrar has one approve button that does everything at once — so files that need a Cashier step can't move forward in stages. This child adds the first half of the double gate on the current flow: a student's-own-email field, per-file verification with a clear return path, and a review step that forwards complete files to Cashier instead of approving outright.

## 2. Who Is Affected

- **Families (primary)** — apply on the admissions page, upload requirements, get back a named list of what's missing instead of silence.
- **Registrar** — verifies files, forwards complete ones to Cashier, returns incomplete ones with a note. No direct enrollment creation at this step.
- **Front-desk staff** — enter walk-in details as applications in the same flow; no separate path.
- **Cashier** — receives only complete, forwarded files (acts in child 2). IT Admin, Principal, Directress untouched in this child.

## 3. Business Flow: Today vs After

- **As-is**: family applies (draft, submit, upload, status) → Registrar verifies (single or Verify All) → Approve checks gender, all-Verified docs, unlocked year → enrollment created + Approved By Registrar + confirmation email.
- **To-be**: family applies + uploads + student's own email → Registrar verifies → complete → **Forward to Cashier** (application waits as awaiting-downpayment, no enrollment yet); incomplete → **Return with note** naming missing files. Enrollment creation and full approval move to child 2.
- **Preserved**: draft/submit/upload/status, per-file Verify + Verify All, gender gate (filled before forward), school-year locks, Pending-first queues with counts, resendable emails, rate limits.
- **Exceptions**: siblings file per-child applications sharing one parent contact; returning students with old balances carry a "has balance" flag into the Cashier queue.

## 4. How It Should Work

1. Family opens the admissions page → fills details Steps 1–6 → enters the student's own email (helper text: "Use the student's email, not the parent's — the school account notice goes here") → completes Step 7 Requirements Uploads inside the same stepper (uploads attach to the draft without a page reload) → submits on Step 7 (blocked until required uploads present).
2. Family sees status: Received → Under Review → (Forwarded to Cashier / Returned: <missing files>).
3. Registrar opens the review queue (current school year pre-filled, counts visible) → opens a file → verifies each requirement or Verify All → fixes gender if empty.
4. Complete file → Forward to Cashier (single or bulk with one note). Incomplete → Return with note naming each missing file; uploads already received are kept.
5. Walk-in: desk staff file the same application with the family present, same email rule, same queue.

## 5. Look & Feel (UX)

- Where it lives: existing admissions page + existing Registrar review screens — no new menu. One primary action per screen: family `Submit Application`, Registrar `Forward to Cashier` / `Return with Note`.
- What the user sees first: application stepper Steps 1–7 (Step 7 is the upload checklist) with email field on Step 2; Registrar queue with counts then file detail (requirements with per-file status, gender, history).
- Key states: default (queue with counts); empty ("No pending reviews — you're caught up"); typing (submit blocked until uploads + student email valid); returned ("Missing: birth certificate, report card — upload and resubmit; your other files are kept"); success ("Sent to Cashier" / "Returned with note"); denied (forward with unverified docs is blocked, gender-missing is blocked).
- Plain labels: Submit Application, Forward to Cashier, Return with Note, Awaiting Downpayment. Fewest clicks: Verify All + bulk forward need one confirmation.

## 6. Business Rules

### Must always be true

- One application per child per school year; duplicates merge to one survivor, history follows it.
- Submit blocked until required uploads are present and the student email looks valid and differs from a pure parent-contact pattern only by family confirmation (warn, don't block, if identical — family confirms "same address").
- Forward blocked until all documents are Verified and gender is filled.
- Returned files keep already-verified uploads; family only supplies what's named.
- Every forward/return records who and when with the note.
- Sibling applications: separate record per child; student emails distinct; shared parent contact allowed.

### Must never happen

- Forward with unverified documents or missing gender.
- Enrollment created at review-forward (creation happens at full approval in child 2).
- A returned file losing already-uploaded documents.
- Two live applications for one child in one school year.
- Auto-forward of any file in v1.

### Edge cases and what happens then

- Duplicate applications for the same child → merge to survivor; uploads and notes consolidate; loser archived.
- Student email bounces or is typo'd → Registrar returns with "email needs fix"; uploads kept.
- Family confirms the student email equals the parent contact (shared address) → accepted with a recorded confirmation; the notice still goes there in child 2.
- Transfer/withdrawal mid-review → removed from queue; re-entry is a fresh single application.
- School year locks mid-review → forward blocks with the school-year message; verified work is kept.

## 7. Out of Scope

- Cashier downpayment, full approval, institution email, dashboard gate (child 2).
- Calendar, holds, clearance-status removal (child 3).
- Online payment, auto-approval or auto-forward, kiosks, per-grade checklists.
- Inquiry email/button flow (parked follow-up).

## 8. Success Checks

- [ ] Family submits with uploads + student email → status shows Under Review.
- [ ] Submit with a missing required file → blocked naming the file.
- [ ] Registrar Verifies All → Forwards → file shows Awaiting Downpayment and reaches the Cashier queue (queue visible even before child 2 ships).
- [ ] Incomplete file → returns naming missing files; re-upload keeps prior verified files.
- [ ] Forward with an unverified doc or missing gender → blocked with the reason.
- [ ] Duplicate applications → one survivor, uploads consolidated.
- [ ] Sibling pair → two records, distinct student emails, one shared parent contact.

## 9. Open Questions (if any)

None — review-forward scope, student-email rule (warn-don't-block on identical), and sibling handling all confirmed.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: student admission apply/draft/status/requirements views; `portal/registrar/admissions-index` (+ partials) and `admissions-show` (+ partials) review screens.
- Likely areas of the codebase (from read-only inspection):
  - Application: `StudentAdmissionController` (create/store/draft/status/uploadRequirements) + `admissions` table — add student-email field (validated format, warn-and-confirm if equal to contact email); keep draft/submit/upload behavior; submit requires all required uploads.
  - Review: `RegistrarAdmissionController` (index/show/verifyRequirement/verifyAll/updateGender/approve/reject/resendApprovalEmail) — add forward-to-Cashier (+ bulk) and return-with-note; forward requires all-Verified + gender (reuse existing gender gate); statuses add awaiting-downpayment / returned; today's approve (enrollment creation + Approved By Registrar + confirmation email) is NOT called from this step — it moves to child 2's full approval.
  - Queues: keep Pending-first ordering, pending/approved counts, `throttle:search` behavior, `ajaxTable` pause/cancel/kept-list patterns; current school year pre-filled.
- Data/records touched: admissions + requirements + student-email field, forward/return history + audit entries; no enrollment creation, no ledger/payment writes, no account provisioning in this child.
- Roles/permissions involved: families (apply/upload), front-desk (same application entry), registrar (verify/forward/return); cashier read-receives; no permission-model change. Parent: `enrollment-payment-gating.md` (Approved revised scope).
- Peak-load note: enrollment season — paginated queues with counts, single-confirmation bulk, uploads stream with kept-list behavior; review reads never block family submissions.

## 11. Approval

> Approved by user on 2026-10-11.
