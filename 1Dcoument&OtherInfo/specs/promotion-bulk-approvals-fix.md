# Spec: Promotion Bulk Approvals Fix

- **Status**: Implemented
- **Created**: 2026-10-12
- **Approved by**: user on 2026-10-12 (re-approved same day after Option A reject-reason wording fix)
- **Implemented**: 2026-10-12 — Slice 1 Principal bulk UI + Slice 2 Directress nested-form fix (2 Blade views only, no controller/route/migration changes); all 7 success checks passed.
- **Created**: 2026-10-12
- **Approved by**: user on 2026-10-12

## 1. Why We Need This

At the end of the school year, the Principal must approve hundreds of student promotions one tap at a time — there is no way to tick many and approve them together on the Principal's screen. The Directress has a tick-many box, but it sits inside a broken page layout, so it cannot be trusted at peak time. This change adds a safe tick-many approval to the Principal and fixes the Directress tick-many box, so year-end decisions finish in minutes instead of days, with every decision still traceable.

## 2. Who Is Affected

- **Principal (primary)** — ticks many proposals → approves or rejects them together with one confirmation; keeps single-row buttons for one-offs.
- **Directress** — ticks many Principal-approved proposals → signs off and executes them together with one confirmation; keeps the single-row button for one-offs.
- **Registrar** — no new work; sees decisions land faster and rejected proposals return with reasons.
- **Teachers, students, parents** — see only the resulting grade and section next year. No new work.

## 3. Business Flow: Today vs After

- **As-is**: Registrar proposes → Principal opens the Promotions tab and taps Approve or Reject on each row, one by one → Directress opens Sign-off and either taps each row or uses a tick-many box that is wrapped inside a broken page layout (buttons inside buttons, which browsers handle unpredictably).
- **To-be**:
  1. Registrar proposes as today (no change).
  2. Principal opens Approvals → Promotions tab → ticks many (or Select-all) → taps Approve Selected or Reject Selected → confirms once naming the count → approved rows move to the Directress, rejected rows return to the Registrar (who may re-propose; each decision stays in the activity trail).
  3. Directress opens Promotion Sign-off → ticks many (or Select-all) → taps Sign off Selected & Execute → confirms once → each row executes in its own safe step; already-moved rows are skipped, problem rows are named.
- **Preserved**: approve-then-execute order (nothing executes before Principal approval; Directress only sees Principal-approved rows), school-year locks, holds checks, per-row history of who decided what and when, press protection (double-taps decide once).
- **Exceptions**: a student with unpaid dues, library or clinic holds, or a locked school year is refused with a plain message naming the problem; a student who transfers or withdraws mid-batch is excluded and flagged for a single follow-up proposal.

## 4. How It Should Work

1. Principal opens Approvals → Promotions tab → sees the waiting list with a checkbox on each row and a Select-all box at the top.
2. Principal ticks 3 rows → taps Approve Selected → a confirmation names the count ("Approve 3 selected?") → confirms → sees "Bulk approved: 3 sent to the Directress for sign-off."
3. Principal ticks 1 row → taps Reject Selected → confirms → the row leaves the Principal queue and the rejection is recorded in the activity trail; the Registrar may re-propose.
4. Tapping Approve Selected with nothing ticked shows "Select at least one proposal first" — nothing happens.
5. Directress opens Promotion Sign-off → ticks rows → taps Sign off Selected & Execute → confirms once → sees "Signed off and executed 3 students. 1 skipped (already moved)."
6. Single-row Approve / Reject / Sign off buttons keep working exactly as today for one-offs.
7. Current school year is pre-selected everywhere; the list never mixes years in one bulk tap.

## 5. Look & Feel (UX)

- Where it lives: existing pages only, no new menu. Principal: Approvals hub → Promotions tab. Directress: Promotion Sign-off page. One primary action per step: Approve Selected / Reject Selected (Principal), Sign off Selected & Execute (Directress).
- What the user sees first: the waiting list with counts, then a checkbox column, a Select-all box, and the bulk buttons above the table.
- Key states: default (list with counts); empty ("No proposals waiting for approval" / "Nothing waiting for sign-off — you're caught up"); typing/confirming (one confirmation naming the count); success ("Bulk approved: 3…" / "Signed off and executed 3…"); error ("Select at least one first", "Section full — placed in Section B", "Row: holds must be cleared first"); permission-denied (Registrar opening an approval button sees no action — read-only).
- Plain-language labels: Select all, Approve Selected, Reject Selected, Sign off Selected & Execute. Fewer clicks: one confirmation per bulk action; Select-all ticks only the rows on screen in the current year.
- Per role: Principal sees the proposed action + grades summary first; Directress sees the Registrar → Principal chain + execute warning first ("Signing off executes immediately").

## 6. Business Rules

### Must always be true

- Nothing executes before Principal approval, and Directress sign-off only touches Principal-approved rows.
- One bulk tap touches one school year only.
- Already-decided or already-moved rows are skipped and counted ("2 skipped — already decided"), never double-decided or double-moved.
- Every bulk row keeps its own history entry (who, when, bulk or single).
- Holds (library, clinic, finance) and locked school years always refuse with a named reason; good rows still proceed.
- Double-taps on Approve, Reject, or Sign off decide exactly once.

### Must never happen

- Execution from the Principal's screen.
- A bulk tap mixing two school years.
- A section ending above 30 active students because of a bulk execution.
- A bulk decision with no per-row trail.
- Silent skipping — every skipped or failed row is counted and named.

### Edge cases and what happens then

- Principal approves part of a batch → approved rows move to Directress; rejected rows return to Registrar (re-proposable); counts reconcile on screen.
- Student has holds → that row is refused with "holds must be cleared first"; the rest execute.
- School year locked mid-batch → affected rows refuse with "school year is locked"; the rest execute.
- Double-tap Approve / Sign off → single decision; the screen confirms the existing decision.
- Student transfers or withdraws mid-batch → excluded, flagged "moved — file a single proposal for this student."
- Connection lost mid-execution → re-running the same bulk skips already-moved students — never double-promotes.

## 7. Out of Scope

- Auto-approval of any proposal.
- Per-level cap tuning (uniform 30 stays).
- Manual drag-and-drop movement between sections.
- Text-message or push notices.
- Per-grade Select-all, grades column on the Directress page, expiry reminders (noted for later).

## 8. Success Checks

- [ ] Principal ticks 3 proposals → Approve Selected → all 3 appear on the Directress Sign-off list.
- [ ] Principal ticks 1 → Reject Selected → confirms → the row leaves the Principal queue and the rejection is recorded in the activity trail; Registrar may re-propose.
- [ ] Directress ticks 3 → Sign off Selected & Execute → all 3 execute; message names executed + skipped counts.
- [ ] Approve Selected with nothing ticked → "Select at least one first", nothing happens.
- [ ] Double-tap Approve or Sign off makes exactly one decision per row.
- [ ] Empty queues read "No proposals waiting" / "Nothing waiting for sign-off."
- [ ] A student with holds is refused with a named message while the rest of the batch succeeds.

## 9. Open Questions (if any)

None.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages:
  - Principal: `resources/views/portal/principal/approvals.blade.php` (Promotions tab — add checkbox column + Select-all + bulk bar; single-row forms stay).
  - Directress: `resources/views/portal/directress/promotion/index.blade.php` (fix nested-form: single-row sign-off buttons must not sit inside the bulk `<form>`; keep Select-all + bulk bar).
- Likely areas of the codebase (files, routes, tables) — fill from code inspection:
  - Controller `app/Http/Controllers/Portal/PromotionWorkflowController.php`: `principalBulkApprove`, `principalBulkReject`, `directressBulkSignoff` already exist — reuse as-is; no new write path expected. Single-row `principalApprove`, `principalReject`, `directressSignoff` untouched.
  - Routes in `routes/web.php`: `principal.promotion.bulk-approve`, `principal.promotion.bulk-reject`, `directress.promotion.bulk-signoff` already registered — wire the new Principal bulk form to the existing routes; fix Directress markup without touching routes.
  - Pattern to copy: Grade Edit tab in the same Principal hub view (bulk form + `confirmBatchUnlocks`-style count confirmation + select-all toggle) and the press-protection behavior from the safe-actions work.
  - Execution stays per-row inside its own transaction with resume-safe skip (`STATUS_EXECUTED` skipped on re-run); holds check via `HoldService`, locked-year guard via `school_year_locked`, audit via `log_activity` per row.
- Data/records touched: promotion proposals (status chain proposed → principal-approved → executed, decided-by/at stamps); enrollments/sections on Directress execution only; audit/activity rows per decision.
- Roles/permissions involved: principal (bulk approve/reject), directress (bulk sign-off + execute), registrar read-only on these queues. No permission-model change.
- Parent spec: `1Dcoument&OtherInfo/specs/promotion-section-capacity.md` (Implemented) — this is a child fix; it does not reopen the parent.

## 11. Approval

> Approved by user on 2026-10-12.
>
> Re-opened 2026-10-12 (Option A spec-gap decision): bulk/single reject stays reasonless in v1 (mirrors single-reject behavior; a shared reject-reason box is deferred to a follow-up spec). Re-approved by user on 2026-10-12.
