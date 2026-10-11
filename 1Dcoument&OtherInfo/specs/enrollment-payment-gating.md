# Spec: Enrollment Payment Gating (Parent)

- **Status**: Approved
- **Created**: 2026-10-11
- **Revision**: 2026-10-11 — per user: skip the inquiry email/button; use the current admission flow; keep the Registrar→Cashier→Registrar double gate + student's-own-email field.
- **Approved by**: user on 2026-10-11 (revised scope)

## 1. Why We Need This

Families get confused about when they are fully in. Today admission ends at Registrar approval with a confirmation email — there is no Cashier step, no second approval, and no clear link between paying and unlocking the full student dashboard. This vision keeps the current admission flow and adds the double gate: admission with requirements → Registrar review → Cashier downpayment → Registrar full approval → institution email → full dashboard. No full access before all gates pass.

## 2. Who Is Affected

- **Families and students (primary)** — one checklist from applied to account-ready; limited dashboard until fully approved.
- **Registrar** — two touches: review (forward to Cashier or return) then full approval after downpayment.
- **Cashier** — confirms the downpayment in the middle; amounts and installment math live in `cashier-fee-rules.md`.
- **IT Admin** — owns the institution email identities and account provisioning.
- **Principal / Directress** — read-only oversight; no new approvals in this vision.

## 3. Business Flow: Today vs After

- **As-is (verified in code)**: public inquiry form + admissions page → family files admission application (draft, submit, upload requirements, check status) → Registrar verifies requirements one by one (or Verify All) → Approve checks gender filled, all documents Verified, school year unlocked → enrollment created, marked Approved By Registrar, confirmation email sent (Resend available). No Cashier step, no downpayment gate, no second approval, no institution-email provisioning.
- **To-be (revised 5-step flow)**: (1) family completes the application on the admissions page + uploads requirements + provides the student's own email → (2) Registrar reviews: complete → forwards to Cashier, incomplete → returns with note → (3) Cashier confirms downpayment → (4) Registrar gives full approval → (5) `firstname.lastname@` institution email sent to the student's email address → full student dashboard unlocks. Before step 5: limited dashboard (checklist + bills only). Walk-ins follow the same steps (details entered at the desk, same application).
- **Preserved**: draft/submit/upload/status on the admissions page, requirement verification (single + Verify All), gender gate, school-year locks, Pending-first queues with counts, honest/recoverable emails from `applicant-email-reliability.md`, rate-limited inquiry and search behavior.
- **Exceptions**: transfers, late enrollments, and rejections-then-refiles follow the same gates as single proposals.

## 4. How It Should Work

1. Family submits the admission application on the admissions page + uploads requirements + enters the student's own email (helper text: student's email, not the parent's).
2. Registrar reviews documents → forwards complete files to Cashier or returns incomplete ones with a note.
3. Cashier confirms the downpayment (1.5k on enrollment; full-vs-installment math in `cashier-fee-rules.md`).
4. Registrar sees payment-confirmed → grants full approval.
5. System provisions `firstname.lastname@`, emails it to the student's email address, and unlocks the full dashboard.
6. Any full-dashboard visit before step 5 redirects to the checklist with the exact next step named.

## 5. Look & Feel (UX)

- Family side: one checklist ("1 Application → 2 Registrar review → 3 Downpayment → 4 Full approval → 5 Account ready") inside the limited dashboard; each step names its owner and next action in plain words.
- Staff side: existing queues only — Registrar review list, Cashier downpayment list, Registrar approval list — current school year pre-filled, counts visible.
- Key states: limited ("Pay ₱1.5k at Cashier to continue" — amount finalized in #4); returned ("Missing birth certificate — upload and resubmit"); denied (full dashboard redirects to checklist); hold (only when a new school year is announced — detail in child 3).
- Plain labels: Submit Application, Forward to Cashier, Confirm Downpayment, Grant Full Approval, Account Ready. One primary action per screen.

## 6. Business Rules

### Must always be true

- No full dashboard before all three: Registrar review + Cashier downpayment + Registrar full approval, with email verified.
- Walk-ins follow the same steps; no shortcut path exists.
- One active institution address per student (numbered suffix on collision, family told plainly).
- Every hand-off records who and when.
- Duplicate applications for the same child merge to one surviving record.
- Returning students with old balances follow the same gates with a "has balance" flag plus Cashier clearance.
- The student's own email is collected at application time and used for the institution-address notice.

### Must never happen

- Full dashboard or institution email before Registrar full approval.
- Two active institution addresses for one student.
- Auto-approval of any step in v1.

### Edge cases and what happens then

- Downpayment unpaid → application waits at Cashier as "awaiting downpayment"; no auto-reject.
- Duplicate application → staff merges; one record survives; history follows the survivor.
- Sibling applications (two children, one parent contact) → separate application per child, student emails distinct.
- Student email bounces/typo'd → Registrar flags "email needs fix" and returns without losing uploads.
- Name collision on institution address → numbered suffix; family informed in plain words.
- Student transfers or withdraws mid-flow → removed from the batch path; any re-entry starts a single fresh proposal.
- School year locks mid-flow → affected steps block with the school-year message; completed steps keep their history.

## 7. Out of Scope (parent level — see children for detail)

- Online payment (in-person Cashier confirmation only in v1).
- Auto-approval, walk-in kiosks, per-grade custom checklists.
- Downpayment/installment amounts and discount math (lives in #4 `cashier-fee-rules.md`; 1.5k on enrollment carried there).
- July–March school year, grading-close timing, hold/clearance rules (live in child 3 `-calendar-holds`).
- Inquiry email + in-application button flow (parked as a follow-up; explicitly out of this revision).

## 8. Success Checks

- [ ] Family submits application + requirements + student email on the admissions page.
- [ ] Registrar review → complete files reach the Cashier queue; incomplete return with a note.
- [ ] Cashier downpayment → application returns to Registrar for full approval.
- [ ] Full approval → institution email arrives at the student's email address and the full dashboard unlocks.
- [ ] Before full approval, any full-dashboard visit redirects to the checklist naming the next step.
- [ ] Walk-in completes the same steps with no shortcut.
- [ ] Duplicate applications merge to one survivor with history intact.

## 9. Open Questions (if any)

None for this revision. Parked follow-up (not in v1): inquiry email + in-email application button flow.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages:
  - Public: `/inquiry` form (unchanged) + `/admissions` page + student admission apply/draft/status/requirements views (`StudentAdmissionController`: create/store/draft/status/uploadRequirements) — add student's-own-email field with helper text.
  - Registrar: `portal/registrar/admissions-index` (Pending-first, counts, search) + `admissions-show` (requirements verify/verify-all, gender fix, approve/reject, resend email) — split today's single approve into review-forward and post-payment full-approval steps.
  - Cashier: downpayment confirmation queue (amounts from #4) feeding back to Registrar.
  - Student: limited vs full dashboard gate (extends `future-year-enrolled-dashboard.md` year-following behavior).
- Likely areas of the codebase (from read-only inspection):
  - Application: existing draft/submit/upload/status + `admissions` table + requirements verification flow — reused; add student-email field (distinct from contact email, validated, used for the institution-address notice in child 2).
  - Review chain: `RegistrarAdmissionController` (index/show/verify/verifyAll/approve/reject/gender/resend) — split today's single approve into review-forward and post-payment full-approval; statuses beyond Pending / Approved By Registrar / Rejected (e.g. awaiting-downpayment, returned) per child 1/2.
  - Emails: extend `applicant-email-reliability.md` guarantees (provable, honest, resendable) to the institution-email notice; collision numbering for `firstname.lastname@`.
  - Gate: dashboard/hold checks (hold display rules per child 3; `HoldService`, school-year locks) — full dashboard requires review + downpayment + full approval + verified email.
- Data/records touched: admissions + requirements, enrollments (created at full approval, not at review), downpayment confirmations (detail in #4), provisioned accounts/addresses, audit entries per hand-off.
- Roles/permissions involved: families (apply, upload, pay, verify), registrar (review + full approval), cashier (downpayment confirm), IT admin (identities/accounts); principal/directress read-only here. No permission-model change.
- Children (dependency order — each independently approvable, one at a time):
  1. `enrollment-payment-gating-review-forward.md` — admissions page + uploads + student email, Registrar review forward/return. Depends on: `applicant-email-reliability.md` (Implemented).
  2. `enrollment-payment-gating-payment-access.md` — Cashier downpayment confirm, Registrar full approval, institution email + provisioning, limited-vs-full dashboard gate. Depends on: child 1 + #4 amounts (`cashier-fee-rules.md`).
  3. `enrollment-payment-gating-calendar-holds.md` — July–March school year, grading close 1 week after exams, holds only when new SY announced, clearance-status removal. Depends on: child 2 (gate wording references hold states).
- Peak-load note: enrollment season is the worst day — inquiry throttling stays, queues paginated with counts, emails queued with resend, no bulk step blocks another queue's reads.

## 11. Approval

> Approved by user on 2026-10-11 (revised scope: current flow + double gate, inquiry parked).
