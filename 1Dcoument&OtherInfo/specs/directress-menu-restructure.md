# Spec: Directress Menu Restructure

- **Status**: Implemented
- **Created**: 2026-10-06
- **Approved by**: user on 2026-10-06 (re-approved after "Fee Management" rename)
- **Implemented**: 2026-10-06

## 1. Why We Need This

The Directress's left menu is 8 flat entries in no workflow order: daily reading (Announcements) sits at the bottom, money setup (Fee Schedule, Graduation Fees) sits scattered in the middle, and the two decision queues (Discount Approvals, Promotion sign-off) sit apart. During fee season and sign-off season she hunts for the right entry. This restructure puts the menu in workflow order, groups the two money pages into one Assign Fees hub and the two decision queues into one Approvals hub — the same one-page, pill-switch pattern already proven on the registrar's and principal's menus — renames Fee Schedule to Fee Management and School Years to School Year Management, and moves rare setup out of the daily path.

## 2. Who Is Affected

- **Directress** — the only role whose menu changes. Same pages, same buttons, fewer, better-ordered entries.
- **Registrar** — assigns fees per student from the Directress's schedule, proposes promotions. Those flows keep working exactly as today.
- **Principal** — approves promotions before Directress sign-off. Untouched.
- **Cashier** — collects against the fee schedule. Untouched.
- **Teachers, parents, students** — no visible change.

## 3. Business Flow: Today vs After

- **As-is**: 8 flat entries in file order — Demographics, School Years, Fee Schedule, Graduation Fees, Reports, Discount Approvals, Promotion, Announcements. Money lives in two places, decisions live in two places, daily reading sits last.
- **To-be**: the menu reads in workflow order, top to bottom:
  1. **Dashboard** (unchanged, comes from the shared layout)
  2. **Announcements** — unchanged, opens its own page
  3. **Assign Fees** — one page with a two-way switch: **Fee Management → Graduation Fees**
  4. **School Year Management** — renamed from "School Years", opens its own page
  5. **Approvals** — one page with a two-way switch: **Discount Approvals → Promotion**
  6. **Demographics** — unchanged, opens its own page
  7. **Reports** — unchanged, opens its own page
- **Preserved**: every page address, every filter, every approve/reject/sign-off/assign button, every confirmation message, and every audit trail — the hubs move where lists live, never what they do. Old links and bookmarks keep working. The registrar's fee-assignment and promotion-propose flows are untouched.
- **Exceptions**: transfers, corrections, rejected discounts, rejected/refused promotions, and late sign-offs follow the same rules as today — this spec changes navigation only, never workflow.

## 4. How It Should Work

1. Directress signs in — the sidebar shows 7 entries in the order above.
2. Tapping **Assign Fees** opens the hub on **Fee Management** (first-listed default); the switch flips to Graduation Fees, each with its own list and buttons as today.
3. Tapping **Approvals** opens the hub on **Discount Approvals** (first-listed default); the switch flips to Promotion, each with its own list and buttons as today.
4. The open tab survives reload and the back button (the page address remembers it).
5. Small count badges sit on both Approvals tabs (discount requests waiting, promotions waiting for sign-off) — hidden when zero. No badges on the fee tabs (no natural waiting count there).
6. Approving a discount, signing off a promotion, or saving a fee behaves exactly as today, including confirmations and success messages.

## 5. Look & Feel (UX)

- **Where it lives:** the Directress sidebar. Eight entries become seven; four become two hub pages. The one job of each switch: change lists.
- **The one primary action per tab:** pick a list, act on it — same buttons, same places, same wording.
- **Key states:** first-listed tab by default; per-tab empty states as today (quiet Promotion tab off-season); error keeps the last good tab plus a way back; dark mode and collapsed sidebar behave as today.
- **Badges:** only on the two Approvals tabs — every tab there is a waiting queue, so every tab earns a count. Hidden at zero. Fee tabs carry no badges by design.
- **Renames, Directress-facing only:** sidebar label and hub tab change from "Fee Schedule" to **"Fee Management"**; sidebar label changes from "School Years" to **"School Year Management"**. Page addresses do not change. "Graduation Fees" keeps its plural; "Discount Approvals" and "Promotion" keep their wording as hub tabs.
- **What the Directress sees first:** Announcements as a standalone page, then money setup, then decision queues — daily awareness before setup before sign-off.

## 6. Business Rules

### Must always be true
- The open tab survives reload and back-button (page address remembers it).
- Each tab keeps its own list and actions; lists and totals never mix.
- Badges always match the underlying lists (stale badges are worse than none).
- Every approve/reject/sign-off writes the same audit trail as today.
- Old page addresses keep loading the standalone pages — deep links and bookmarks never break.
- The registrar's fee-assignment page, promotion propose flow, and the principal's approve step work exactly as today.
- Directress's permission set is unchanged — no new access, no lost access.

### Must never happen
- Switching tabs loses another tab's place or filters.
- An action lands on the wrong tab's data (approving a discount from the promotion tab, and the like).
- Any role other than the Directress sees a changed menu.
- A badge shows while its list is empty, or hides while work waits.
- A promotion stalls with no Directress screen to sign it off.
- Dark mode or the collapsed sidebar breaks a switch or hides a badge.
- Any form is rewritten as anything but a native submit with CSRF.
- Search throttles (where present) are removed or weakened.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Mid-load flip | Latest tap wins; no mixed content |
| Action confirm open, tab flipped underneath | Confirm completes against its own record, then the current tab refreshes |
| Off-season (no promotions) | Quiet Promotion tab, no badge — discount badge still guides |
| Both queues empty | Two quiet tabs, no badges — a clean morning |
| Registrar opens fee assignment / proposes promotion | Standalone pages, exactly as today — the hubs are invisible to them |
| Old bookmark to a standalone page loads | Standalone page works as today |
| Session ends mid-work | Sign back in; the address-kept tab resumes |

## 7. Out of Scope

- The approval workflows themselves (discount approve/reject rules, promotion propose/approve/sign-off rules, fee-assign rules).
- The registrar's, principal's, and cashier's menus and screens.
- Roles and permissions — who may approve, propose, or assign is unchanged.
- Exports, reminders, notifications.
- Renaming page addresses (frozen — zero breakage).
- Any change to what the dashboard shows.

## 8. Success Checks

- [ ] Sidebar shows exactly 7 entries in the order: Dashboard, Announcements, Assign Fees, School Year Management, Approvals, Demographics, Reports.
- [ ] No "Fee Schedule", "School Years", "Graduation Fees", "Discount Approvals" (standalone), or "Promotion" (standalone) entries remain in the Directress sidebar.
- [ ] Assign Fees opens on Fee Management; each tap shows the right list with its own buttons.
- [ ] Approvals opens on Discount Approvals; each tap shows the right queue with its own buttons.
- [ ] Sit on Graduation Fees / Promotion — reload keeps it open; back-button behaves.
- [ ] Badges match their lists; empty queues show no badge; fee tabs show no badges (cross-check against the standalone pages).
- [ ] "Fee Management" and "School Year Management" read correctly in sidebar and hub.
- [ ] Every old link still loads: Demographics, School Years, Fee Schedule, Graduation Fees (+ create/edit/assigned), Discount Approvals, Promotion, Announcements, Reports (+ all report tabs and exports).
- [ ] Registrar's fee-assignment and promotion-propose flows, and the principal's approve step, work exactly as before.
- [ ] Approve/reject/sign-off paths show the same confirmations and success messages as before.

## 9. Open Questions

None — all decisions made in interview (order, hub grouping and tab order, default tabs, badge scope, rename wording, School Year Management placement).

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Proven pattern: `registrar-menu-restructure.md` (Implemented) + `principal-menu-restructure.md` (Implemented) — pill switch + `?view=` Alpine persistence, hub composes source queries, badges counted from the same collections.
- Affected screens: `resources/views/portal/partials/sidebar-directress.blade.php` (8 entries → 7 in spec order, active-state covering all sub-routes per entry); two new hub views (`portal/directress/assign-fees.blade.php`, `portal/directress/approvals.blade.php` or matching names); sidebar-only renames ("Fee Schedule" → "Fee Management", "School Years" → "School Year Management" — confirm whether page headings change too at build).
- New routes (mirrors of registrar/principal hubs): `GET /directress/assign-fees` → `directress.assign-fees`; `GET /directress/approvals` → `directress.approvals`, hosted on `DirectressController` next to existing methods (same hosting pattern: registrar hubs on `RegistrarController`, principal hub on `PrincipalController`).
- Tab data sources (compose in the hub methods, same variable names as the standalone views):
  - Assign Fees: `DirectressController@fees` (`directress.fees`, view `portal/directress/fees/index.blade.php`) + `DirectressController@graduationFees` (`directress.graduation-fees`, view `portal/directress/graduation-fees/index.blade.php`).
  - Approvals: `DiscountRequestController@reviewIndex` (`directress.discount-requests.index`, view `portal/directress/discount-requests/index.blade.php`) + `PromotionWorkflowController@directressIndex` (`directress.promotion.index`, view `portal/directress/promotion/index.blade.php`). Badge = pending discount requests + proposals awaiting sign-off — confirm exact status filters against each source query at build; hide on zero.
- Standalone routes stay registered (deep links keep working): `directress.fees*`, `directress.graduation-fees*`, `directress.discount-requests.*`, `directress.promotion.*`, `directress.school-years*`, `directress.demographics`, `directress.reports*`, `directress.announcements.*`, plus legacy redirects `directress.library-reports` / `directress.cashier-reports`. Only sidebar entries move.
- Renames: Directress-facing labels only (sidebar, hub tabs). Route names frozen.
- Dashboard entry renders from `portal/layouts/app.blade.php`, before the partial — ordering happens within `sidebar-directress.blade.php` only (same as registrar/principal closures).
- Data/records touched: none — same lists, same tables, navigation only.
- Roles/permissions involved: Directress only for the menu; registrar/principal/cashier flows unchanged and re-verified by success checks.

## 11. Approval

> Approved by user on 2026-10-06.

## 12. Closure Log (2026-10-06)

**Built in five slices:** (1) two `GET directress/*` hub routes; (2) two hub
composition methods on `DirectressController`; (3) two hub views; (4) sidebar
rewrite; (5) verification walkthrough — all 10 success checks passed (browser
passes by user, static checks `php -l` / `route:list` / `view:cache` clean,
live-action check passed by construction-review: POST routes byte-for-byte
untouched).

**Files changed (one commit, one spec):**
- `routes/web.php` — two new GET routes only; all existing routes untouched.
- `app/Http/Controllers/Portal/DirectressController.php` — `View` import +
  `assignFees()` / `approvals()` methods; no existing method edited.
- `resources/views/portal/directress/assign-fees.blade.php` — new hub view.
- `resources/views/portal/directress/approvals.blade.php` — new hub view.
- `resources/views/portal/partials/sidebar-directress.blade.php` — 8 entries →
  6 in spec order ("Fee Schedule" → hub tab "Fee Management",
  "School Years" → "School Year Management"), active-state covering all
  sub-routes.
- this spec file.

**Deviation from §10's guesses (wording, per user mid-build):** fee tab
renamed "Fee Assign" → **"Fee Management"** (spec + hub view tab/heading/keys);
sidebar parent stays "Assign Fees". Hub first-paint is unfiltered — tab
searches post to the standalone addresses, so hub routes carry no throttle and
cross-tab filter collisions are impossible; flashes hoisted to hub level;
graduation tab composed as `$gradFees` to avoid colliding with the fee tab's
`$fees`.
