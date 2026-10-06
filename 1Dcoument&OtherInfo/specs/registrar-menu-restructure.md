# Spec: Registrar Menu Restructure

- **Status**: Implemented
- **Created**: 2026-10-06
- **Approved by**: user on 2026-10-06
- **Implemented**: 2026-10-06

## 1. Why We Need This

The registrar's left menu is a flat list of 9 links with no order to it: action
queues (withdrawals, discount requests) sit mixed in with year-start setup
(subjects, sections) and year-end work (promotion). During enrollment season the
registrar hunts for the queues that matter; everything looks equally important.
This restructure groups related work into three hub pages — the same one-page,
pill-switch pattern already proven on the cashier's menu — renames two
misleading labels, and puts the menu in workflow order.

## 2. Who Is Affected

- **Registrar** — the only role whose menu changes. Same pages, same buttons,
  fewer, better-ordered entries.
- **Cashier** — uses the shared Discount Requests page. It must keep working
  exactly as today (their sidebar highlights it as before).
- **Directress and Principal** — approve promotions, discount requests, and
  promotions sign-off as today. Their screens and menus are untouched.
- **Teachers and parents** — no visible change.

## 3. Business Flow: Today vs After

- **As-is**: 9 flat sidebar entries in no workflow order — Admissions Queue,
  Withdrawals, Report Cards, Sections, Subjects, Promotion, Discount Requests,
  Grade Unlocks, Fee Assignment. Two of the names confuse: "Promotion" doesn't
  say it's the end-of-year process, and "Grade Unlocks" doesn't say the
  registrar edits grades there.
- **To-be**: the menu reads in workflow order, top to bottom:
  1. **Dashboard** (unchanged, comes from the shared layout)
  2. **Admissions Queue** — unchanged, opens its own page
  3. **Report Cards** — unchanged, opens its own page
  4. **Requests** — one page with a three-way switch: **Discount Requests →
     Withdrawals → End-of-Year Promotion**
  5. **Sections & Subjects** — one page with a two-way switch: **Sections →
     Subjects**
  6. **Grade & Fee** (closing entry) — one page with a two-way switch:
     **Grade Edit → Fee Assignment**
- **Preserved**: every page address, every filter, every approve/reject/propose
  button, every confirmation message, and every audit trail — the hubs move
  where lists live, never what they do. Old links and bookmarks keep working.
  Search throttles on Admissions, Withdrawals, and Report Cards stay exactly
  where they are. The cashier's access to Discount Requests is untouched.
- **Exceptions**: transfers, corrections, late promotions, and rejected
  withdrawals follow the same rules as today — this spec changes navigation
  only, never workflow.

## 4. How It Should Work

1. Registrar signs in — the sidebar shows 6 entries in the order above.
2. Tapping **Requests** opens the hub on **Discount Requests** (first-listed
   default); the switch at the top flips to Withdrawals or End-of-Year
   Promotion, each with its own list, filters, and buttons as today.
3. The open tab survives reload and the back button (the page address
   remembers it).
4. Small count badges sit on the Requests tabs (waiting discount requests,
   withdrawals awaiting action, promotion proposals awaiting registrar step)
   and on Grade Edit (unlock requests waiting) — hidden when zero.
5. Tapping **Sections & Subjects** or **Grade & Fee** works the same way:
   first-listed tab opens, pill switches, no badges except Grade Edit.
6. Approving a withdrawal, proposing a promotion, or editing a grade behaves
   exactly as it does today, including confirmations and success messages.

## 5. Look & Feel (UX)

- **Where it lives:** the registrar sidebar. Nine entries become six; one new
  entry (Sections & Subjects) appears; two entries become hub pages. The one
  job of each switch: change queues.
- **The one primary action per hub:** pick a queue, act on it — same buttons,
  same places, same wording.
- **Key states:** first-listed tab by default; per-tab empty states as today;
  error keeps the last good tab plus a way back; dark mode and collapsed
  sidebar behave as today.
- **Badges:** only where a count equals work waiting (Requests' three tabs +
  Grade Edit). Never on Fee Assignment, Sections, or Subjects — no natural
  waiting count exists there.
- **Renames, registrar-facing only:** sidebar label, hub tab label, and the
  registrar page heading change from "Promotion" to **"End-of-Year Promotion"**
  and from "Grade Unlocks" to **"Grade Edit"**. The Principal's sidebar and
  teacher pages keep their current wording. Web addresses do not change.
- **What the registrar sees first:** Admissions Queue and Report Cards as
  standalone pages, then the Requests hub — daily action before yearly setup.

## 6. Business Rules

### Must always be true
- The open tab survives reload and back-button (page address remembers it).
- Each tab keeps its own filters, results, and actions; lists and totals never
  mix.
- Badges always match the underlying lists (stale badges are worse than none).
- Every approve/reject/propose writes the same audit trail as today.
- Old page addresses keep loading the standalone pages — deep links,
  bookmarks, and the cashier's Discount Requests link never break.
- The cashier sidebar still highlights Discount Requests exactly as today.
- Registrar's permission set is unchanged — no new access, no lost access.

### Must never happen
- Switching tabs loses another tab's picked filters.
- An action lands on the wrong tab's data (approving a withdrawal from the
  promotion tab, and the like).
- Any role other than the Registrar sees a changed menu.
- A badge shows while its list is empty, or hides while work waits.
- Dark mode or the collapsed sidebar breaks a switch or hides a badge.
- Any form is rewritten as anything but a native submit with CSRF (that would
  silently drop the double-submit guards).
- Search throttles are removed or weakened on Admissions, Withdrawals, or
  Report Cards.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Mid-load flip | Latest tap wins; no mixed content |
| Action confirm open, tab flipped underneath | Confirm completes against its own record, then the current tab refreshes |
| All queues empty | Quiet tabs, no badges — a clean morning |
| Cashier opens Discount Requests | Standalone page, exactly as today — the registrar hub is invisible to them |
| Old bookmark to a standalone page loads | Standalone page works as today |
| Session ends mid-work | Sign back in; the address-kept tab resumes |

## 7. Out of Scope
- The approval workflows themselves (Directress discount approvals, principal
  promotion approvals, withdrawal approve/reject rules).
- The Principal's and Teacher's sidebars and their "Grade Unlocks" wording.
- Roles and permissions — who may approve, propose, or edit is unchanged.
- Search throttle expansion, calm-search retrofits, exports, reminders.
- Renaming web addresses or route names (frozen — zero breakage).
- Any change to what the dashboard shows.

## 8. Success Checks
- [ ] Sidebar shows exactly 6 entries in the order: Dashboard, Admissions
      Queue, Report Cards, Requests, Sections & Subjects, Grade & Fee.
- [ ] Requests opens on Discount Requests; each tap shows the right queue with
      its own filters and buttons.
- [ ] Sit on Withdrawals — reload keeps Withdrawals open; back-button behaves.
- [ ] Badges match their lists; Fee Assignment, Sections, and Subjects show no
      badges (cross-check counts against the standalone pages).
- [ ] "End-of-Year Promotion" and "Grade Edit" appear in the registrar sidebar,
      hub tabs, and page headings; the Principal's sidebar still says
      "Grade Unlocks".
- [ ] Every old link still loads: Admissions, Report Cards, Withdrawals,
      Discount Requests, Promotion, Grade Unlocks, Fee Assignment, Sections,
      Subjects addresses all work as standalone pages.
- [ ] Cashier's Discount Requests entry still highlights and works as before.
- [ ] Approve/reject/propose actions show the same confirmations and success
      messages as before (walk one withdrawal and one promotion proposal).
- [ ] Admissions, Withdrawals, and Report Cards searches still throttle with
      countdown (same routes, same middleware — behavior review).

## 9. Open Questions
None — all decisions made in interview (order, hub grouping, default tabs,
badge scope, rename scope).

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this
appendix.*
- Proven pattern: `cashier-requests-hub.md` (Implemented) + `cashier-reports-toggle.md`
  — pill switch + `?view=` Alpine persistence. Copy the mechanism from
  `resources/views/portal/cashier/requests.blade.php` (x-data tab block, line 22)
  and the sidebar collapse shape from `sidebar-cashier.blade.php`.
- Affected screens: `resources/views/portal/partials/sidebar-registrar.blade.php`
  (reorder + collapse 9→6, active-state covering all sub-routes per entry);
  three new hub views; page-heading renames in
  `portal/registrar/promotion/index.blade.php` and
  `portal/registrar/grade-unlocks/index.blade.php`.
- New routes (namespaces reserved, mirrors of `GET /cashier/requests`):
  `GET /registrar/requests` → `registrar.requests`;
  `GET /registrar/sections-subjects` → `registrar.sections-subjects`;
  `GET /registrar/grade-fee` → `registrar.grade-fee`.
- Tab data sources (compose in hub methods, badge/list agreement by
  construction — same deviation applied in the cashier hub closure):
  - Requests: `DiscountRequestController@index` (shared list — read-only
    reuse; `discount-requests.index` route and cashier sidebar active-state
    untouched), `WithdrawalController@index` (line ~101), and
    `PromotionWorkflowController@registrarIndex` (line 35).
  - Grade & Fee: `GradeUnlockController@reviewIndex` (line 87),
    `FeeAssignmentController@index` (line 41).
  - Sections & Subjects: `Admin\SectionController` and `Admin\SubjectController`
    resources (`registrar.sections.*`, `registrar.subjects.*` incl.
    template/import routes).
- Badge data: pending discount requests, withdrawals awaiting action,
  `openProposals` (already computed in `registrarIndex`), pending unlock
  requests — confirm exact status filters against each source query at build;
  hide on zero.
- Throttles: `throttle:search` sits on `registrar.admissions.index`,
  `registrar.withdrawals.index`, `registrar.report-cards.index`
  (`routes/web.php`). Admissions and Report Cards stay standalone — untouched.
  The withdrawals tab moves into a hub: if its search posts to the hub route
  rather than the standalone route, carry `throttle:search` onto the hub route
  — never drop it.
- Renames: registrar-facing labels only (sidebar, tab, heading). Route names
  (`registrar.promotion.*`, `registrar.grade-unlocks.*`) and
  `sidebar-principal.blade.php` / teacher views stay byte-for-byte.
- Dashboard entry renders from `portal/layouts/app.blade.php` (line ~347),
  before the partial — ordering happens within `sidebar-registrar.blade.php`
  only (same as the cashier closure amendment).
- Data/records touched: none — same lists, same tables, navigation only.
- Roles/permissions involved: registrar only for the menu; cashier/directress/
  principal flows unchanged and re-verified by success checks.

## 11. Holding Notes (pre-approval review points, per user)
- Sections & Subjects tabs carry no badges by design (year-start setup, no
  waiting-queue) — confirm at approval that quiet tabs are acceptable there.
- The withdrawals search throttle is the one genuine build-time risk; the fix
  rule is in §10 (carry the throttle onto the hub, never drop it).
- Cashier's shared Discount Requests link gets its own success check (§8),
  since that is the cross-role coupling.

## 12. Approval
> Approved by user on 2026-10-06.

## 13. Closure Log (2026-10-06)

**Built in five slices:** (1) three `GET registrar/*` routes; (2) three hub
composition methods on `RegistrarController`; (3) three hub views; (4) sidebar
collapse + heading renames; (5) verification walkthrough — all 9 success checks
passed (browser passes by user, static checks `php -l` / `route:list -v` /
`view:cache` clean, check 8 waived to construction-review: POST routes
byte-for-byte untouched).

**Files changed (one commit, one spec):**
- `routes/web.php` — three new GET routes only; all existing routes and
  middleware untouched.
- `app/Http/Controllers/Portal/RegistrarController.php` — three new methods
  (`requests`, `sectionsSubjects`, `gradeFee`) + `View` import; no existing
  method edited.
- `resources/views/portal/registrar/requests.blade.php` — new hub view.
- `resources/views/portal/registrar/sections-subjects.blade.php` — new hub view.
- `resources/views/portal/registrar/grade-fee.blade.php` — new hub view.
- `resources/views/portal/partials/sidebar-registrar.blade.php` — 9 entries →
  5 in spec order, active-state covering all sub-routes.
- `resources/views/portal/registrar/grade-unlocks/index.blade.php` — breadcrumb
  + heading renamed to "Grade Edit".
- this spec file.

**Deviations from §10's guesses (mechanism, not behavior):** hub first-paint is
unfiltered (no request filters in hub methods) — tab searches post to the
throttled standalone routes, so hub routes carry no throttle and cross-tab
filter collisions are impossible; subjects tab hardcodes the registrar search
route (hub is registrar-only; principal's shared path untouched); flashes
hoisted to hub level (per-tab blocks stripped to avoid double display);
subjects filter uses `$subjectGradeLevels` (sections' list lacks SHS).
Promotion page needed no rename — it already read "End-of-Year Promotion".
