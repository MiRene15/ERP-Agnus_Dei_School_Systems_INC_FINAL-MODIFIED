# Spec: Cashier Requests Hub

- **Status**: Implemented
- **Created**: 2026-10-06
- **Approved by**: user on 2026-10-06
- **Implemented**: 2026-10-06

## 1. Why We Need This

The cashier's money-adjustment work lives in three separate sidebar entries: Discounts, Discount Requests, and Refunds. During fee week the cashier bounces between them clearing queues — apply an approved discount here, check a request there, release a refund somewhere else. One page with a three-way switch keeps the whole queue in one place with nothing to relearn, exactly like the Collections/Receivables toggle did for reports.

## 2. Who Is Affected

- **Cashier** — the only one who sees the new page. Same lists, same buttons, one switch.
- **Registrar** — uses the same Discount Requests list today and keeps it exactly as is: same address, same list, same request form. This spec changes nothing for them.
- **Directress** — approval screens and rules untouched; approvals and rejections work as today.
- **Parents and students** — indirectly, through faster clearing of discounts and refunds. Nothing they see changes.

## 3. Business Flow: Today vs After

- **As-is**: sidebar holds Discounts, Discount Requests, and Refunds as three entries. Each opens its own page; moving between queues means navigating back and forth.
- **To-be**:
  1. Cashier opens one **Requests** page — a three-way pill switch sits at the top: Discounts, Discount Requests, Refunds. Discounts shows first.
  2. Each tab carries a small count of what's waiting (approved-to-apply, pending requests, unreleased refunds); empty queues show no badge.
  3. Cashier taps a tab — that queue appears with its own filters and buttons, exactly as today.
  4. Cashier reloads or goes back — the open tab is kept (the page address remembers it).
- **Preserved**: every filter, button, and audit trail on all three sides; the two-step discount chain (request with proof → Directress approves → cashier applies); the refund approve-then-release split; registrar's standalone list.
- **Preserved (already-proven behaviors carry over untouched)**: the search throttle stays exactly where it is today (discounts list; refunds and requests keep their current state — extending it is a rate-limit-spec decision, not this one); calm searching stays exactly where it is (discounts list only — no retrofit here); one-tap locks and single-submission markers keep working on apply, release, and request because grouping moves forms without changing how they submit (native submits + CSRF preserved); date limits don't apply (none of the three pages has date inputs); where a tab's search keeps its list on screen while typing, the same named-list message + dimming from the cashier slice applies.
- **Exceptions**: transfers, corrections, and late cases follow the same rules as today — grouping changes where lists live, never what they do.

## 4. How It Should Work

1. Requests opens on Discounts: approved-ready-to-apply table first, then the ledger search below, as today.
2. Tapping Discount Requests shows the New Request form plus the request list with its statuses, as today.
3. Tapping Refunds shows payouts awaiting release plus released history, as today.
4. Flipping between tabs never loses the other tabs' filters or scroll position within the visit.
5. Applying a discount, releasing a payout, or filing a request behaves exactly as it does on the standalone pages today, including confirmations and success messages.

## 5. Look & Feel (UX)

- **Where it lives:** one new Requests page replacing three sidebar entries with one (in the Discounts position). The one job of the switch: change queues.
- **The one primary action:** pick a queue, act on it. Same buttons, same places, same wording.
- **Key states:** default Discounts tab; per-tab empty states as today ("nothing awaiting release", "no approved discounts", empty request list); error keeps the last good tab plus a way back; dark mode and narrow screens as today.
- **Badges:** small pending counts on tabs, hidden when zero — a morning checklist at a glance, reusing numbers the dashboard already computes plus one small pending-requests count.
- **What the cashier sees first:** the apply queue with its badge — the highest money consequence first.

## 6. Business Rules

### Must always be true
- The open tab survives reload and back-button (page address remembers it).
- Each tab keeps its own filters, results, and actions; totals and queues never mix.
- Badges always match the underlying lists (stale badges are worse than none).
- Registrar's standalone Discount Requests page works byte-for-byte as today.
- Every apply/release/request writes the same audit trail as today.
- Proven behaviors survive the move: throttle/countdown on the discounts list, calm searching where it exists, single-effect guarded submits on every form.

### Must never happen
- Switching tabs loses another tab's picked filters.
- An action lands on the wrong queue's data (apply hitting a released refund, and the like).
- Registrar, Directress, or any other role gains or loses access to anything.
- A pending count shows while its list is empty, or hides while work waits.
- Dark mode or narrow screens break the switch or hide a badge.
- No new throttle added to refunds/requests in this spec; no search-behavior retrofit promised.
- No form rewritten as anything but a native submit with CSRF (non-native rewrites would silently drop guard/marker coverage).

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Mid-load flip | Latest tap wins; no mixed content |
| Action confirm open, tab flipped underneath | Confirm completes against its own record, then the current tab refreshes |
| All three queues empty | Three quiet tabs, no badges — a clean morning |
| Registrar opens their requests link | Standalone page, exactly as today — the hub is invisible to them |
| Session ends mid-work | Sign back in; the address-kept tab resumes where it was |

## 7. Out of Scope
- The approval workflow (Directress approve/reject screens and rules).
- Discount computation and refund math.
- Roles and permissions — who may apply, release, or request is unchanged.
- API routes, extra exports, reminders, projections.
- Smart-default tab and registrar hub copy (recorded as future ideas).
- Extending the search throttle to refunds/requests, retrofitting calm search or stale naming beyond the kept-list check, rewriting any form as a non-native submit.

## 8. Success Checks
- [ ] Open Requests — Discounts shows with its apply table; each tap shows the right queue with its own filters.
- [ ] Sit on Refunds — reload keeps Refunds open; back-button behaves.
- [ ] Badges match their lists; zero queues show no badge (cross-check against dashboard counts).
- [ ] Registrar's standalone page loads and works exactly as before.
- [ ] Apply/release/request paths confirmed as the same untouched endpoints (code review — no live money moved to test navigation).
- [ ] Discounts search still throttles with countdown (same route, same middleware — behavior review).
- [ ] Apply/release/request buttons still show busy and single-fire (code review: native forms, CSRF intact, guard + middleware paths untouched).
- [ ] Any tab whose search keeps its list while typing names the list's search like the cashier slice (or records why its shape differs).

## 9. Open Questions
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Proven pattern: `cashier-reports-toggle.md` (Implemented — pill switch + `?view=` persistence, per-side state). Copy the mechanism, not the styling twice: same navy pill, same persistence shape.
- Affected screens: new hub view (Discounts apply table + ledger search, requests form + list, refunds pending + history as three switched sections); sidebar collapses three cashier links into one Requests entry with active-state covering all three sub-routes.
- Likely areas: `CashierController@discounts` (apply queue + counts), `DiscountRequestController@index` (shared list — read-only reuse, registrar path untouched), `CashierController@refunds` (pending + released); existing partials (`discounts-results`, request list, refunds) reused inside the switch where possible.
- Old routes stay valid (deep links, registrar, back-button history); only the cashier sidebar entries change. Registrar never sees the hub.
- Badge data: approved-to-apply and unreleased counts already computed for the dashboard; add one pending-requests count; hide on zero.
- Proven-behavior pointers (carry over, do not rebuild): `throttle:search` on `cashier.discounts` only (`routes/web.php`); calm search via shared component on discounts list only; document-level submit guard + route-level idempotency cover all three tabs' POSTs automatically — keep forms native with CSRF.
- Stale parity: check each tab for the kept-list-while-typing shape at build; apply the cashier-slice message + dimming where it recurs.
- Data/records touched: none new — same queues, same tables.
- Roles/permissions involved: cashier only for the hub; registrar/directress flows unchanged and re-verified.

## 11. Approval
> Approved by user on 2026-10-06.

## 12. Closure Log (2026-10-06)

**Built as three slices:** (1) route `GET cashier/requests` + `CashierController@requests` composition method; (2) hub view with three-way pill, badges, `?view=` persistence; (3) sidebar collapse.

**Files changed (one commit, one spec):**
- `routes/web.php` — new `GET cashier/requests` only; `throttle:search` on `cashier.discounts` untouched.
- `app/Http/Controllers/Portal/CashierController.php` — new `requests()` method only; no existing method edited.
- `resources/views/portal/cashier/requests.blade.php` — new hub view (three tabs reuse standalone markup verbatim; discount tab's ledger search posts to the untouched `cashier.discounts?ajax` endpoint).
- `resources/views/portal/partials/sidebar-cashier.blade.php` — three entries → one Requests entry (§5), **plus user-directed ordering amendment** (below).
- this spec file.

**User-directed amendment (recorded here so the commit stays spec-covered):** after §5's collapse, the user directed the sidebar order be **Dashboard → Payments → Requests → Projections → Reports**; the first pass left Reports in its old slot, corrected the same day. Dashboard itself renders from the shared layout, before the partial — so the fix was ordering within `sidebar-cashier.blade.php` only. This ordering is a direct user instruction, not spec-derived; it is hereby adopted into this spec.

**Success checks (§8):** checks 1–7 verified — static checks (`php -l`, `route:list`, `view:cache`) plus code review by construction (endpoints, throttle, native+CSRF forms, guard/idempotency paths untouched because no existing code was edited); browser pass 1–4 delegated to the user. **Check 8 recorded-and-deferred (its own escape clause):** the discounts ledger list does keep rows while typing with per-row Apply buttons, but the fix belongs in the shared `ajaxTable` component (≈40 pages) — a component-level spec, not hub surgery. Proposed as follow-up work; not silently dropped.

**Deviations from §10's guesses (mechanism, not behavior):** no ajax branches or duplicate partials in the controller — the hub composes the same queries as the three standalone pages in one method, so badge/list agreement holds by construction; requests/refunds tabs have no search, so only the discounts tab reuses `ajaxTable`.
