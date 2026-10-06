# Spec: Safe Actions Coverage Sweep

- **Status**: Implemented (2026-10-07 — verification sweep complete, all 6 success checks passed, no code changes needed)
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07

## 1. Why We Need This

The safe-actions family promises every button is safe everywhere — but that promise was verified role by role as each piece landed, and two things joined the system since: new hub pages, resend buttons, and badges (from the menu and email work), plus one known gap (the public inquiry form lives outside the shared page shell). This sweep re-checks every button, search, loading state, and page event against that promise, fixes whatever it finds, and writes down the proof per role — so "covered everywhere" is verified, not assumed.

## 2. Who Is Affected

- **Cashier, registrar, teachers** — heaviest button users; first in line. Fewer doubles at the counter, in enrollment, in grading.
- **Librarian, nurse, admin, student, directress, principal** — same protection confirmed on their pages and queues.
- **Applicants/parents** — the public inquiry button finally behaves like every inside button.
- **IT admin** — a per-role proof list instead of tribal knowledge.

## 3. Business Flow: Today vs After

- **As-is**: portal pages inherit press-locks, submit markers, and calmed searches from the shared shell; the public inquiry form does not (validation only — safe data, wrong message on doubles, plus a same-instant race window).
- **To-be**: sweep order — public inquiry form first (fix + verify), then cashier → registrar → teacher, then librarian/nurse/admin/student/directress/principal. Each group: walk every save (press twice → one write + calm notice), every link (hammer → one load), every search (pauses before firing), every loading state (busy shows, always releases). Stragglers fixed as found, verified per group.
- **Preserved**: the parent rulebook (`safe-actions-everywhere.md` and its three children) is untouched — this sweep verifies against it, never rewrites it. Deliberate repeats always fire; back/refresh/new-tab never locked; auth tokens and API routes stay excluded by design.
- **Exceptions**: same as the parent — lost-response resends recognized calmly, two people submitting the same thing both succeed, expired references treated as new work, background retries never double-apply.

## 4. How It Should Work

1. Sweep opens on the public inquiry form: double-submit makes one account and says so calmly; hammering links and searches behaves.
2. Cashier, registrar, teacher pages walked group by group: double-press any save → one write, calm notice, button busy then released.
3. Remaining roles walked the same way; any straggler gets the established pattern (shared behavior where the shell loads, documented local lock where it doesn't — the student form's precedent).
4. Deliberate separate submits verified working in each group; navigation (back/refresh/new-tab) verified untouched.
5. Proof recorded per role in the closure log; parent spec unchanged.

## 5. Look & Feel (UX)

- Nothing new to learn, nowhere new to visit — same buttons, same places. Only additions where missing: busy-on-press on the pressed button only, calm repeat notice in the existing success spot.
- Plain words throughout ("already saved" style); never an error for a repeat, never silent, never technical wording.
- Busy always releases — success, error, lost connection. Nobody left with a dead button.
- What each role sees first: their tap registered, then the answer. No re-tap needed.

## 6. Business Rules

### Must always be true
- Every save across all roles fires once per intent; repeats recognized and reported calmly.
- Button locks release on every outcome; links resolve to the latest intended load.
- Every search waits for a pause without its author arranging it.
- Fresh intent always succeeds, in every group.
- Records keep reference/route/outcome/time only — never contents or anything personal.

### Must never happen
- Two genuinely different intents refused for resembling each other.
- A repeat shown as an error, or set aside silently.
- A dead button after any outcome; a locked back/refresh/new-tab.
- A full-page block while one button works.
- New global behavior breaking the parent specs' verified checks.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Double-press save | One write, rest dropped, calm notice |
| Lost response, browser resends | Recognized repeat, calm notice, one write |
| Hammer link while loading | One load (latest wins), spinner to render |
| Double-tap export | One file |
| Save fails mid-press | Button returns + existing error; retry is fresh |
| Same form after edit / reload | New reference, processed as new work |
| Two people, same action | Both succeed (their own work) |
| Public form double-submit | One account, calm notice (the fix) |

## 7. Out of Scope

- Rewriting the parent or child safe-actions specs or their mechanisms.
- Email content/delivery design (closed under `applicant-email-reliability.md`); mail duplicate-protection re-verified, not redesigned.
- Auth tokens and API routes (excluded by the parent's design).
- New screens, rewording beyond busy + repeat notice, search-speed work.
- Queue worker supervision/schedules (ops follow-up, per parent).

## 8. Success Checks

- [ ] Inquiry double-submit → one account + calm notice (not error).
- [ ] Per role group (cashier, registrar, teacher, then the rest): double-press any save → one write + calm notice + busy-then-released button.
- [ ] Hammer any link per group → one load; back/refresh/new-tab untouched.
- [ ] Every search per group waits for a pause before firing.
- [ ] Two deliberate submits per group → both succeed.
- [ ] No legit work refused in any group; nothing personal kept with a reference.

## 9. Open Questions

None — order, rule reuse, exclusions, and checks decided in interview; technical details from code inspection in §10.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: `PromotionalWebsite/inquiry` (+ layout, which never loads the shared script); portal pages need verification only (all extend `portal.layouts.app`, confirmed uniform across cashier/registrar/teacher/librarian/nurse/admin/student/directress/principal, including new hubs `directress/assign-fees`, `directress/approvals`, registrar badges/resend).
- Likely areas of the codebase (from code inspection):
  - Guard + marker minting: `resources/js/app.js` (~line 265 mints `_idempotency_key` per submit; global press lock + link guard + calm-search behavior); loaded on every portal page via the portal layout — nothing per-page to add there.
  - Server repeat-check: `app/Http/Middleware/EnsureIdempotentSubmission.php`, appended to the whole `web` group (`bootstrap/app.php`) — every web save passes through; missing marker passes as new (never blocked).
  - Fix pattern for the inquiry form: documented local-lock precedent in `portal/student/admission-apply.blade.php` (~line 491); promo layout has only Alpine CDN + inline scripts, so the fix lives with the form, not the shell.
  - New endpoints already consistent: resend/approve read the marker exactly like the cashier/teacher code; hub copies carry source markup verbatim; hub GET routes are reads (correctly unguarded).
- Data/records touched: submission-reference rows for fixed forms (reference/route/outcome/time only — 3-day prune per child C); no other data changes.
- Roles/permissions involved: none changed — all nine roles plus unauthenticated applicants; auth/API boundaries from the parent hold.

## 11. Approval

> Approved by user on 2026-10-07.
