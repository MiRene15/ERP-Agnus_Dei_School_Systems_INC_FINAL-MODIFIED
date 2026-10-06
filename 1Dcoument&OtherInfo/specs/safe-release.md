# Spec: Safe Release

- **Status**: Approved
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04 (original); re-approved by user on 2026-10-05 for §4 ilike-check revision
- **Revised**: 2026-10-05 — a pre-release check for case-sensitive searches was added to §4, per `case-insensitive-search.md` slice 3. Status was reset to **Draft** for re-approval (`spec-rules.md` §6) then **re-approved by user on 2026-10-05** ("please do 1 and 2"). The three-release requirement in §8 is unchanged and still outstanding.

## 1. Why We Need This

When you push, the live school site rebuilds itself in about a minute and nobody looks at it. If the push broke something, the first person to find out is a member of staff at their desk — and on 4 October that was a cashier, met by a "Server Error" page instead of their dashboard.

Nothing warned us. **The live site could not report its own errors**: they were being written to a file inside the server where nobody can read them. So a broken release stayed invisible for hours, and we spent the evening guessing before finding the actual answer.

This spec fixes that with no new software: a short routine you run around every push, and one change that makes the live site speak up when it breaks.

## 2. Who Is Affected

- **IT admin (you)** — the owner. You run the routine. It costs a minute or two per release.
- **Every member of staff** — the people currently affected when a release goes wrong. They are the detection mechanism today, which is exactly what we're replacing.
- **Parents and students** — indirectly. A cashier locked out of the dashboard means nobody can take a payment at the counter.

## 3. Business Flow: Today vs After

- **As-is**: push → the site rebuilds → nobody checks → a member of staff finds the problem, hours later, usually by reporting it. The release is only "verified" if something breaks.
- **To-be**:
  1. Three quick checks before you push, so you never ship something half-finished.
  2. Push. The site rebuilds by itself.
  3. Sign in as each role you touched — and always the cashier. Confirm each landing page loads.
  4. Open the live site's log and confirm it reports cleanly.
  5. Write one line saying what you released and that you checked it.
- **Preserved**: your preference to **fix forward** rather than roll back. Fixing forward is right while somebody is watching, and most faults are obvious.
- **Exceptions**:
  - **Nobody available to finish within the hour** → revert. A broken site costs the whole day; a paused release costs an hour.
  - **A revert on the free plan takes a few minutes**, because reverting is itself another build. Expect that, and don't read the wait as failure.
  - **If you changed a file every role shares** — the layout, the base controller, the navigation — then treat it as *every* role, not just the ones you meant to touch.
  - **After a revert, verify again.** Do not assume the revert fixed it.

## 4. How It Should Work

### Once, before the first release you use this on

- [ ] **Make the live site able to report its own errors.** In Render: your service → Environment → change `LOG_CHANNEL` from `stack` to `stderr`. Save, and let it redeploy.
  - Without this, everything below still works, but the log check after the push cannot be trusted — an empty log looks identical to a clean one.

### Before you push

- [ ] You are on `main`, and your working tree is clean — nothing half-finished is riding along.
- [ ] You know **which roles this touches.** If you changed anything shared, the answer is *all of them*.
- [ ] **If you wrote or changed any search, it ignores capitalisation.** Run this and confirm every hit is deliberate:

```powershell
Get-ChildItem app -Recurse -Filter *.php | Select-String -Pattern "'like',\s*[`"']%"
```

  - Each hit must be a machine-written identifier matched by prefix, where exactness is intended. Anything else should be `'ilike'`. See `case-insensitive-search.md`.
- [ ] You have noted the commit you are about to push, so you can reverse it later:

```powershell
git rev-parse HEAD
```

### After you push

- [ ] Wait for the rebuild to finish on Render.
- [ ] Sign in as **each role you touched**, **and always the Cashier**. Each landing page loads with no error. See nothing but a blank or a spinner? Treat it as broken.
- [ ] Change a date or filter on one page you changed, to prove the page responds rather than merely rendering.
- [ ] **Open the live site's Logs on Render.** Confirm the recent entries look like normal page loads, with no errors after your deploy.
- [ ] Write one line in the record below: what you released, which roles you checked, and that it passed.

### If a check fails

1. Read the error from the Logs tab. It will now actually be there.
2. **Fix forward** if the cause is clear.
3. **If it is not fixed within the hour, revert:**

```powershell
git revert <the commit you noted above>
```

```powershell
git push
```

4. Verify the pages again after the revert. Then debug away from the live site.

## 5. Look & Feel (UX)

- **Where it lives:** one file at `1Dcoument&OtherInfo/specs/safe-release.md`. It is both the document and the checklist — no second copy to keep in sync.
- **The one primary action:** follow it top to bottom. No branching, no decisions to make under pressure.
- **How it reads:** tick boxes, copy-pasteable commands, ordinary words. No jargon, no theory, no explanation of why unless it changes what you'd do.
- **Printable:** if you'd rather have it on paper by the machine, print this file. One page.
- **Who can use it:** a colleague who has never seen the codebase should be able to run a release from this alone.

## 6. Business Rules

### Must always be true
- Every release ends with **someone having signed in as each affected role** and seen the page load.
- **The cashier is verified on every release**, whether or not the cashier was touched.
- Every release ends with **the live site's log opened and read**.
- Every release has **one recorded line** saying what shipped and that it was checked.
- A shared file — layout, navigation, base controller — means **every** role is verified.

### Must never happen
- A release is never described as "probably fine" or "should be OK". It was either verified or it wasn't.
- A person blocked from their work is never used as the way a problem gets found.
- Debugging is never done on the live site while staff are locked out.
- Nobody changes the live site's own settings to fix a page. Pages get fixed in code and released like anything else.
- The log check is never skipped once the log is working. A skipped check is worse than none, because it records a pass that never happened.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| A role you didn't touch is broken | Check the shared files you changed. If any were shared, verify every role instead. |
| Nothing was changed except text or wording | Verify the Cashier and read the log. Still a release. |
| The log shows an error you didn't cause | Fix it as part of the release, or record it as a known issue. Either way, record it. |
| The site is fine but the log is empty | The logging is not set up. Fix `LOG_CHANNEL=stderr` before trusting any check. |
| A release at the worst possible moment | Enrollment or a fee week counts as *more* reason to verify, not less. If you cannot verify, wait until you can. |

## 7. Out of Scope

Deliberately not in this version:

- **An automated per-role check** — a command that signs in as each role and reports pass/fail. The natural next version; see §10.
- **Changing the other eight dashboards** to match the cashier's new server-rendered shape. They work and are untouched.
- **An in-app release page** for the IT admin. It cannot help if the site is already down.
- **Any change to Render's build settings or the `Dockerfile`.** The deploy works.
- **Automatic pre-push checks and CI.** Same reason.
- **The three broken pages** — Subjects, Sections and Staff Accounts. Separate cause, separate spec.

## 8. Success Checks

This is a routine, so it is proven by use rather than by inspection. **It cannot be honestly closed until all of these are true:**

- [ ] `LOG_CHANNEL=stderr` is set on Render, and an error has been seen arriving in the Logs tab.
- [ ] Three consecutive releases have been run with this checklist.
- [ ] Each of those three recorded what shipped, which roles were checked, and that it passed.
- [ ] **No member of staff reported a broken page across those three releases.**
- [ ] A colleague who did not write this checklist can run a release from it without being told what to do.

## 9. Open Questions

None.

## 10. Technical Notes

*Plain-language pointer only — the source of truth is the code and this appendix.*

- **Deploy model — why there is almost nothing "before" the push.** Render builds a Docker image from `Actual_Website/ERP_Agnus_Dei_School_Systems_INC/Dockerfile` and redeploys automatically on every push to `main`. The image's start command is `php artisan migrate --force && php artisan serve` (`Dockerfile:46`). There is **no** `route:cache`, `config:cache`, `view:cache` or `optimize` anywhere in the build, and `.dockerignore` excludes `storage/framework/views/*` and `storage/logs/*.log`. Therefore **no cached routes, settings or compiled pages exist on the server** — a class of fault often blamed on stale caches cannot occur there. Worth knowing, because it cost real time on 4 October.
- **Why production errors were invisible.** `config/logging.php:21` reads `LOG_CHANNEL`, defaulting to `stack`; `config/logging.php:57` expands `LOG_STACK`, defaulting to `single`; `config/logging.php:61` writes that to `storage_path('logs/laravel.log')`. Inside a container that file is unreachable, and Render displays only stdout/stderr. The `stderr` channel (`config/logging.php:97`) writes to `php://stderr` and is visible in the free Logs tab. **Note the free tier restriction:** the *Logs* tab is free; the Request Log and Metrics are not. The Logs tab is what this spec depends on.
- **Reverting.** Render's one-click Rollback is a paid feature. On the free plan the only route is `git revert` + push, which triggers a fresh build. This is why the hour rule exists rather than an instant undo.
- **The automated check (v2).** The right shape is a console command or feature test that authenticates as each role and requests its landing page, asserting a 200 and no exception. It would replace the human loop entirely. It needs its own spec, and it needs a working test runner first — see below.
- **Test runner gap.** `phpunit.xml` declares MySQL while production is PostgreSQL (Supabase), and per `cashier-projection-chart.md` PHPUnit is not installed and `php artisan test` is undefined. So there is currently **no automated net at all**, which is why this document is the only gate.
- **The cashier dashboard is now the only one rendering server-side** — the other eight role dashboards still fetch their figures in the background. Accepted, recorded in `cashier-dashboard-simplification.md` §10. If the others ever show the same slowness, that spec is the precedent to follow rather than something to reverse.
- **Deploy source files for a maintainer:** `Dockerfile`, `.dockerignore`, `config/logging.php`, and this file. No application code changes in this spec.

## 11. Approval

> Approved by user on 2026-10-04 (original). Re-approved by user on 2026-10-05 for the §4 ilike-check revision ("please do 1 and 2").

## 12. Release log (operational records — §8 needs three of these before this spec can close)

| # | Date | Shipped | Roles checked | Result |
|---|------|---------|---------------|--------|
| 1 | 2026-10-06 | One-submission idempotency (child C); other uncommitted work may have ridden along (C confirmed, riders unverified — pre-push ship-list never returned) | All nine, per user report | Pass, per user report |