# Spec: Promo Light-Only

- **Status**: Implemented (2026-10-06 — all 5 §8 checks pass per user report, see §12)
- **Created**: 2026-10-06
- **Approved by**: user on 2026-10-06

## 1. Why We Need This

The public school website sometimes opens in dark mode. It happens when a visitor's phone or computer is set to dark, or when someone who switched their account to dark visits the public site in the same browser — the two share one saved preference. Parents meeting the school for the first time get a dim, off-brand page, and the "Enroll for 2026" button turns muddy and hard to read. The public site should always be white. Dark mode stays an account-only feature.

## 2. Who Is Affected

- **Parents and visitors** — always get the bright, readable public site, on any device setting. Nothing for them to learn or toggle.
- **Staff** — nothing changes in how their accounts look or behave; the account dark/light switch works exactly as today.
- **IT admin** — one small, contained change to review; no new settings to maintain.

## 3. Business Flow: Today vs After

- **As-is**: a visitor opens the public site. If their device is set to dark, or their browser carries a dark preference saved from an account session, the whole public site — including the enrollment button — renders dark.
- **To-be**: a visitor opens the public site. It is white, every page, every time, regardless of device setting or saved preferences. The account portal keeps its own dark/light switch, untouched.
- **Preserved**: the login page stays white as it already is; the account portal's toggle, saved preference, and behavior do not change in any way.
- **Exceptions**: none — there is no legitimate case for a dark public page. Browser extensions that force their own dark styling are outside our control and out of scope.

## 4. How It Should Work

1. A visitor opens any public page (home, admissions info, programs, requirements, inquiry form).
2. The page renders white, even if the visitor's device is set to dark.
3. The page renders white, even if the same browser holds a dark preference saved from an account session.
4. The "Enroll for 2026" button and all other buttons, menus, and forms appear in their normal white-site styling, fully readable.
5. A staff member toggles dark inside their account, visits the public site, returns to their account — dark still works there, white still shows here.

## 5. Look & Feel (UX)

- **Where it lives:** the public site layout shared by all thirteen public pages — one change covers the whole site, including the public inquiry form.
- **The one primary action:** none needed from visitors. The fix is invisible on a good day: the site simply looks right.
- **States:** default (white site) is the only state. No toggle appears on the public site (none exists today), so there is nothing to add or remove for users.
- **Labels and language:** unchanged — no wording, buttons, or menus are renamed or moved.
- **Enroll button:** unchanged on the white site; it additionally carries a dark variant as dormant insurance, matching the treatment the outline button already has, so it can never go muddy even if dark ever renders.

## 6. Business Rules

### Must always be true
- Every public page renders white under every combination of device setting and saved preference.
- The account portal's dark/light behavior is byte-for-byte unchanged: same switch, same saved preference, same appearance.
- The "Enroll for 2026" button is fully readable on the white site.
- The login page remains white.

### Must never happen
- A public page is never dark because of a device setting.
- A public page is never dark because of a preference saved from an account session.
- No theme switch or toggle ever appears on the public site.
- The account portal is never touched by this change — no shared behavior is altered.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Visitor's device is set to dark | Site renders white anyway |
| Browser holds a dark account preference | Site renders white anyway; the account keeps its own preference intact |
| Staff moves between dark account and public site | Each keeps its own appearance; neither leaks into the other |
| Visitor uses a dark-forcing browser extension | Out of our control; the site still declares white, the extension may override it |
| A future change accidentally re-enables dark switching | The dormant dark styling and the button's dark variant keep the page readable; the regression is visible in review, not silent |

## 7. Out of Scope
- Any change to the account portal: toggle, saved preference, appearance, behavior.
- Removing the public layout's dormant dark styling (kept deliberately as a safety net).
- Separating the shared saved preference into two names (unnecessary once the public site ignores it).
- A dark mode for the public site, now or later — explicitly not wanted.
- The librarian screens, API routes, and all other modules.

## 8. Success Checks
- [ ] Device set to dark → open the public site → every page white, Enroll button readable.
- [ ] Inside an account, switch to dark → open the public site in the same browser → still white.
- [ ] Back in the account, flip the toggle both ways → dark and light both still work there.
- [ ] Login page white, as before.
- [ ] Enroll button readable on the white site (dark insurance verified in code review, since no honest click-test exists for dormant styling).

## 9. Open Questions
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Public layout: `resources/views/PromotionalWebsite/layout.blade.php` — theme script at lines 8–22 (device check + shared-preference check + `/login` carve-out at 18–20). Change: the public layout never applies the dark marker; keep the carve-out (harmless) and keep all dark styling rules dormant (lines ~398–428).
- Account layout untouched: portal toggle, key, and behavior unchanged; no shared behavior altered.
- All thirteen public views extend the public layout (verified), including the public inquiry form — one layout change covers the site.
- Enroll button: `welcome.blade.php:187`, styled by `.btn-primary` (layout lines 286–304; navy background, white variable text). Add the missing dark variant beside the existing outline-button one (line ~423), so the text can never melt into the button.
- No theme switch exists on the public site (verified) — nothing to remove.
- Data/records touched: none. No preference, account, or content data changes.
- Roles/permissions involved: none — unauthenticated public pages plus a visual-only button variant.

## 11. Approval
> Approved by user on 2026-10-06.

## 12. Implementation Note (2026-10-06)

**Delivered:** public layout never applies the dark marker (device setting and shared account preference both ignored; `/login` carve-out and all dormant dark rules kept), plus a dormant dark variant for `.btn-primary` beside the existing outline rule. One file, no other file touched. All 5 §8 checks pass per user report (dormant button variant by code review — no honest click-test exists for it).

**Scope proof:** 16 views use the public layout (13 public + 3 auth); zero portal/account views — verified by codebase search, so the change cannot reach accounts structurally.

**Not committed by the agent** — the user runs git themselves (`AGENTS.md` §1.4).
