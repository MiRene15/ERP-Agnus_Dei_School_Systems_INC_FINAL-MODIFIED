# Spec: Begin Admission Labels

- **Status**: Implemented
- **Author**: Muse Spark (spec interview)
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07 (re-approved after Open Questions resolution)
- **Implemented**: 2026-10-07 — labels-only rename live in 3 views; all 5 success checks passed.

## 1. Why We Need This
Parents see the word "Inquiry" and don't realize it's the first step to get a student account and start enrollment. Clearer words will help more families start on their own without calling the school for help.

## 2. Who Is Affected
- **Prospective parents and students** — see clearer words and know what to do next.
- **Registrar** — gets the same applications as today, just with fewer confused follow-ups. No change to how they review or approve.
- **IT admin** — no new upkeep; same page and same settings.

## 3. Business Flow: Today vs After
- **As-is**: Visitor clicks `Inquiry` in the top menu → lands on a page titled "Admission Inquiry" → clicks a button that says "Generate Credentials & Inquire" → sees a pop-up that says "Inquiry Submitted!"
- **To-be**: Same steps, same page, new words — clicks `Begin Admission` → sees "Apply For Student Account" → clicks "Apply for Student Account" → sees "Check Email Inbox."
- **Preserved**: The same details are collected (first name, last name, personal email). The same checks, limits, and registrar approval steps stay exactly as today.
- **Exceptions**: Same as today — duplicate applications, email send failures, and invalid emails behave exactly as they do now, only with the new titles shown.

## 4. How It Should Work
1. Visitor opens the public site and clicks **Begin Admission** in the top menu.
2. Page opens titled **Apply For Student Account** with the same short form.
3. Visitor fills first name, last name, personal email and clicks **Apply for Student Account**.
4. If details are missing or invalid, the same helpful message appears under the field.
5. If accepted, the pop-up appears titled **Check Email Inbox** telling them to check email for login details.
6. Contact page card heading changes from "Inquiries" to "Admissions" and link text changes from "Inquiry Form" to "Begin Admission form" — same link, same page.

## 5. Look & Feel (UX)
- Where it lives: public site top menu, the one application form page, its success pop-up, and the contact page card that links to it. One primary action per screen: start the application.
- Default: new words show on load. Empty: form shows blank fields as today. Error: same field messages as today. Success: new pop-up title. Permission-denied: not applicable — this is a public page.
- Plain-language labels; same layout, colors, and order of fields — only words change.
- What the user sees first: menu says Begin Admission; on the page the biggest heading is Apply For Student Account.

## 6. Business Rules
### Must always be true
- The menu, page title, button, and pop-up title always use the new words.
- The web address, saved records, and registrar review steps stay the same as today.
- Every screen still tells the parent what to do next in plain words.

### Must never happen
- Parents are never sent to a broken or missing page because of this rename.
- An application is never lost, duplicated, or treated differently because of the new words.
- Internal email titles and registrar-side wording never change in this version.

### Edge cases and what happens then
- Saved bookmarks or Facebook posts using the old address still open the same page — only the visible words are new.
- If a parent submits twice with the same details, the same duplicate message appears as today.
- If the login-details email fails to send, the same "use Forgot Password" guidance appears as today.

## 7. Out of Scope
- Changing the `/inquiry` web address to `/begin-admission`.
- Renaming internal code names, email subject lines, or registrar-side labels.
- Changing what details are collected, how accounts are approved, or CAPTCHA/verification rules.
- Updating printed flyers, Facebook posts, or SEO — can be a follow-up.

## 8. Success Checks
- [ ] Top menu shows "Begin Admission" and opens the application page when clicked.
- [ ] That page title reads "Apply For Student Account" and the button reads "Apply for Student Account."
- [ ] After applying, the pop-up title reads "Check Email Inbox."
- [ ] Contact page heading reads "Admissions" and link reads "Begin Admission form" and still opens the same page.
- [ ] Old saved links/bookmarks still open the page without errors.

## 9. Open Questions (if any)
- None — resolved 2026-10-07 per user approval of defaults:
  - Contact card heading "Inquiries" becomes "Admissions"; link text "Inquiry Form" becomes "Begin Admission form" (same `/inquiry` link).
  - Page subtitle stays word-for-word in v1.
  - Pop-up body text stays; only the title changes to "Check Email Inbox" in v1.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
  - `resources/views/PromotionalWebsite/layout.blade.php` line ~481 (top menu link)
  - `resources/views/PromotionalWebsite/inquiry.blade.php` lines 6 (page title), 48 (submit button), 70 (modal title)
  - `resources/views/PromotionalWebsite/contact-information.blade.php` line 30-31 (card heading + link text)
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - Routes `GET /inquiry` and `POST /inquiry` in `routes/web.php` stay unchanged (with `throttle:inquiry`).
  - `app/Http/Controllers/PromotionalWebsite/InquiryController@show/@store` — no logic change, labels only.
  - Mailers `app/Mail/InquiryCredentialsMail`, `InquiryVerificationMail` and email views untouched in v1.
  - No database change.
- Data/records touched: none — display text only.
- Roles/permissions involved: public visitors (no login); registrar view unchanged; no permission change.

## 11. Approval
> Approved by user on 2026-10-07. Re-approved 2026-10-07 after Open Questions resolution (contact heading/link wording locked).
