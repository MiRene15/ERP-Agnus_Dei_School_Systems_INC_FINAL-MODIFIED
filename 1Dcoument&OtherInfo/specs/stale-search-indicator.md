# Spec: Stale Search Indicator

- **Status**: Approved
- **Created**: 2026-10-05
- **Approved by**: user on 2026-10-05
- **Origin**: found by the user while testing `cashier-search-speed.md`, 2026-10-05

## 1. Why We Need This

On the Process Payments page, the search results are deliberately kept on screen while a new search runs — so you don't lose your place while typing. That was a good decision.

But nothing tells you the list has been **superseded**. You type a new name, and the old list sits there looking like the answer to your new question. The search box says one family; the screen shows another.

**At a payment counter this is how the wrong family gets paid.** There is a "Process Payment" button beside every row, nothing on the page contradicts what you're looking at, and the person who notices is usually the family who was charged incorrectly.

The list isn't wrong and the search isn't broken. What's missing is the truth: *this list is from your earlier search.*

## 2. Who Is Affected

- **Cashier** — the primary and urgent case. The action beside each row moves money.
- **Librarian** — has the same defect on six screens; see §7. Less severe: the mistake is borrowing the wrong book rather than charging the wrong person.
- **Everyone else** — unaffected.

## 3. Business Flow: Today vs After

- **As-is**: search for a name → results appear → type another name → the old results stay, silent and authoritative, until the new ones land. There is no moment where the page admits the list is out of date.
- **To-be**:
  1. Search for a name → results appear, and the page records which name produced them.
  2. Type another name → the list stays (you keep your place), but the page now **says which search the list is from** and that a newer one is on its way.
  3. New results land → the message clears and the page records the new search.
- **Preserved**: the kept-list behaviour. You do not lose your results mid-typing, and the list does not blank.
- **Exceptions**:
  - **Clearing the search, or typing something too short to search, empties the list.** There is then nothing stale to describe, and no message appears.
  - **If a search fails**, the existing message already says so; that behaviour is unchanged.
  - **A rate-limit wait already has its own message.** While waiting, the list is also superseded, and must say so rather than appear current.

## 4. How It Should Work

1. When results arrive, the page remembers **the search text that produced them**.
2. When a newer search starts and a list is already showing, the page displays:
   - the search the visible list came from, and
   - the search now running.
3. While that message is showing, the list is visibly set back — dimmed — so the eye registers that it isn't current.
4. When the newer results land, the message disappears and the remembered search updates.
5. When the list is emptied, the remembered search is cleared too, and nothing is shown.

**The message must be plain and unambiguous.** Something like:

> Showing results for "santos" — searching for "maria"…

Not a spinner alone, not "Loading…", and never anything that leaves the reader guessing which of the two names the list belongs to.

## 5. Look & Feel (UX)

- **Where it lives:** on the Process Payments page, directly above the results table, in the same quiet style as the existing "Searching…" hint.
- **The one primary action:** search, then act on the list — but only once the page says the list is current.
- **Plain language:** both search terms named, in quotes. A cashier must be able to tell at a glance which family is on screen.
- **Dimming, not hiding:** the rows stay readable so nothing is lost, but they are clearly recessed.
- **Works on first search too:** when there is no earlier list, the existing skeleton behaviour is unchanged.

## 6. Business Rules

### Must always be true
- A list on screen is **always identifiable** as belonging to a specific search.
- While a newer search is running against a visible list, the page **states both** the old search and the new one.
- The message **names the search the list came from**, never a vague "loading".
- Emptying the list clears the message and the remembered search together.

### Must never happen
- **A list is ever presented as current when it is not.** This is the whole purpose of the work.
- The results blank while the cashier types. The kept-list behaviour stays.
- The message names a search other than the one that produced the visible list.
- A message appears when there is no list to describe.
- This change alters what the page returns or calculates. It only changes what it displays.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| First search, no earlier list | Existing skeleton. No stale message. |
| Second search while the first is showing | Both names shown; rows dimmed |
| Search text cleared or too short | List empties, message clears |
| New search fails | Existing failure message; the visible list keeps its own search named |
| Rate-limit wait with a list showing | The wait message appears **and** the list is described as superseded |
| Two searches in rapid succession | Only the newest search is described as running; the older list keeps its own name until replaced |
| Same search typed twice | Message appears and clears normally; nothing special |

## 7. Out of Scope

- **The librarian screens carrying the identical defect** — `books.blade.php:67`, `loans.blade.php:58`, `inactive-logs.blade.php:34`, `history.blade.php:18`, `visits.blade.php:99`, and the student picker in `borrow.blade.php:69` / `visits.blade.php:65`. Same defect, same fix, lower severity because nothing financial happens on a mistaken click. **To be fixed next, the same way.** They are listed here so the pattern is on record rather than rediscovered.
- **Disabling the action buttons while a list is stale.** Considered and rejected for v1: it stops a cashier who has already found the right row from acting, which trades one mistake for a different one. Naming the list is enough to prevent the confusion.
- **Any change to the search itself, the debounce, the rate limit or the results returned.**
- **The cashier dashboard or any other page.**

## 8. Success Checks

- [ ] Search a name → results appear, and no stale message is shown.
- [ ] Start a second search while the first list is visible → the page names **both** the search the list came from and the search running.
- [ ] The second search's results land → the message disappears and the rows return to normal.
- [ ] The dimmed list is visibly set back while superseded.
- [ ] Clearing the search empties the list and shows no message.
- [ ] Typing fewer than two characters empties the list and shows no message.
- [ ] A slow search still keeps the previous list visible throughout — it never blanks.
- [ ] **A cashier can tell, without scrolling, which search the visible list belongs to.**

## 9. Open Questions

None.

## 10. Technical Notes

*Plain-language pointer only — the source of truth is the code and this appendix.*

- **Where the missing signal is.** `resources/views/portal/cashier/payments.blade.php:35` — the "Searching…" hint — and `:38` — the skeleton — are both gated on `loading && students.length === 0`. Once a list exists, neither can appear, so a superseded list is indistinguishable from a current one.
- **The search component** is `searchPayments()` at `payments.blade.php:103`. It already holds `_seq` and an `AbortController`, and `performSearch()` (`:135-183`) correctly discards out-of-order responses — verified, so **this is not a race condition** and no concurrency change is needed.
- **State to add:** one value remembering the search text that produced the displayed list. Set it when results arrive (`:172`); clear it wherever the list is emptied (`:130`, `:140`).
- **Where to render:** beside the existing hint at `:35`, shown when `loading` is true **and** a list already exists. The rows need a dimming treatment when that is true.
- **The message must quote both terms.** The running search is already available as `searchQuery`; the remembered one needs no new source.
- **Deliberately unchanged:** `performSearch()`'s request logic, the 600ms debounce, the rate-limit handling, the sequence guard, and the controller. This is a display-only change.
- **Also unchanged:** `resources/js/app.js` `ajaxTable` is a separate component for list-filter pages and has its own correct handling. It is **not** affected and should not be touched for this.
- **The same condition to correct later**, recorded in §7: `loading && books.length === 0`, `loading && transactions.length === 0`, and the two `loading && !html` occurrences.
- **Not caused by `cashier-search-speed.md`.** Found while testing it, but a pre-existing defect in that page's display logic. The speed change only made it noticeable.

## 11. Approval

> Approved by user on 2026-10-05.