# Spec: Safe Actions — One Submission

- **Status**: Implemented (2026-10-06 — all 9 §8 checks pass, see §12)
- **Created**: 2026-10-05
- **Approved by**: user on 2026-10-05
- **Revised**: 2026-10-06 — §4.1 reference-timing clarification (per-submission minting) + retention pinned to 3 days (§§3, 6, 10). Changed parts reset to **Draft** per `spec-rules.md` §6, then **re-approved by user on 2026-10-06** as part of the `/exec-spec` plan approval.
- **Parent**: `safe-actions-everywhere.md` (child C of 3 — needs B; completes the set)

## 1. Why We Need This
Child B stops the hammers it can see — five rapid taps while the button is still busy. But two retries arrive *after* B has released: the connection drops so the browser resends a finished submit, or a queued mail job retries after a hiccup. B's lock is already gone, so only a recorded marker recognizes them. Without C, that resend writes the payment twice or mails the parents twice, and nobody is told it happened.

## 2. Who Is Affected
- **Cashier** — no double charge on resend; the money case.
- **Teacher, registrar, librarian, nurse** — no double save/return/log on resend.
- **Principal, directress, admin** — no double approve on resend.
- **Students, parents** — no duplicate admission/grade/reminder mail from a retried job.
- **IT** — stops unpicking doubles and apologizing for duplicate mails.

## 3. Business Flow: Today vs After
- **As-is (after B)**: rapid hammers stopped, but a lost-response resend or a retried mail job applies twice with no notice.
- **To-be**:
  1. Form loads → carries a single-use reference.
  2. Staff submits → system records the reference as it processes.
  3. Same reference again → work NOT repeated → original outcome returned + calm "already saved."
  4. Fresh/edited form → new reference → always processed.
  5. Named writing job queued → carries its own once-only marker → retry sees it → sends once.
  6. Old references/markers expire after **3 days** (daily prune) → expiry restores old behavior, never blocks.
- **Preserved**: what each save/mail does; B's busy + wording ("already saved" reads the same); audit (one intent = one row; repeat returns original, logs nothing new); Search instant from A.
- **Exceptions**: two people submitting the same thing = two markers = both succeed (their own work); very old page = expired marker = treated as new work; no worker running = job rows wait safely (ops follow-up, not built here).

## 4. How It Should Work
1. Every form that changes something carries a single-use reference, created per submission attempt — the shared submit guard mints a fresh reference at submit time, so an edited-and-resubmitted form always carries a new reference while a browser-level resend replays the original one.
2. The system records that reference as it processes it.
3. Same reference again → not repeated; original outcome returned + calm plain confirmation.
4. New submission (fresh form, edited data) → new reference → always processed.
5. Named writing jobs (admission, grade, reminder mails) carry a once-only marker; a retry with a seen marker sends nothing.
6. Repeats are told what already happened, in ordinary language — never an error, never silent.

## 5. Look & Feel (UX)
- Invisible on a good day — same screens, same buttons, B's busy unchanged.
- On a recognized repeat: calm confirmation in the existing success spot (*"That payment was already saved"* style). Not red, not warning, never the word "idempotent."
- Duplicate mails never reach parents — no "oops, twice" mail.
- Back/refresh/new-tab unchanged.

## 6. Business Rules
### Must always be true
- Every write (~85 endpoints, all 9 roles) + named writing jobs carry a once-only marker.
- Same marker twice changes nothing and returns the original outcome + calm notice.
- Fresh intent (new reference) always succeeds.
- Stored marker keeps reference/route-or-job/outcome/time only — never contents, names, or amounts.
- Markers expire after **3 days** (daily prune); expiry restores old behavior, never blocks.
- Protection is the default, not something anyone remembers to add.
### Must never happen
- Two genuinely different intents refused for resembling each other.
- A repeat shown as an error (they'll retry again — worse than a duplicate).
- Personal data, names, or amounts kept with a marker.
- A queued/background retry double-applies.
- A repeat set aside silently.
- A save blocked because its marker expired (expiry = treated as new, never refused).
### Edge cases and what happens then
- Response lost, resend → recognized, "already saved", one write.
- Same form after edit → new reference → new work.
- Reload form → new reference → new work.
- Two people same thing → two markers → both succeed.
- Very old page → expired → new work, not blocked.
- Job retry → marker seen → one mail.
- No worker running → rows wait safely, none lost, none doubled on later run.
- Save fails → nothing recorded (fail = no marker; retry is fresh).

## 7. Out of Scope
- Auth/refresh tokens (reference-only, confirmed).
- Running/supervising the queue worker and schedules (ops follow-up).
- API routes (different auth, excluded by parent).
- Search pause tuning (A) and button lock wording (B).
- All-jobs-including-future rule; new screens beyond the repeat notice.

## 8. Success Checks
- [ ] Double-pressed save → work done once.
- [ ] Resubmission after lost response → once + calm confirmation, not error.
- [ ] Repeat → calm confirmation, never error styling.
- [ ] Two deliberate separate submissions → both succeed.
- [ ] No role's legit work refused (Admin, Registrar, Cashier, Teacher, Librarian, Nurse, Student, Directress, Principal).
- [ ] Retried admission/grade/reminder job → one mail, rest dropped.
- [ ] No worker running → rows wait safely, none lost, none doubled on later run.
- [ ] Nothing personal kept with a marker.
- [ ] Expired old page → treated as new work, not blocked.

## 9. Open Questions
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Coverage: ~85 write endpoints measured from `routes/web.php` (81 explicit POST/PATCH/PUT/DELETE + 3 `Route::resource` store/update/destroy) across all 9 roles + shared profile; named writing jobs: the 6 mail classes in `app/Mail` (admission, grades, inquiry ×2, payment confirmation, payment reminder) + 12th `reminders:payment` (`SendPaymentReminders`). Rule is universal: every one of them, per parent coverage rule.
- Framework has no idempotency support (verified `vendor/laravel/framework/src` 2026-10-05); project is `laravel/framework ^12.0` — prefer a later supported mechanism if one appears, else build here.
- Screens: single-use reference per submission attempt (minted by the submit guard) + dedicated table (reference/route/outcome/timestamp — not cache: `CACHE_STORE=database` still evicts; an evicted reference re-allows a duplicate) + middleware rejecting repeats before controllers, returning original outcome. Retention is 3 days via daily prune; expiry = old behavior, never a block. Excludes form contents/names/amounts.
- Jobs: once-only marker per queued writing job (job ID–scoped); retry with seen marker sends nothing. `QUEUE_CONNECTION=database` (`config/queue.php:16`, `jobs` table); image start (`Dockerfile:46`) runs migrations + web server only, no worker — rows waiting safely is the v1 guarantee; running the worker is ops follow-up.
- Untouched: `routes/api.php` (26 routes, different auth), A pause, B lock wording, money/grade rules.
- Related, not duplicated: `stale-search-indicator`, `cashier-search-speed`, search-resilience family.

## 11. Approval
> Approved by user on 2026-10-05.
>
> Revised 2026-10-06 (§4.1 per-submission reference timing; 3-day retention in §§3, 6, 10) and re-approved by user on 2026-10-06 as part of the `/exec-spec` implementation-plan approval.

## 12. Implementation Note (2026-10-06)

**Delivered:** reference table + middleware + guard minting + job markers, all nine roles; verified live on a temp :8020 server against Supabase (cashier double-submit cycle with full revert, per-role waves, Mail::fake double-send, prune + expiry proofs). All 9 §8 checks pass. Zero residue except truthful audit rows and self-pruning key rows.

**Fixed during verification, recorded so it is not reintroduced:** bare `throw;` is a parse error — rethrow needs the variable; casts/`Str::isUuid` explode on crafted array input, so references are strict-`is_string`-checked first; `Mail::send()` on a `ShouldQueue` mailable queues behind the scenes (no worker runs — nothing sends until one does); `SendQueuedMailable` forwards no job middleware, so retry-safety lives in the mailable `send()` override; uuid columns reject semantic markers, hence deterministic-UUID `referenceFor()`; mail lookups are route-scoped or HTTP rows would suppress chained mails; repeat notices need a success spot on AJAX-shell pages (added to the financial shell). Single quotes group nothing in CMD — double-quote tinker outer strings.

**Not committed by the agent** — the user runs git themselves (`AGENTS.md` §1.4).
