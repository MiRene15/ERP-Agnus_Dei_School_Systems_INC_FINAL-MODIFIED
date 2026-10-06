# Spec: Applicant Email Reliability

- **Status**: Implemented
- **Created**: 2026-10-06
- **Approved by**: user on 2026-10-06 (re-approved after strict 1-student-1-email change)
- **Implemented**: 2026-10-07

## 1. Why We Need This

When a family applies to the school, two emails carry the relationship: the login credentials after applying, and the "admission approved" notice later. Today both can silently fail — the family sees success while nothing arrives, or the approval email goes to a school mailbox nobody opens — and the registrar can see "failed" for an enrollment that actually succeeded. This spec makes every applicant email provable (verification link), honest (messages always match reality), and recoverable (resend everywhere a failure can strand someone).

## 2. Who Is Affected

- **Applicants / parents** — get credentials plus a verification link in their personal inbox; always told the truth about what arrived; never stuck without a recovery path.
- **Registrar** — sees a verified/unverified badge per applicant, honest approval results ("enrolled, email sent" vs "enrolled, email failed + resend"), and a one-click resend. Same Approve button, same document checks.
- **IT admin** — mail settings remain theirs; failures become visible in logs instead of mystery complaints.
- **Cashier, teachers, principal** — no visible change.

## 3. Business Flow: Today vs After

- **As-is**: family applies → account made, password emailed to personal inbox, instant full access, no proof the inbox is real. Registrar approves → enrollment saves, approval email goes to the school address, and any mail hiccup reports "failed to approve" for an enrolled student.
- **To-be**:
  1. Family applies → account created **unverified** → credentials **plus** verification link emailed to the personal inbox.
  2. Unverified login → "check your inbox" notice with a **resend** link (limited repeat sends).
  3. Link clicked → verified, full access.
  4. Registrar sees verified/unverified badge per applicant; approval sends to the personal address with school address as fallback only.
  5. Any failed send is logged with a **resend** where staff can reach it (applicant notice page + registrar admissions page).
- **Preserved**: honeypot + attempt limits + email-provider rules + no-duplicate-applications rule; document checks, holds, and locked-year rules still gate approval; the existing double-send guard; every send and failure in the activity log.
- **Exceptions**: walk-ins with no personal email skip verification (registrar confirms contact manually); expired or twice-clicked links give a plain message plus a fresh link; mail provider down never blocks account creation or enrollment; two parents applying one child with different emails stay as two applications for the registrar to merge by hand.

## 4. How It Should Work

1. Parent submits the inquiry form with a fresh personal email.
2. System creates the account as unverified and emails credentials + verification link to that personal inbox.
3. Parent logs in before clicking → sees "check your inbox," with resend.
4. Parent clicks → verified, normal access from then on.
5. Registrar opens admissions → each applicant shows verified or unverified.
6. Registrar approves → enrollment saves first, then the approval email goes to the personal address; the result message says which of the two happened and offers resend on failure.
7. Any resend (applicant or registrar) sends at most one fresh email and says so plainly.

## 5. Look & Feel (UX)

- **Where it lives:** the public inquiry form (same fields, new result messages), a verify-notice page for unverified logins, and the registrar's admissions pages (badge per row, resend next to failed sends). One primary action per screen: apply, verify/resend, approve.
- **Key states:** applied-and-waiting (check inbox); link expired/used (plain message + new link sent); mail failed (honest message + resend, never a fake failure); verified (normal); no-email walk-in (registrar-confirmed).
- **Plain language:** "Check your personal inbox — we sent your login and a confirm link," never jargon; public resend answers never hint whether an address applied.
- **Fewest clicks:** resend is one click; verification is one click.
- **What each role sees first:** parents see inbox guidance; the registrar sees badges before buttons.

## 6. Business Rules

### Must always be true
- Account creation and enrollment always save even if email fails.
- One application sends at most one credentials email; retries never duplicate.
- Verification links are single-use and expire; a fresh link is always available via resend.
- Every send and every failure is logged with who, what, and when.
- Family mail goes to the personal address first; the school address is fallback only.
- One personal email belongs to exactly one applicant. No sharing, no override — siblings need separate email addresses. (Existing shared addresses already in the database stay as-is; a hard database lock is a separate cleanup, not this spec.)

### Must never happen
- Email failure blocks or pretends to undo enrollment or signup.
- A success message shows when nothing was sent, or a failure message shows for something that succeeded.
- Family mail goes to an unreadable address while a personal one is known.
- Public pages reveal whether an address applied.
- Passwords or link tokens appear in logs.
- Anti-bot limits, duplicate rules, and approval gates (documents, holds, locked years) are weakened.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Mail provider down at signup | Account created; applicant told plainly the email didn't go through, with resend |
| Expired or twice-clicked link | Plain "no longer valid" message; fresh link sent |
| Walk-in with no personal email | Verification skipped; registrar confirms contact manually |
| Approval email fails | "Enrolled, email failed" + staff resend; never "failed to approve" |
| Same child, two parent emails | Two applications stand; registrar merges by hand — no auto-merge |
| Spam folder | Clear subject/sender, guidance text, and resend cover it |

## 7. Out of Scope

- Payment-reminder emails (same address lesson, but cashier-owned — its own spec).
- SMS or any non-email channel.
- Auto-merging duplicate applications.
- Changing the inquiry email-provider rules or attempt limits.
- CAPTCHA on the inquiry form (follow-up only if bot spam appears despite verification).
- Cosmetic cleanup of already-correct password-reset behavior.

## 8. Success Checks

- [ ] Apply with a fresh personal email → credentials + verification link arrive in that inbox.
- [ ] Log in before clicking → inbox notice with working resend; click → full access.
- [ ] Used/expired link → plain message + fresh link sent.
- [ ] Same personal email applies again → blocked with guidance to use a different address; no override path.
- [ ] Registrar sees verified badges; approving shows "enrolled, email sent" and the family inbox gets it.
- [ ] With sending broken → signup/enrollment still save, messages admit the email failed, resends recover it.
- [ ] Double-clicking Approve or retrying never sends a duplicate email.

## 9. Open Questions

None — all decisions made in interview (both emails in scope, personal-inbox-only assumption, gate-until-clicked, resends included, strict 1-student-1-email with no override, anti-spam posture, out-of-scope list).

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: `PromotionalWebsite/inquiry` (form + result messages); verify-notice page (default auth `verify-email` pages exist); registrar `admissions-index` / `admissions-show` (+ `partials/admissions-show-results`, which already shows `personal_email`); login flow.
- Likely areas of the codebase (from code inspection):
  - Inquiry send: `app/Http/Controllers/PromotionalWebsite/InquiryController@store` (account survives mail failure today via inner try/catch — keep; add verification-link send + honest failure message + applicant resend). Route `POST /inquiry` carries `throttle:inquiry` (5/hr per IP, 3/day per email — untouched); honeypot `website` field stays.
  - Approval send: `app/Http/Controllers/Portal/RegistrarAdmissionController@approve` (move mail out of the outer try into its own guarded block mirroring the inquiry pattern; resolve family address personal-first; result message distinguishes enrolled+sent vs enrolled+failed with staff resend).
  - Mailers: `app/Mail/InquiryCredentialsMail` (modern `content()` markdown — the template to extend with the link); `app/Mail/AdmissionCredentialsMail` (only mailer still on legacy `build()` + text-only — normalize to `content()` like its five siblings); `app/Mail/InquiryVerificationMail` (dead code, empty constructor — reuse or remove at build).
  - Verification wiring: `email_verified_at` column exists; `VerifyEmailController`, prompt, and resend routes exist in `routes/auth.php`; `User` has verification switched off (commented-out interface) — switch on + gate. `User::routeNotificationForMail` already prefers personal email (keep — it also fixes reset delivery). Legacy `inquiries` table (`pending`/`verified`) is unused by the current flow — do not revive implicitly.
  - Guards to keep: `SkipsDuplicateSends` + `_idempotency_key` markers (both sends already wired); duplicate name+email closure in inquiry validation (extended to strict one-email-per-applicant, no override); `throttle:search` untouched.
- Data/records touched: users (`email_verified_at`), students (`personal_email`), admissions/enrollments (unchanged shapes), activity log (new send/failure entries), idempotency keys (existing mechanism).
- Roles/permissions involved: applicants (verify/resend), registrar (badges, approve, override, staff resend), IT admin (mail settings). Cashier/teacher/principal flows untouched.

## 11. Approval

> Approved by user on 2026-10-06.

## 12. Closure Log (2026-10-07)

**Built in four slices:** (1) verification switch (`MustVerifyEmail` + backfill migration + applicant-route gate); (2) inquiry send (verification link, honest failure message, applicant resend via existing throttled route); (3) approval send (`FamilyEmailService` personal-first guarded send, normalized mailer, staff resend endpoint + FormRequest); (4) registrar UI (verified pills, resend button, strict one-email-per-applicant validation). Static checks `php -l` / `route:list` / `view:cache` / `migrate` clean; live inbox receipt confirmed delivery end to end.

**Root cause found during rollout (infra, outside code):** every mailer implements `ShouldQueue`, so `send()` parks mail in the `jobs` table — but no worker runs, and `QUEUE_CONNECTION` was `database`. All mail piled up unsent with success screens everywhere. Fix: `QUEUE_CONNECTION=sync` (all sends already guarded, so inline delivery is safe). Stuck backlog recovers via resends, not automatically. Dockerfile now installs `ca-certificates`; `.dockerignore` excludes the frozen config cache so dashboard env always rules.

**Files changed (one commit, one spec):**
- `app/Models/User.php`, `database/migrations/2026_10_06_000002_*`, `routes/web.php` (+1 resend route)
- `app/Http/Controllers/PromotionalWebsite/InquiryController.php`, `app/Mail/InquiryCredentialsMail.php`, `resources/views/emails/inquiry_credentials.blade.php`, `resources/views/PromotionalWebsite/inquiry.blade.php`
- `app/Services/FamilyEmailService.php` (new), `app/Mail/AdmissionCredentialsMail.php`, `app/Http/Controllers/Portal/RegistrarAdmissionController.php`, `app/Http/Requests/Registrar/ResendAdmissionEmailRequest.php` (new)
- `resources/views/portal/registrar/admissions-index.blade.php`, `partials/admissions-results.blade.php`, `partials/admissions-show-results.blade.php`
- this spec file.
