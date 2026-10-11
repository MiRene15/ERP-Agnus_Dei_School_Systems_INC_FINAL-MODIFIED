# Spec: Grade Edit Approval Chain

- **Status**: Approved
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11

## 1. Why We Need This

Locked grades stall today. A teacher asks for a fix, both Registrar and Principal can see the request, but nobody clearly forwards it — so corrections pile up at grading deadlines and miss report cards. This change gives every fix a clear forward path with bulk tools and a timed reopen, so corrections land on time and every step is traceable.

## 2. Who Is Affected

- **Teacher (primary)** — files a fix request with reason + proof, tracks Pending → Forwarded → Decided → Reopened, fixes within the window.
- **Registrar** — triage: checks completeness, forwards good requests to Principal in bulk, returns incomplete ones to the teacher with a note. Never edits grades directly.
- **Principal (sole approver)** — approves or rejects in bulk with a reason; approval reopens the grade until a set date.
- **Students and parents** — see only the corrected grade on report cards and class lists. No new work.
- **IT Admin** — no daily work; steps in only when a reopen fails.

## 3. Business Flow: Today vs After

- **As-is**: Teacher files a grade unlock request → Registrar and Principal each have review lists with batch tools, but there is no required forward step — either side may act, requests stall, unlocks happen ad hoc, CSV rows and bulk unlocks have no per-row trail.
- **To-be**:
  1. Teacher submits (single or CSV file — each row becomes its own ticket) with a saved reason + note + proof.
  2. Registrar triages: Forward to Principal (single or bulk) or Return to Teacher with a note.
  3. Principal Approves or Rejects (single or bulk) with a reason.
  4. Approved grades Reopen until a set date with a visible banner; teacher fixes and submits; the grade re-locks itself and the full history stays.
- **Preserved**: grade locks, who-may-see-which-list, search + school-year filter + batch tools from the hub consistency work, press protection (double-taps make one ticket), audit history of every step.
- **Exceptions**: transfers, late submissions, and corrections after report cards follow the same chain — no side doors. A rejected request may be re-filed once with stronger proof.

## 4. How It Should Work

1. Teacher opens My Classes → taps Request Edit on a locked grade → picks a saved reason (typo, transposed score, missed quiz, wrong raw-score entry, other), adds a short note + proof, submits. CSV upload: each row becomes its own ticket with the same fields.
2. Teacher sees the ticket as Pending with Registrar, with a reference number.
3. Registrar opens the Requests list → opens a ticket → either Forwards to Principal or Returns to Teacher with a note. Bulk: tick many → Forward Selected / Return Selected with one note.
4. Principal opens the Approvals list → sees only Registrar-forwarded tickets → Approves (sets reopen-until date, default 3 school days) or Rejects with a reason. Bulk allowed.
5. Teacher sees Approved → grade shows "Reopened until <date>" → fixes the score → submits → grade re-locks, history shows request → forward → approval → fix.
6. Rejected or returned tickets show the reason and what to fix; teacher may re-file once.
7. Current school year is pre-filled everywhere; queues show counts (Pending / Forwarded / Decided).

## 5. Look & Feel (UX)

- Where it lives: existing screens only — Teacher grade list (`Request Edit`), Registrar Requests list (`Forward to Principal` / `Return to Teacher`), Principal Approvals list (`Approve` / `Reject`). No new menu. One primary action per screen.
- What the user sees first: their queue with counts, then the ticket detail (student, class, grading period, old → new, reason + proof, history).
- Key states: default (queue with counts); empty ("No pending requests — you're caught up"); typing (saved-reason picker + note + proof required); error ("Add a reason and proof first", "CSV row 4: missing score"); success ("Sent to Registrar" / "Forwarded to Principal" / "Reopened until Friday"); permission-denied (a teacher opening another teacher's ticket; Registrar attempting to edit a grade directly).
- Plain-language labels: Request Edit, Forward to Principal, Return to Teacher, Approve, Reject, Reopened until <date>. Fewest clicks: bulk actions need one confirmation; double-taps never create two tickets.
- Saved reason templates (v1): typo, transposed score, missed quiz/exam entry, wrong raw score, recomputation — picking one fills the note starter; teacher can still edit it.

## 6. Business Rules

### Must always be true

- Only the Principal reopens a locked grade, always with an expiry date (default 3 school days).
- Registrar forwards or returns — never edits a grade directly.
- Every step records who, when, and why; the full trail (request → forward → decision → fix) stays viewable by Teacher, Registrar, and Principal.
- One ticket per request: double-taps, double-forwards, and double-approvals create or change exactly one ticket.
- CSV upload creates one ticket per row; each row is approved or returned on its own.
- A reopened grade re-locks itself after the teacher submits the fix or when the expiry passes, whichever comes first.
- Current school year is the default scope; queues never mix years.

### Must never happen

- A teacher approves their own request.
- A grade stays open with no expiry date.
- A grade changes without a ticket (no silent overwrites, no ad-hoc unlocks).
- A rejected or returned ticket disappears — it stays with its reason.
- Bulk actions approve tickets from more than one school year in a single tap.

### Edge cases and what happens then

- CSV with 1 bad row → good rows proceed as tickets; the bad row returns to the teacher naming the row and the missing field.
- Double-tap Submit / double Forward / double Approve → single ticket or single decision; the screen confirms "Already sent — showing the existing ticket."
- Principal approves while the teacher is mid-edit → the single timed window wins; a second approval shows "Already reopened until <date>."
- Expiry passes with no fix → grade re-locks; teacher files a fresh ticket (no silent auto-extend).
- Rejected ticket re-filed → linked to the original so Registrar/Principal see the prior reason + new proof; only one re-file per rejection.
- Grading-deadline rush → bulk forward/approve with one shared note; queues stay countable and each ticket keeps its own history.
- Teacher leaves / class reassigned mid-chain → ticket follows the class; the new teacher sees the history and continues within the same window.

## 7. Out of Scope

- Auto-approval of any request.
- Editing a locked grade without a ticket.
- Text-message or push notices (hub + email only in v1).
- Different approval chains per subject (one chain for all subjects in v1).
- Reopen-expiry reminders and approval-time stats (noted for later).

## 8. Success Checks

- [ ] Teacher files a fix → it shows as Pending with Registrar with a reference number.
- [ ] Registrar forwards 3 tickets at once → all 3 reach the Principal's list.
- [ ] Principal approves 1 and rejects 1 with a reason → the teacher sees "Reopened until <date>" on one and the reject reason on the other.
- [ ] Approved grade is fixed and submitted → it re-locks and the history shows request → forward → approval → fix.
- [ ] Double-tap Submit makes exactly one ticket.
- [ ] CSV with 1 bad row → good rows move forward, the bad row returns naming the row and problem.
- [ ] Registrar has no way to edit the grade directly — only Forward or Return.
- [ ] Saved reason picker offers the 5 starters and fills the note.

## 9. Open Questions (if any)

None — strict Principal-only reopen confirmed; saved reason templates confirmed in v1.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages:
  - Teacher: `portal/teacher/grade-unlocks/index.blade.php` (request form + ticket list), `portal/teacher/partials/computed-grades-results.blade.php` (Locked → request link), teacher dashboard card `portal/teacher/partials/dashboard-results.blade.php`.
  - Registrar: `portal/registrar/grade-unlocks/index.blade.php` + `portal/registrar/grade-fee.blade.php` (triage queue, forward/return + bulk), `portal/registrar/partials/grade-unlocks-results.blade.php`, dashboard card.
  - Principal: `portal/principal/approvals.blade.php` (grade-edit view — decision queue + bulk), dashboard pending count `portal/principal/partials/dashboard-results.blade.php`, badge counts in `PrincipalController@approvals` / `RegistrarController`.
- Likely areas of the codebase (from read-only inspection):
  - Ticket store: `grade_unlock_requests` table (`2026_09_30_000003_create_grade_unlock_requests_table.php`); add forward-step fields (status chain pending → forwarded → decided, forwarded-by/at, decided-by/at, reopen-until, return/reject reason, parent link for re-files, per-row CSV source) — keep existing approve/reject/batch behavior in `GradeUnlockController` (`reviewIndex`, `approve`, `reject`, `batchApprove`, `batchReject`, `teacherIndex`, `teacherStore`) and extend with forward/return + bulk-forward (new status, not parallel approve); validation in `BatchGradeUnlockRequest` + teacher store request (reason template + note + proof required, CSV row rules).
  - Routes in `routes/web.php:168-181` (registrar + principal grade-unlocks approve/reject/batch) and `:259-260` (teacher index/store) — add forward/return + bulk-forward alongside, keep principal-scoped names truthful for audit logs; single-ticket guard on double submit (reuse `safe-actions` press protection).
  - Reopen window: approval sets reopen-until (default +3 school days); grade write allowed only inside window; auto re-lock on submit or expiry; banner "Reopened until <date>".
  - Saved reasons: fixed 5-item starter list (typo, transposed score, missed entry, wrong raw score, recomputation) seeding the note field; stored as reason code + free note.
- Data/records touched: grade unlock tickets + history/audit entries, grade records inside the window only; no fee, enrollment, or attendance writes.
- Roles/permissions involved: teacher (own classes only), registrar (forward/return, no direct grade write), principal (approve/reject + reopen), IT admin (failure recovery only). Parent: `grade-edit-hub-consistency.md` (Implemented) — this is a child; it does not reopen the parent.
- Peak-load note: bulk forward/approve paths must stay single-confirmation and keep per-ticket history; queues paginated with counts (reuse `ajaxTable` pause/cancel/kept-list behavior).

## 11. Approval

> Approved by user on 2026-10-11.
