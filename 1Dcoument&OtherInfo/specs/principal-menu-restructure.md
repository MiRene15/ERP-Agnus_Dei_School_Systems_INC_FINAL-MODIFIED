# Spec: Principal Menu Restructure

- **Status**: Implemented
- **Created**: 2026-10-06
- **Approved by**: user on 2026-10-06
- **Implemented**: 2026-10-06

## 1. Why We Need This

The principal's left menu is 8 flat entries with two problems: names that
don't say what the page does ("Teachers" is actually teacher-to-class
assignment), and approval work scattered across three separate entries
(Subject Approvals, Grade Unlocks, Promotion) that the principal opens one by
one during decision time. This restructure puts the menu in workflow order,
fixes the misleading label, removes the little-used Student Grades entry, and
groups all three decision queues into one Approvals hub — the same one-page,
pill-switch pattern already proven on the cashier's and registrar's menus.

## 2. Who Is Affected

- **Principal** — the only role whose menu changes. Same pages, same buttons,
  fewer, better-ordered entries.
- **Registrar** — proposes promotions, stages subject changes, and shares the
  Grade Edit page. All three flows must keep working exactly as today.
- **Directress** — signs off promotions after the principal. Untouched.
- **Teachers, parents, students** — no visible change.

## 3. Business Flow: Today vs After

- **As-is**: 8 flat entries — Schedules, Student Grades, Announcements,
  Promotion, Subjects, Subject Approvals, Teachers, Grade Unlocks. Decision
  queues live in three places; "Teachers" hides assignment work behind a vague
  name.
- **To-be**: the menu reads in workflow order, top to bottom:
  1. **Dashboard** (unchanged, comes from the shared layout)
  2. **Announcements** — unchanged, opens its own page
  3. **Schedules** — unchanged, opens its own page
  4. **Subjects** — unchanged, opens its own page (read-only oversight)
  5. **Teacher Assignments** — renamed from "Teachers", opens its own page
  6. **Approvals** — one page with a three-way switch: **Promotions →
     Subject Approvals → Grade Edit**
- **Preserved**: every page address, every filter, every approve/reject
  button, every confirmation message, and every audit trail — the hub moves
  where queues live, never what they do. Old links and bookmarks keep working,
  including the removed Student Grades address. Search throttles on Schedules
  and Announcements stay exactly where they are. The registrar's shared Grade
  Edit page and the promotion propose flow are untouched.
- **Exceptions**: transfers, corrections, rejected proposals, and rejected
  change requests follow the same rules as today — this spec changes
  navigation only, never workflow.

## 4. How It Should Work

1. Principal signs in — the sidebar shows 6 entries in the order above.
2. Tapping **Approvals** opens the hub on **Promotions** (first-listed
   default); the switch flips to Subject Approvals or Grade Edit, each with
   its own list and buttons as today.
3. The open tab survives reload and the back button (the page address
   remembers it).
4. Small count badges sit on all three tabs (proposed promotions, pending
   subject changes, pending unlock requests) — hidden when zero.
5. Approving a promotion, applying a subject change, or unlocking grades
   behaves exactly as today, including confirmations and success messages.

## 5. Look & Feel (UX)

- **Where it lives:** the principal sidebar. Eight entries become six; three
  become one hub page. The one job of the switch: change decision queues.
- **The one primary action per tab:** review the queue, approve or reject —
  same buttons, same places, same wording.
- **Key states:** Promotions tab by default (quiet empty state off-season);
  per-tab empty states as today; error keeps the last good tab plus a way
  back; dark mode and collapsed sidebar behave as today.
- **Badges:** all three tabs — every tab here is a waiting queue, so every
  tab earns a count. Hidden at zero.
- **Renames:** sidebar "Teachers" becomes **"Teacher Assignments"** (the page
  itself already reads that way — only the sidebar label changes). The hub's
  third tab reads **"Grade Edit"**, matching what the registrar already sees
  on the shared page. Web addresses do not change.
- **Removal:** the Student Grades entry leaves the sidebar; its address keeps
  loading for anyone with the link. Nothing else references that entry.
- **What the principal sees first:** announcements and schedules as standalone
  pages, then the Approvals hub — daily awareness before decision queues.

## 6. Business Rules

### Must always be true
- The open tab survives reload and back-button (page address remembers it).
- Each tab keeps its own list and actions; queues and totals never mix.
- Badges always match the underlying lists (stale badges are worse than none).
- Every approve/reject writes the same audit trail as today.
- Old page addresses keep loading the standalone pages — deep links and
  bookmarks never break, including Student Grades.
- The registrar's Grade Edit page, propose flow, and subject staging work
  byte-for-byte as today.
- Principal's permission set is unchanged — no new access, no lost access.

### Must never happen
- Switching tabs loses another tab's place or filters.
- An action lands on the wrong tab's data (approving a promotion from the
  subject-approvals tab, and the like).
- Any role other than the Principal sees a changed menu.
- A badge shows while its list is empty, or hides while work waits.
- A promotion proposal stalls with no principal screen to decide it.
- Dark mode or the collapsed sidebar breaks the switch or hides a badge.
- Any form is rewritten as anything but a native submit with CSRF.
- Search throttles are removed or weakened on Schedules or Announcements.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Mid-load flip | Latest tap wins; no mixed content |
| Action confirm open, tab flipped underneath | Confirm completes against its own record, then the current tab refreshes |
| Off-season (no proposals) | Quiet Promotions tab, no badge — subject and unlock badges still guide |
| All three queues empty | Three quiet tabs, no badges — a clean morning |
| Registrar opens Grade Edit | Shared standalone page, exactly as today — the hub is invisible to them |
| Old Student Grades bookmark loads | Standalone page works as today |
| Session ends mid-work | Sign back in; the address-kept tab resumes |

## 7. Out of Scope
- The approval workflows themselves (promotion propose/approve/sign-off
  rules, subject-change apply rules, unlock rules).
- The registrar's and directress's menus and screens.
- Roles and permissions — who may approve or propose is unchanged.
- Search throttle expansion, exports, reminders, notifications.
- Renaming web addresses or route names (frozen — zero breakage).
- Any change to what the dashboard shows.

## 8. Success Checks
- [ ] Sidebar shows exactly 6 entries in the order: Dashboard, Announcements,
      Schedules, Subjects, Teacher Assignments, Approvals.
- [ ] No "Student Grades", "Teachers", "Promotion", "Subject Approvals", or
      "Grade Unlocks" entries remain in the principal sidebar.
- [ ] Approvals opens on Promotions; each tap shows the right queue with its
      own buttons.
- [ ] Sit on Subject Approvals — reload keeps it open; back-button behaves.
- [ ] Badges match their lists; empty queues show no badge (cross-check
      against the standalone pages).
- [ ] "Teacher Assignments" and "Grade Edit" read correctly in sidebar and hub.
- [ ] Every old link still loads: Schedules, Announcements, Subjects, Student
      Grades, Promotion, Subject Approvals, Grade Unlocks, Teacher Assignments
      addresses all work as standalone pages.
- [ ] Registrar's Grade Edit page and promotion propose flow work exactly as
      before.
- [ ] Approve/reject paths show the same confirmations and success messages
      as before (walk on expendable test records, or pass by construction
      review since the POST routes are untouched).
- [ ] Schedules and Announcements searches still throttle (same routes, same
      middleware — behavior review).

## 9. Open Questions
None — all decisions made in interview (order, hub grouping and tab order,
default tab, rename wording, Student Grades removal).

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this
appendix.*
- Proven pattern: `registrar-menu-restructure.md` (Implemented) +
  `cashier-requests-hub.md` (Implemented) — pill switch + `?view=` Alpine
  persistence, hub composes source queries, badges counted from the same
  collections.
- Affected screens: `resources/views/portal/partials/sidebar-principal.blade.php`
  (8 entries → 6, active-state covering all sub-routes); one new hub view;
  sidebar label "Teachers" → "Teacher Assignments" (page heading already
  reads that way — no page edit needed).
- New route (mirrors the registrar hubs): `GET /principal/approvals` →
  `principal.approvals`, hosted on `PrincipalController` next to the dashboard
  method (same hosting pattern: cashier hub on `CashierController`, registrar
  hubs on `RegistrarController`).
- Tab data sources (compose in the hub method, same variable names as the
  standalone views):
  - Promotions: `PromotionWorkflowController@principalIndex` (line 115) —
    `$proposals` (STATUS_PROPOSED) + `$passingGrade`; badge =
    `$proposals->count()`.
  - Subject Approvals: `SubjectApprovalController@index` — `$pending`
    (STATUS_PENDING) + `$history`; badge = `$pending->count()`.
  - Grade Edit: `GradeUnlockController@reviewIndex` (shared with registrar —
    read-only reuse, registrar path untouched); badge = pending count.
- Throttles: `throttle:search` sits on `principal.schedules`, `principal.grades`,
  `principal.announcements` (`routes/web.php`). All three stay standalone —
  untouched. Approval tabs have no search inputs, so the hub route needs no
  throttle.
- Removal: `principal.grades` route stays registered (deep links keep working);
  only the sidebar entry goes. Verified: nothing else references that entry
  except its own view.
- Renames: principal-facing labels only (sidebar, hub tab). Route names
  (`principal.promotion.*`, `principal.teacher-assignments.*`,
  `registrar.grade-unlocks.*`) frozen.
- Dashboard entry renders from `portal/layouts/app.blade.php`, before the
  partial — ordering happens within `sidebar-principal.blade.php` only.
- Data/records touched: none — same queues, same tables, navigation only.
- Roles/permissions involved: principal only for the menu; registrar/
  directress flows unchanged and re-verified by success checks.

## 11. Approval
> Approved by user on 2026-10-06.

## 12. Closure Log (2026-10-06)

**Built in five slices:** (1) one `GET principal/approvals` route; (2) one hub
composition method on `PrincipalController`; (3) one hub view; (4) sidebar
rewrite; (5) verification walkthrough — all 10 success checks passed (browser
passes by user, static checks `php -l` / `route:list -v` / `view:cache` clean,
live-action check passed by construction-review: POST routes byte-for-byte
untouched).

**Files changed (one commit, one spec):**
- `routes/web.php` — one new GET route only; all existing routes and
  middleware untouched.
- `app/Http/Controllers/Portal/PrincipalController.php` — one new method
  (`approvals`) + `View` import; no existing method edited.
- `resources/views/portal/principal/approvals.blade.php` — new hub view.
- `resources/views/portal/partials/sidebar-principal.blade.php` — 8 entries →
  5 in spec order ("Teachers" → "Teacher Assignments", Student Grades entry
  removed), active-state covering all sub-routes.
- this spec file.

**Deviations from §10's guesses (mechanism, not behavior):** subject-approval
collections composed as `$subjectPending`/`$subjectHistory` (unlock
`$pending`/`$history` collision), mapped back to standalone names via a
one-line alias in the hub view so copied markup stays verbatim; flashes
hoisted to hub level. No page-heading edits needed — Teacher Assignments page
already read correctly and the shared Grade Edit heading was renamed under
`registrar-menu-restructure.md`. "Teacher Assign" wording explicitly rejected
by user during build (kept "Teacher Assignments"); spec unchanged.
