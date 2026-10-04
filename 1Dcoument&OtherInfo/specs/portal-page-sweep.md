# Spec: Portal Page Sweep

- **Status**: Draft — **not approved, not started**
- **Created**: 2026-10-04
- **Origin**: deferred from `restore-blocked-admin-pages.md` §7, question 3 option 2 ("visit every page of every role")
- **Related**: `safe-release.md` v2 (automated per-role check) — this is the manual precursor to it

## 1. Why We Need This

Three dead pages were found on 4 October by reading the server log — not by looking at the system. A page that nobody has opened since it broke leaves **no trace at all**. So we know of at least three broken pages purely by luck, and we cannot honestly claim to know how many there are.

This is the same class of problem as the invisible production log: we are relying on a user to tell us something we should be able to see ourselves.

A one-off sweep will not solve that permanently — it is a snapshot that starts decaying the moment it finishes. But it establishes a known-good baseline once, reveals unknown breakage now rather than at the worst moment, and gives the automated check something to be measured against.

## 2. Who Is Affected

- **IT admin** — does the work.
- **Every role** — Registrar, IT admin, Principal, Directress, Cashier, Teacher, Nurse, Librarian, Student, Admin. Each one's pages get proven, and broken ones get found and specified.
- **Parents and students** — indirectly, through whichever pages turn out to be broken.

## 3. Business Flow: Today vs After

- **As-is**: a page breaks. Nobody finds out until a member of staff needs that specific page at that specific moment — possibly weeks later, possibly on the worst day of the term.
- **To-be**:
  1. Work one role at a time, in order of business impact.
  2. Open every page that role can reach — from the left menu, and then everything reachable from those pages.
  3. Record pass or fail for each. On a failure, record the exact error text.
  4. **Stop recording. Start nothing.** Fixing during the sweep loses your place.
  5. Afterwards, triage the failures into work that gets specified.
- **Preserved**: nothing about the site changes. This is a read-only exercise apart from the minimum needed to prove a form saves.
- **Exceptions**:
  - **Some pages may legitimately fail for a legitimate reason** — a page that needs data that does not exist yet, or a permission that correctly denies access. Record what it showed and judge it separately, not as a fault.
  - **Do not leave test data behind.** Prefer opening a form and cancelling. Where a save must be proven, use an obviously temporary record and delete it before you finish that role.
  - **A redirect to the login screen is not a pass.** It usually means your session ended mid-sweep.

## 4. How It Should Work

1. Work in this order — highest business impact first, so that if time runs out the most important roles are already proven: **Registrar → IT admin → Principal → Directress → Cashier → Teacher → Nurse → Librarian → Student → Admin**.
2. For each role:
   - Open every item in the left menu.
   - From each, follow everything else that role can reach: sub-pages, tabs, detail pages, create and edit forms (open, then cancel), report tabs, and export links.
   - For each page, record: **pass / fail**, and for a failure the **exact error text**.
3. Do not fix anything during the sweep. Collect first.
4. When a role is finished, tidy up any temporary records you created.
5. At the end, produce one triage list: each failure marked **needs a spec**, **slow but working**, or **working as intended**.

## 5. Look & Feel (UX)

- **Where it lives:** one record, so the next person can see the baseline. Suggested: a results table in this spec, one section per role.
- **The one primary action:** open a page and record what happened. Nothing else.
- **How results read:** role, page, pass or fail, error text if it failed. Plain language — a registrar should be able to read their own line without being told what HTTP means.
- **No jargon in the results.** "Failed: server error" beats "500 from CashierController".

## 6. Business Rules

### Must always be true
- **Every page every role can reach is opened and recorded**, including forms and tabs, not only menu items.
- Every failure is recorded with its **exact error text, before any attempt to fix it**.
- **No test data is left behind** in the live system.
- Every failure ends the sweep marked as one of: needs a spec / slow but working / working as intended.

### Must never happen
- **Fixing during the sweep.** The moment you start fixing, you lose your place and your coverage becomes a guess.
- A page is never described as fine without someone having actually opened it.
- A failure is never closed as "probably a permissions thing" without recording what it said.
- A temporary record is never left behind.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| A page needs data that does not exist | Record it as such. A working empty state is a pass. |
| A page correctly refuses access | Record "denied as intended". Not a fault. |
| The session ends mid-sweep | Sign back in and resume from your record. Do not assume you know where you were. |
| Two roles share a page | Test it once per role — the page may behave differently for each. |
| A page is slow but loads | Record it as slow. Slow is its own problem and deserves its own spec. |
| You find a page that is broken for everyone | Stop, record it, and deal with it before continuing the sweep. That is a live outage, not a finding. |

## 7. Out of Scope

- **Fixing what the sweep finds.** Findings become their own specs. This document produces a list, not repairs.
- **The three already-known broken pages** — Subjects, Sections and Staff Accounts. Already specified in `restore-blocked-admin-pages.md`; exclude them from the findings.
- **The API routes.** They are not pages a person uses, and they need a different kind of test entirely.
- **The promotional website** and any public pages outside the portal.
- **Any lasting safeguard.** This work decays. The durable version is the automated per-role check in `safe-release.md` v2, which needs a working test runner first (`phpunit.xml` currently declares MySQL while production is PostgreSQL, and PHPUnit is not installed).

## 8. Success Checks

- [ ] Every role in §4 has a recorded pass or fail for **every page it can reach**.
- [ ] Every failure has its **exact error text** recorded.
- [ ] **No test data remains** in the live system.
- [ ] A triage list exists, with every failure marked as needs-a-spec / slow / working-as-intended.
- [ ] Each failure marked *needs a spec* has either a spec written or a recorded decision not to fix it.
- [ ] The sweep is not described as complete while any role has an unrecorded page.

## 9. Open Questions

- Whether to run this against the live site or a copy with production-like data. Live is the only honest test of a page's real behaviour, but it carries the test-data risk noted in §3. **Decide before starting, not during.**

## 10. Technical Notes

- **Size of the job.** `routes/web.php` defines ~206 explicit routes plus 3 resource routes that expand to roughly 6 actions each, giving **~224 portal pages** across 9 roles. `routes/api.php` adds 26 API routes, out of scope for this sweep. Expect roughly a day including recording and triage, at about a minute per page.
- Route inventory: `routes/web.php`, `routes/api.php`. Role guards appear at 12 places, covering roles 1–9.
- The three known-broken pages and their cause are recorded in `restore-blocked-admin-pages.md` §10 — useful as the worked example of what a finding looks like.
- **This is a snapshot, not a safeguard.** It is worth doing precisely because it is the cheap half of the durable version; the automated per-role check in `safe-release.md` v2 is what stops the decay.

## 11. Approval

> Not approved. Not started.