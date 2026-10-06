# Spec: Safe Actions Everywhere

- **Status**: Implemented (2026-10-06 — children A, B, C all Implemented and verified; coverage rule holds)
- **Created**: 2026-10-05
- **Approved by**: user on 2026-10-05
- **Scope decision**: user confirmed 2026-10-05 — **all writes protected, all reads calmed, across all nine roles**

## 1. Why We Need This

Two things go wrong in this system today, and both are quiet.

**The same action can arrive twice.** A cashier taps "Process Payment" and the connection is slow, so they tap again. Or the page times out and the browser sends it again. Or a stray double-click. The system has no way to tell an accidental repeat from a deliberate one, so it does the work twice — and a doubled payment has to be found and unwound by hand afterwards. Nothing tells anyone it happened.

**Calm searching depends on everyone remembering.** Pausing before a search is built in by hand at each place it's used — noted in the shared JavaScript file as a convention for developers to follow. A developer who doesn't know the convention writes a search that fires on every keystroke. That is exactly what was found on the Process Payments page while testing this work: not a race, and not broken code, but a search that behaved differently from its neighbours because nobody was reminded.

**The rule this spec sets:** every action that changes something is protected exactly once, everywhere, for everybody — and every action that only reads is automatically calm. Not because someone remembered, but because the system does it.

## 2. Who Is Affected

**All nine roles**, without exception. This is the point of the spec — protection that covers only the payment screen leaves a teacher, a librarian and a principal unprotected.

- **Cashier** — payments, receipts, discounts, refunds. The highest financial consequence.
- **Teacher** — grades, assessments, attendance.
- **Registrar** — admissions, sections, subjects, promotions, report cards.
- **Principal** — approvals, promotions, announcements, schedules, teacher assignments.
- **Directress** — fee schedules, graduation fees, discount approvals, promotion sign-off, school years.
- **Librarian** — books, loans, returns, visits.
- **Nurse** — clinic logs.
- **Admin** — staff and student accounts, school settings.
- **Student** — admission and enrolment applications.
- **IT admin** — owns this work; also benefits, being the person who previously had to unpick doubled records.

## 3. Business Flow: Today vs After

- **As-is**: a submission can be performed twice and nothing notices. Searches are calm on some screens and frantic on others, depending on what the developer remembered.
- **To-be**:
  1. Staff fill a form and submit once. It works.
  2. If the same submission arrives again — a second tap, a retry, a double-click — the system **recognises it and does nothing further**, then tells the person plainly that the work was already saved.
  3. Every search waits for a pause before it fires, on every screen, whether or not anyone remembered to build that in.
- **Preserved**: **deliberate repeat work is never blocked.** Two separate payments for one family, two books returned, two classes marked — each is a distinct submission and each succeeds. Only the *identical submission arriving twice* is recognised and set aside.
- **Exceptions**:
  - **A refusal must never look like a failure.** If a repeat is recognised, the person is told what already happened, not shown an error. They should leave more certain than when they started.
  - **A genuine network retry is the best case, not the worst.** Losing a response and having the browser resend is precisely where this protects the school.
  - **Nothing is stored about the person.** A record of a submission keeps its reference and outcome, never the contents.
  - **Read-only screens are not given write protection.** Listing, filtering and searching changes nothing, so there is nothing to protect. Recorded in §7 as a deliberate decision.

## 4. How It Should Work

### Part A — Calm searching, everywhere, automatically

1. Any search or filter waits for a pause before it fires, on every screen, for every role.
2. This is built into the shared behaviour once. No screen author has to remember it.
3. Where a screen has always waited a particular length of time, that timing is kept.
4. Anything else uses one standard pause.

### Part B — One tap, one action

1. Any button that changes something is inert the moment it is pressed, until the press has been answered.
2. The button shows it is working, so the person can see their tap registered.
3. If the press is answered with an error, the button becomes usable again — a person is never left with a dead button.
4. This applies to every form on every screen, including ones added later.

### Part C — One submission, one effect

1. Every form that changes something carries a single-use reference, created when the form loads.
2. The system records that reference as it processes it.
3. If the same reference arrives again, the work is **not repeated**. The system returns the outcome of the first time and adds nothing.
4. A new submission — the same form filled in again, or a different form — always carries a new reference and is always processed.
5. When a repeat is recognised, the person is told what already happened, in ordinary language.

### The rule of coverage

**Every change to data is protected. Every action that only reads is calmed.** Applies to all nine roles and to everything added in future — including work not yet written.

## 5. Look & Feel (UX)

- **Where it lives:** nothing new to learn and nothing new to visit. It is behaviour that should be invisible when everything is working.
- **The one primary action:** carry on working. Staff should not be conscious of this feature at all on a good day.
- **When a repeat is recognised:** a calm, plain confirmation — the equivalent of *"That payment was already saved."* Not an error, not a warning, not a red box. The person should feel reassured, not alarmed.
- **While working:** the pressed button shows it is busy. Brief, unobtrusive, and always released.
- **Wording:** ordinary words. "Already saved" rather than any technical term. A cashier should never see the word "idempotent".
- **Never:** a silent no-op. If something was set aside, the person is told.

## 6. Business Rules

### Must always be true
- **Every write in the system is protected**, across all nine roles — approximately 85 endpoints, enumerated in §10.
- A repeated submission of the **same** reference changes nothing and is reported as already done.
- A **deliberate** repeat — a fresh form, fresh data — always succeeds.
- Every search in the system waits for a pause, without its author having to arrange it.
- A busy button always becomes usable again, whatever the outcome.
- What is recorded about a submission never includes its contents or anything personal.

### Must never happen
- **Two genuinely different actions are ever refused** because they resemble each other.
- A person is shown an error when the work in fact succeeded. That is worse than a duplicate, because they will try again.
- A protected screen becomes unusable and leaves someone unable to work.
- A repeat is set aside **silently**.
- Protection is applied by remembering to add it. It must be the default, not a checklist item.
- Personal data, names, or amounts are kept alongside a submission reference.
- A queued or background action is affected in a way that makes it fail silently.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Response lost, browser resends | Recognised as a repeat. Person told it was already saved |
| Button pressed twice deliberately for different work | Two distinct submissions; both succeed |
| Same form submitted again after an edit | New reference; processed as new work |
| Person reloads the form and submits again | New reference; processed as new work |
| A repeat is recognised | Told what already happened, in plain words, no error styling |
| Two people submit the same thing at once | Two separate submissions; both are the person's own work |
| Pressed button and the save fails | Button becomes usable again; the error is shown as it always was |
| Very old page still open after a long break | Reference has expired; treated as new work |
| A background job retries | Must not double-apply. Covered in §10 |

## 7. Out of Scope

- **Write protection on read-only actions.** Listing, filtering, searching and viewing change nothing, so there is nothing to protect. This was put to the user on 2026-10-05 and the decision was made: protection covers **everything that writes**; calm behaviour covers **everything that reads**. Applying write protection everywhere would guard against nothing while risking the refusal of legitimate repeat work — a teacher marking two sections, a librarian returning two books.
- **The API routes.** These serve outside clients rather than screens and have their own authentication model. Named in §10 so the boundary is deliberate rather than accidental.
- **Changing any screen's appearance**, apart from the button and repeat-notice behaviour described here.
- **Payment or refund business rules.** This changes how often an action happens, never what it does.
- **Performance work on the searches themselves.** Calming them is in scope; making them quicker is `cashier-search-speed.md`.

## 8. Success Checks

**Calm searching**

- [ ] Every search screen waits for a pause before searching, without any screen having been changed.
- [ ] A screen that already used a particular pause keeps using it.
- [ ] A new search screen behaves correctly without anyone adding anything to it.

**One tap, one action**

- [ ] Pressing a saving button twice sends one submission, not two.
- [ ] A pressed button shows it is working.
- [ ] A button is never left unusable after any outcome, including failure.

**One submission, one effect** — tested on at least one endpoint per role

- [ ] A double-pressed save performs the work once.
- [ ] A resubmission after a lost response performs the work once and says so.
- [ ] A repeat shows a calm confirmation, not an error.
- [ ] Two deliberate separate submissions both succeed.
- [ ] **No role's work is refused that should have succeeded.** Checked by role: Admin, Registrar, Cashier, Teacher, Librarian, Nurse, Student, Directress, Principal.

**Nothing regressed**

- [ ] Every existing screen still saves correctly.
- [ ] No screen shows a technical word about repeats.
- [ ] Nothing personal is kept with a submission reference.

## 9. Open Questions

None.

## 10. Technical Notes

*Plain-language pointer only — the source of truth is the code and this appendix.*

**Coverage inventory — mutating endpoints, measured 2026-10-05 from `routes/web.php`:**

| Role | Writes |
|---|---|
| Principal | 14 |
| Directress | 12 |
| Librarian | 11 |
| Registrar | 10 |
| Teacher | 7 |
| Admin | 6 |
| Student | 6 |
| Cashier | 5 |
| Nurse | 2 |
| Registrar + Cashier | 1 |
| Registrar + Principal | 2 |
| Shared (profile) | 2 |

**76 explicit POST/PATCH/PUT/DELETE routes**, plus 3 `Route::resource` declarations contributing `store`, `update` and `destroy` — **approximately 85 write endpoints in total.** The rule is universal: every one of them, plus anything added later. This inventory exists so "did we miss any?" is answerable by reading the routes file rather than trusting memory.

**Part A — mechanism.** `resources/js/app.js` is loaded by `resources/views/portal/layouts/app.blade.php:28` via `@vite`, on **every portal page for every role**. An Alpine directive registered once there therefore applies universally with no per-page work. The file already documents the hand-applied convention at line 13 (`@input.debounce.600ms`), and the existing helper already implements abort plus a sequence guard (`app.js:79-85`, `:102`, `:114`) — the correct pattern, currently opt-in. Note per-screen debounce counts vary (`books.blade.php` has 7 bindings, `payments.blade.php` 1), which is the inconsistency this removes.

**Part B — mechanism.** A single Alpine directive bound globally, applied declaratively to submitting forms. Must release the button on both success and failure, including a thrown fetch.

**Part C — mechanism, and an important caveat.** **The installed framework build has no idempotency support** — verified by searching `vendor/laravel/framework/src` on 2026-10-05, no match. The project is on `laravel/framework: ^12.0`. If a later patch release introduces a supported mechanism, prefer it over a custom one; otherwise this is built here:

- A single-use reference generated per form load, submitted with it.
- A record of processed references — **a dedicated table, not the cache.** Cache entries are evicted, and an evicted reference would allow a duplicate through. `CACHE_STORE=database` in production does not make cache suitable for this.
- Middleware rejecting a repeat before the controller runs, returning the original outcome rather than an error page.
- Retention long enough to cover a realistic retry window; entries older than that may be forgotten, which is safe because it restores the old behaviour rather than blocking work.
- **Excluded from the reference record:** any form content, name, or amount. Reference, route, outcome and timestamp only.

**A background job that retries is a second application of the same risk** and is named here so it is not overlooked: anything queued must also be safe against running twice. `QUEUE_CONNECTION=database` is set in production, and the current image start command (`Dockerfile:46`) runs only migrations and the web server, so no worker is running today — worth confirming intended behaviour separately.

**Out of scope by design:** `routes/api.php` (26 routes) serves outside clients with a different authentication model; it is excluded deliberately, not overlooked.

**Related work, not duplicated here:** `stale-search-indicator.md` (which search belongs to which list), `cashier-search-speed.md` (how many requests a search makes), `search-resilience.md` and children (already implemented).

**Delivery note.** This spec covers roughly 85 endpoints across three mechanisms. It may reasonably ship in three passes — A, then B, then C — with each verified independently. That is a delivery sequence, **not a narrowing of scope**: all three parts are in scope for this spec, and the spec is not complete until all three land.

## 11. Approval

> Approved by user on 2026-10-05, with the coverage rule in §4: all writes protected, all reads calmed, across all nine roles. All three parts in scope; to be delivered in three verified passes.
>
> Closed 2026-10-06: children `safe-actions-calm-search.md`, `safe-actions-one-tap.md`, `safe-actions-one-submission.md` all Implemented and verified (user sign-off per child). Coverage rule holds across all nine roles.