# Spec: Cashier Fee Rules

- **Status**: Approved
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11

## 1. Why We Need This

Fee rules live in people's heads today. Installments, the full-payment discount, the enrollment due, and library dues at the Cashier window have no single enforced source — so the Cashier improvises, families see raw balances instead of a clear status, and fee lists can't be filtered or saved by school year. This change writes the rules down and enforces them: fixed enrollment line, 10-month installments, full-payment discount, per-term floors, status-first displays, fees saved by school year, and library dues on the same receipt.

## 2. Who Is Affected

- **Cashier (primary)** — collects per enforced rules, including library dues; never takes a below-floor term payment.
- **Families and students** — see Fully Paid / Has Balance + named breakdowns, never raw ledgers.
- **Directress / Principal** — set fees, read the yearly stacked projection chart.
- **Librarian** — overdue fines and dues become payable at Cashier on one shared receipt.
- **Registrar** — reads the 1.5k downpayment state for the enrollment gate (child 2).

## 3. Business Flow: Today vs After

- **As-is**: fees listed without a school-year filter; discounts auto-post to the ledger; receivables/projections/dashboards read-only; Cashier's financial view already sees library dues but can't cleanly receipt them as paid; families see balances.
- **To-be**:
  1. Fees set in a filterable manager, saved by school year, rolled explicitly per year.
  2. Enrollment posts the assessment with the fixed 1.5k enrollment line + tuition lines, offering two paths: full payment (−10% on non-enrollment lines) or 10 equal monthly installments (full price).
  3. Cashier collects per term with the floor enforced (never below the term total), receipting tuition and library dues together.
  4. Family sees status + misc-breakdown table; Directress reads the yearly stacked chart of full vs installment collections.
- **Preserved**: discount auto-post + approval flow, receivables/projection math, receipt + audit rails, rate/press protections, school-year locks.
- **Exceptions**: mid-year enrollees spread remaining full tuition over remaining months; has-balance returnees add clearance to the same confirmation.

## 4. How It Should Work

1. Directress/Registrar opens the fee manager → filters by school year (current pre-filled), searches a fee → edits save under that school year only → explicit Roll Over copies a year forward for review, never silently.
2. At enrollment the assessment posts: 1.5k enrollment line (fixed, never discounted) + tuition lines; family picks Full (−10% on tuition lines, computed once) or Installment (10 equal monthly lines, full price).
3. Cashier opens the payment screen → sees term total, paid-to-date, remaining, and status (Fully Paid / Has Balance) + any library dues as named lines → takes payment at or above the term total → receipt issues covering tuition + library together.
4. Family view: status + misc-breakdown table (each line named with its amount and state), no raw ledger figures.
5. Mid-year enrollee: remaining full tuition ÷ remaining months; the 1.5k line still posts in full.
6. Switching paths after paying: re-compute once, credit prior payments, keep history; refunds reverse discount eligibility on re-assessment.

## 5. Look & Feel (UX)

- Where it lives: existing fee manager (now filterable), existing Cashier payment screen (now floored + status-first), existing family ledger view (now status + table), existing Projections page (now with yearly stacked bar). No new menu. One primary action each: `Save Fees`, `Take Payment`, `View Breakdown`.
- What the user sees first: term total + status, then lines. Library dues appear as named lines ("Overdue: <title> — ₱<fine>"), never as a lump.
- Key states: Fully Paid (green), Has Balance (amber, naming the next term due), Overdue Installment (red, naming the missed month), Receipt Pending (payment recorded, receipt printing).
- Plain labels: Fully Paid, Has Balance, Term Total, Remaining, Library Dues, Roll Over Fees. Amounts formatted uniformly; current school year pre-filled.

## 6. Business Rules

### Must always be true

- 1.5k enrollment line posts on every enrollment, fixed, never discounted, never waived in v1.
- Full payment = 1.5k + 90% of tuition lines, computed once at assessment; installments = 1.5k due at enrollment + remaining tuition ÷ 10 equal monthly lines at full price.
- Every term payment meets or exceeds that term's total; the screen blocks below-floor amounts naming the floor.
- Library dues receipted at Cashier share the single receipt and feed holds; the Librarian view reads the same receipt — one due, one receipt, one lift.
- Fee edits save under the selected school year only; new years start empty until rolled explicitly.
- Overpayments credit the next term; recorded and visible, never silent.

### Must never happen

- Discount applied to installments or to the 1.5k line.
- A below-floor term payment accepted for any reason.
- Silent fee carryover into a new school year.
- The same library due receipted twice (once at Library, once at Cashier).
- Families shown raw ledger balances as their primary figure.
- Prices set or changed inside the payment screen (manager only).

### Edge cases and what happens then

- Mid-year enrollee → remaining full tuition over remaining months; 1.5k posts in full; full-payment −10% still available on the remaining tuition if paid at once.
- Path switch (full↔installment) → single re-computation, prior payments credited in order, history kept; discount granted or removed accordingly, never both.
- Library due recorded while Cashier receipts it → Cashier's receipt wins; Librarian view reflects paid; hold lifts on confirmation.
- Overpayment → auto-credited to the next term with "credited" shown; no cash-back in v1.
- Refund → discount eligibility re-checked on re-assessment; re-issued assessment follows the same rules as new.
- Fee edited mid-year → applies to new assessments only; posted assessments keep their figures with history.

## 7. Out of Scope

- Online payments or auto-debiting installments.
- Per-level custom month counts (uniform 10 in v1).
- Scholarship math beyond the existing discount request flow.
- Re-issuing past-year receipts in new formats.
- Early-bird/sibling stacking, dunning reminders, fee-change audit view (noted for later).

## 8. Success Checks

- [ ] Fee list filters by school year; an edit saves under that year only; a new year starts empty until rolled.
- [ ] Full payment = 1.5k + 90% of tuition lines, computed once.
- [ ] Installment plan = 1.5k at enrollment + 10 equal monthly lines at full price.
- [ ] Below-term-total payment → blocked naming the floor.
- [ ] Library due paid at Cashier → one shared receipt, Librarian view reads paid, hold lifts.
- [ ] Family view shows Fully Paid / Has Balance + named breakdown, never raw balances.
- [ ] Projections show the yearly stacked bar (full vs installment collections).
- [ ] Mid-year enrollee → remaining full tuition over remaining months, 1.5k in full.

## 9. Open Questions (if any)

None — 1.5k fixed inside assessment and excluded from discount, uniform 10 months, library-at-Cashier on shared receipt, status-not-balance all confirmed 2026-10-11.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: fee manager (filter + SY save + Roll Over), Cashier payments + `student-financial` view (+ partials), family ledger view, Projections page (yearly stacked bar), Librarian loan/overdue views (read Cashier receipts).
- Likely areas of the codebase (from read-only inspection):
  - Schedules: `FeeSchedule` per grade-level + school year — add SY filter/save scope + explicit roll-over copy (never silent); keep `assessedTotalsByGradeLevel` one-shot behavior from `page-speed-queries.md`.
  - Assessment: post 1.5k enrollment line + tuition lines; full path = 1.5k + 0.9 × tuition (once); installment path = 1.5k + 10 equal monthly lines; mid-year = remaining tuition ÷ remaining months; path-switch = single re-computation crediting prior payments in order.
  - Collection: `CashierController` payments + `Payment`/`StudentLedger` + `DiscountRequest` auto-post (keep `cashier-discount-auto-apply.md` flow) — add per-term floor guard (block below-floor naming the floor) and shared receipt covering tuition + library lines; reuse press/idempotency guards.
  - Library dues: `LibraryTransaction` overdue + `HoldService` library reason + `LibraryReportService` fines — Cashier receipt marks the due paid (single receipt wins); Librarian views read it; holds lift on confirmation; never double-post.
  - Display: status-first (Fully Paid / Has Balance + next due) + misc-breakdown table in family/Cashier views; remove raw-balance-primary displays; yearly stacked collections chart on Projections (single grouped pass per `cashier-projection-chart.md` §11 lessons, driver-agnostic bucketing).
- Data/records touched: fee schedules (by SY), assessments/installment lines, payments + receipts, ledger balances/credits, library-dues paid flags, hold raise/lift history; posted assessments immutable on later fee edits.
- Roles/permissions involved: directress/registrar (fee setup), cashier (collect), librarian (dues truth, read receipts), families (read status); no permission-model change. Feeds: child-2 gate reads the 1.5k/downpayment state; child-3 hold wording uses Fully Paid / Has Balance.
- Peak-load note: fee-due days — floor checks and totals computed server-side in few passes, queues/lists paginated with counts, receipt writes never block list reads.

## 11. Approval

> Approved by user on 2026-10-11.
