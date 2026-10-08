# Spec: Portal Request Account Wording

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Implemented**: 2026-10-08 — swapped helper to `Submit an email to receive your institutional credentials.` (line 529 only), link and modal unchanged, verified visually by user.

## 1. Why We Need This
First-time parents open the Account box and read inquiry language that feels formal. Email language tells them in plain words what to send to get their school login.

## 2. Who Is Affected
* **Prospective parent / student** — reads it once on the public site, knows to send their email.
* **Registrar / School Administration** — receives the same request as today, no extra work.
* **IT Admin** — checks it once visually.
* Untouched: cashier, teachers, principal, nurse, librarian.

## 3. Business Flow: Today vs After
- **As-is**: Visitor opens the Account box, sees `Don't have an account yet? → Request an Account → Submit an inquiry to receive your institutional credentials.`, taps through to the request form.
- **To-be**: Same steps, same form, but the helper reads `Submit an email to receive your institutional credentials.`
- **Preserved**: Tapping still opens the same request form; access stays controlled by School Administration; logins and approvals do not change.
- **Exceptions**: None — wording change only.

## 4. How It Should Work
1. Visitor opens the public site and opens the Account box.
2. Under `Don't have an account yet?` they see `Request an Account` with the new helper sentence.
3. Tapping it opens the same request form as today.

## 5. Look & Feel (UX)
- Lives in the public Account box only. One primary action stays the same: tap `Request an Account`.
- Layout, icons, order, and footer text stay exactly as today.
- Key states: box closed, box open logged-out, box open logged-in — helper reads the same in each.
- Plain language: title stays `Request an Account`; helper is the new sentence with period.

## 6. Business Rules
### Must always be true
- The helper reads exactly `Submit an email to receive your institutional credentials.` including the period.
- Tapping `Request an Account` still opens the same request form.
- The footer `Access is controlled by School Administration.` stays.

### Must never happen
- The link behind the row must not change to a personal email address.
- The title, order, icons, login rows, or who approves accounts must not change.
- Accounts must never be created by themselves without office approval.

### Edge cases and what happens then
- Saved page shows old line → refresh shows the new one; no action needed.
- Small phone screen → sentence wraps as today; no layout fix needed.

## 7. Out of Scope
- The request form itself, login page, approvals, account creation.
- Any other menu, page, or role's screen.

## 8. Success Checks
- [ ] The Account box shows the new sentence exactly, with period.
- [ ] Tapping `Request an Account` still opens the request form.
- [ ] `Log In to My Account` / `Go to My Dashboard` rows read exactly as before.
- [ ] Footer still reads `Access is controlled by School Administration.`

## 9. Open Questions (if any)
None — text-only swap keeping the same form, confirmed by you on 2026-10-08.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screen: public Account box only.
- Likely area: `resources/views/PromotionalWebsite/layout.blade.php` portal modal, `Request an Account` row (~line 529 `span.portal-option-desc` only).
- Do not touch: the link address on that row, the login rows, the footer, any other file.
- Data/records touched: none.
- Roles/permissions involved: public visitor reads; office approves as today.

## 11. Approval
> Approved by user on 2026-10-08.
