# Spec: Cashier Discount Auto-Apply

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — shared nine-list presets, approval auto-posts to ledger in one transaction, payment states Type-N% locked with no manual options (pending note included), Apply kept as idempotent backlog fallback, Requests copy + toggles intact; verified visually by user (all 9 checks + toggle consistency pass).

## 1. Why We Need This
Discounts live in three places that disagree: the request form offers one set of percentages, the payment screen offers another, and an approved discount waits for a cashier to remember to click Apply before the family sees it. One connected flow — request, approve once, saved by itself, stated at payment — means no forgotten applications and no counter-side discounts outside approvals.

## 2. Who Is Affected
* **Cashier** — only counter reader; sees the approved discount stated with its percentage, never types one in.
* **Directress** — approves once; her approval is the posting, no follow-up needed.
* **Parents/students** — correct balance first try, receipt states the discount and its percentage.
* **Registrar** — queues and labels read the same four types as today.
* Untouched: principal, librarian, nurse, teachers.

## 3. Business Flow: Today vs After
- **As-is**: Request (5/10/15/20/30% picks) → directress approves → waits in Discounts to Apply → cashier clicks Apply → payment shows type + amount (no %). Separately, payment offers manual 0/30/50/100% as `other` with no approval.
- **To-be**:
  1. Request offers nine picks: 5, 10, 20, 25, 30, 40, 50, 75, 100%.
  2. Directress approves → discount saves itself to the family ledger at once.
  3. Payment states the discount with its percentage (e.g. `Sibling — 25% (−₱X)`) and computes the balance. No discount buttons at payment.
- **Preserved**: Approval step itself, approver identity in the audit trail, peso math (`min(amount, assessed)`, balance recompute, clearance refresh), admission auto Honor 10% / Sibling 5% / ESC-waives-tuition, receipts.
- **Exceptions**: A family paying while approval is still pending pays the non-discounted balance; approving later drops the balance (receipt reprint on request). Pre-change approved-but-unapplied items use the old Apply button once.

## 4. How It Should Work
1. Cashier (or requester) picks a type (ESC, Honor, Sibling, Other) and a quick percent from the nine; amount fills itself from assessed.
2. Directress approves → the ledger carries the discount immediately with an audit note naming the approver.
3. Cashier opens payment → the discount line reads `Type — N% (−₱X)`, locked, and the balance reflects it.
4. With no discount the line reads `No discount` (or `No discount — approval pending` when a request is awaiting decision).
5. Old backlog: any request approved before this change is applied once with the existing Apply button.

## 5. Look & Feel (UX)
- Request screen: nine quick % buttons in one row (wrapping on small screens), same type dropdown as today. One primary action: Send to Directress.
- Payment screen: zero discount choices. One read-only line: `Type — N% (−₱X)` or `No discount`. Same locked styling as today's applied state, plus the percentage.
- Key states: default (no discount), approved-applied (stated with %), pending (stated as pending, full balance due), 100%-waived (balance zero, same confirm as today), error/denied unchanged, dark mode unchanged.
- Plain language everywhere: `Honor` (not Honors), `ESC Grant`, `Sibling`, `Other`; percentages as plain `25%`.

## 6. Business Rules
### Must always be true
- The nine picks are exactly 5, 10, 20, 25, 30, 40, 50, 75, 100 — in both the request form and any shared list, never two lists again.
- The four types are exactly ESC, Honor, Sibling, Other — keys and meaning unchanged.
- Approving posts to the ledger in the same moment: type, amount capped at assessed, balance recomputed, clearance refreshed, request marked applied, audit names the approver.
- Payment always states an applied discount as `Type — N% (−₱X)` where N rounds from amount ÷ assessed.
- 100% always means the full assessed amount, through approval only.

### Must never happen
- The payment screen must never offer manual discount buttons or a typed-in percent again.
- The counter must never post, edit, or delete a discount — only the approval does.
- A discount must never apply twice to the same ledger (approval of an already-applied request reports `Already applied`, no second posting).
- 15% must never appear in quick-picks again — but in-flight 15% requests and ledgers keep working untouched.
- Approval must never skip the audit note naming the approver.

### Edge cases and what happens then
- Pending at counter → full balance due; on approval the balance drops by itself; reprint the receipt if the family asks.
- Backlog approved-but-unapplied → old Apply works once; already-applied shows `Already applied` info instead of an error.
- Saved page from before → refresh shows new picks/display; nothing to reconcile.
- Wide-open 100% ESC → allowed, approver-authorized by construction; the audit shows who approved.

## 7. Out of Scope
- Changing who may request or approve; new discount types or percentages beyond the nine.
- Changing peso math, receipts layout beyond the % addition, balances, clearance rules.
- Blocking full payment while approval is pending; backfilling old rows.
- Any other role's screens.

## 8. Success Checks
- [ ] Request form shows exactly the nine picks; 15% is gone from picks.
- [ ] Both screens draw from one shared percent list (inspect: one source, two users).
- [ ] Approving posts to the ledger at once: type, amount, balance, clearance, applied status, audit with approver name.
- [ ] Payment shows `Type — N% (−₱X)` locked; no discount buttons anywhere at payment.
- [ ] No-discount ledger shows `No discount`; pending shows the pending note.
- [ ] Approving an already-applied request reports `Already applied`, posts nothing new.
- [ ] Backlog item applies once via old Apply; auto items never appear in the Apply queue.
- [ ] Auto Honor 10% / Sibling 5% / ESC-waiver at payment work exactly as before.
- [ ] Non-cashier roles see no change.

## 9. Open Questions (if any)
None — auto-post on approval, read-only payment with stated %, Apply-as-backlog-fallback, `Honor` kept, pending-pays-full confirmed by you on 2026-10-08.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: cashier Requests hub (New Request form) + processing-payment form; Manage Discounts queue (drains, fallback kept).
- Likely areas (from code inspection): `DiscountRequest::TYPES` gains a sibling `DISCOUNT_PERCENTS` list both views loop; `requests.blade.php:139` picks loop it; `payment-form.blade.php:92-109` manual block replaced with the read-only stated line (keep locked-state styling, add %); `DiscountRequestController::approve` posts via the same ledger math as `CashierController::applyDiscount:794-821` inside one transaction, then marks applied; `applyDiscount` kept, relabeled `Already applied` info when status is already applied; payment locked branch adds the % computation.
- Data/records touched: ledger discount fields + request status through the approval moment only. No new columns, no migration.
- Roles/permissions involved: cashier (reads), directress (approves/posts), registrar (display only). Server-side approval gate unchanged.

## 11. Approval
> Approved by user on 2026-10-08.
