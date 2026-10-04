# Spec: Restore Blocked Admin Pages

- **Status**: Implemented
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-04
- **Implemented**: 2026-10-04 — see §12

## 1. Why We Need This

Three of your nine staff roles cannot open pages they are responsible for. The pages do not load with a helpful message or an empty list — **they fail outright**, and no action on them is possible.

For the IT admin this includes **creating a staff account**. If a teacher or cashier is hired this week, there is no way to give them access to the system.

The cause is a single missing capability in the file that every page in the system sits on. Three pages ask for a tool that file doesn't provide, so they stop before doing anything.

## 2. Who Is Affected

- **Registrar** — **Subjects** and **Sections**. Not just viewing: create, edit, save, delete, CSV template download and CSV import. This is curriculum and section planning — the core of the registrar's work.
- **Principal** — the read-only Subjects list. No visibility of the curriculum.
- **IT admin** — **Staff Accounts**: create, edit, reset password, toggle status. **No new staff account can be created at all.**

Three roles are unaffected: Cashier, Teacher, and the rest. Nothing they use is touched.

## 3. Business Flow: Today vs After

- **As-is**: open an affected page → it fails. Nothing can be done from it.
- **To-be**: open the same page → it works, exactly as it always did. Nothing about the screens, wording or fields changes.
- **Preserved**: the gentle limit on list and search pages. It exists to stop a stuck key or an over-eager refresh from hammering the server. It must **never** apply to saving, importing or downloading.
- **Exceptions**:
  - **No half-finished records need cleaning up.** The failure happened before any work started, so nothing was ever partially written.
  - **No backfill or repair is needed.** These pages only read and write through normal actions.
  - **Screens that already work are unaffected.** The change is to the shared foundation, so it must not alter any page's behaviour.

## 4. How It Should Work

1. Subjects, Sections and Staff Accounts all open normally.
2. Every action on them works as it always did: list, create, edit, save, delete where allowed, CSV template download, CSV import.
3. Principal sees the read-only Subjects list.
4. The list pages keep their gentle search limit.
5. **Saving, importing and downloading are never limited** — a limit meant for typing must never punish a save.
6. Nothing on screen looks or reads differently.

## 5. Look & Feel (UX)

- **Nothing changes visually.** These screens are meant to look exactly as they did before they broke.
- The only user-visible change is that pages stop failing.
- Same forms, same fields in the same order, same confirmations, same plain-language messages for genuine mistakes.

## 6. Business Rules

### Must always be true
- Every role named in §2 can reach every page it owns.
- The gentle limit applies to **list and search traffic only**.
- The Principal's view of Subjects stays **read-only**.
- CSV template download and CSV import both work.

### Must never happen
- A save, an import or a download is ever blocked by the search limit. A limit meant for someone typing fast must never punish someone finishing their work.
- Any screen's appearance, wording or field order changes because of this fix.
- A page that already worked becomes slower, emptier, or behaves differently.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| The registrar saves an edit repeatedly in a hurry | Never limited. That is exactly what "list only" means. |
| Someone searches a list very fast | They wait briefly, then continue. Existing behaviour, unchanged by this fix. |
| The Principal opens Subjects | Read-only, exactly as before. |
| A subject CSV is imported | Works, and is never limited. |
| A page nobody has opened since it broke | This work cannot prove it works. Only the pages in §2 are proved here — see §7. |

## 7. Out of Scope

- **A full sweep of every page for every role.** These three pages were found by reading the server log, not by inspecting every page — a page nobody has visited since it broke leaves no trace. Specified separately as **`portal-page-sweep.md`** (~224 pages across 9 roles, roughly a day). Not started.
- **A static check for controller method calls.** It would not have caught the 4 October cashier fault either, because no check over call sites can see a missing argument. It would guard this pattern, and only this pattern.
- **The cashier dashboard work** and the other eight role dashboards. Untouched.
- **Making the search limit configurable per screen.** Nobody asked for it, and a setting nobody changes is a setting that drifts.
- **The automated per-role check**, already noted as the next version of **Safe Release**. That is what would actually find unknown broken pages.

## 8. Success Checks

**Every role can reach its own landing page**

- [ ] Admin, Registrar, Teacher, Student, Cashier, Librarian, Nurse, Directress and Principal each sign in and see their dashboard.

**The three blocked areas work**

- [ ] Registrar opens **Subjects** — the list loads.
- [ ] Registrar creates and saves a Subject.
- [ ] Registrar opens **Sections** — the list loads.
- [ ] Registrar edits and saves a Section.
- [ ] IT admin opens **Staff Accounts** — the list loads.
- [ ] IT admin creates and saves a staff account.
- [ ] Principal opens the Subjects list, read-only.
- [ ] Subjects CSV template downloads, and a CSV import succeeds.

**The limit behaves**

- [ ] No save, import or download shows a "too many attempts" message.
- [ ] Searching quickly on those three list pages still gets the gentle limit.

## 9. Open Questions

None.

## 10. Technical Notes

*Plain-language pointer only — the source of truth is the code and this appendix.*

- **Fault, and it has two parts.** Three controllers call `$this->middleware('throttle:search')->only('index')` in their constructors. `app/Http/Controllers/Controller.php` is a standalone `abstract class Controller {}` extending nothing, so it has no `middleware()`. The constructor therefore throws before any action runs — which is why *every* action fails, not just the list. And even with that call removed, `getMiddleware()` is absent, so the limit would silently never apply.
- **The three call sites, which need no edit:** `Admin/SubjectController.php:13`, `Admin/SectionController.php:14`, `Admin/UserController.php:18`.
- **Fix:** have `App\Http\Controllers\Controller` extend `Illuminate\Routing\Controller`.
- **Why that works in this Laravel version.** `vendor/laravel/framework/src/Illuminate/Routing/Controller.php:23` provides `middleware()` and `:40` provides `getMiddleware()`. Both are still present. `vendor/.../Routing/ControllerDispatcher.php:71-78` calls `getMiddleware()` when the method exists and then applies the `only`/`except` scoping through `methodExcludedByOptions()`, so `->only('index')` keeps working exactly as intended.
- **App-wide side effect to be aware of.** `ControllerDispatcher::dispatch()` (`ControllerDispatcher.php:42`) checks `method_exists($controller, 'callAction')`. Once inherited, `callAction()` exists, so **every** controller now dispatches through it. It performs `$this->{$method}(...array_values($parameters))` — identical to the previous path for a public method with route parameters — but it is an inherited change across the whole application. This is why §8 checks all nine landing pages.
- `__call()` also becomes available and raises `BadMethodCallException` for an undefined method, where previously PHP raised a plain `Error`. Both are server errors; only the message differs.
- **Collision check performed 2026-10-04:** no controller under `app/` declares `callAction`, `getMiddleware`, `$middleware` or `__call`, so inheriting those four members conflicts with nothing.
- The `throttle:search` limiter is already registered at `AppServiceProvider.php:38` and is used successfully by many routes. Only these three controllers were broken; the intent was sound and only the wiring was missing.
- **Detection history.** Found by reading `storage/logs/laravel.log` on 2026-10-04 — entries at 20:59:28, 20:59:56 (`SectionController`) and 21:02:52 (`SubjectController`). Confirmed fatal for all actions, not only lists.
- **Data:** no migrations, no schema change, no writes. One file, one line of change. Nothing to roll back.
- **Release bookkeeping:** this counts as one of the three releases required by `safe-release.md` §8, and verifies every role — which is what a change to the shared foundation warrants.

## 11. Approval

> Approved by user on 2026-10-04.

## 12. Implementation Note (2026-10-04)

**Read-only throughout: no migrations, no schema change, no data written. One file, one import, one word.** Nothing to roll back.

Delivered — `app/Http/Controllers/Controller.php`:

```
+use Illuminate\Routing\Controller as FrameworkController;
-abstract class Controller
+abstract class Controller extends FrameworkController
```

**Why the alias rather than an inline fully-qualified name.** This file's own class is named `Controller`, so a plain `use` statement would collide with it. The alias makes the origin explicit at a glance — which matters here, because the entire fault was that nobody reading this file could tell where `Controller` was supposed to come from. It also follows existing precedent: `app/Models/User.php:8` resolves an identical collision with `use Illuminate\Foundation\Auth\User as Authenticatable;`.

**The three call sites were deliberately not edited.** `Admin/SubjectController.php:13`, `Admin/SectionController.php:14` and `Admin/UserController.php:18` were already correct; only the missing capability was at fault.

**Deliberately not changed:** `declare(strict_types=1)` was **not** added to this file. `AGENTS.md` §2.2 requires it in *new* PHP files, this file is not new, and adding a strict-types declaration to a base controller that every controller inherits could alter subclass behaviour — a change well outside this spec's scope. Recorded so a later reader does not "tidy it in" without thinking.

**Verification.** `php -l` clean. The user confirmed in the browser that the three blocked areas load, that saves on all three are not rate-limited, and that the nine role landing pages are unaffected.

**Recorded honestly:** the §8 checks were confirmed by the user as a whole rather than ticked one by one. If any specific check is later found unsatisfied, treat it as an open item against this spec rather than as a passed check.

**The automated net remains absent.** `phpunit.xml` declares MySQL while production is PostgreSQL, and PHPUnit is not installed, so nothing here was machine-verified. This fix rests on a one-line human-verified change to the file every controller inherits — the highest-blast-radius edit in this project so far. `portal-page-sweep.md` exists to find what this spec could not see.

**Not committed by the agent** — the user runs git themselves (`AGENTS.md` §1.4).