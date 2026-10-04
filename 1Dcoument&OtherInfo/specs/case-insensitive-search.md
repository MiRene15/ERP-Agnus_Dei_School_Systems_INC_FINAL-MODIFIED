# Spec: Case-Insensitive Search

- **Status**: Approved — **not fully delivered.** Slice 1 of 3 done; slices 2 and 3 outstanding (see §12)
- **Created**: 2026-10-05
- **Approved by**: user on 2026-10-05
- **Implemented**: 2026-10-05, slice 1 of 3 — see §12
- **Origin**: found by the user while testing `safe-actions-everywhere.md`, 2026-10-05

## 1. Why We Need This

**Search only finds something if you type it with exactly the right capitalisation.**

Type `santos` and a record reading `Santos` is not found. Type `Headache` and a note reading `headache` is not found. Type a subject code or an application number in lower case and nothing comes back.

This is true of **70 searches across 13 screens, affecting every role in the school** — the only search that ignores capitalisation is the cashier's student lookup. It affects names, book titles and authors, serial numbers, subject names, subject codes, application numbers, complaint and symptom text, diagnoses, and audit-log descriptions.

Nobody wrote this on purpose. In most database systems this search is case-insensitive by default, so code written against one of them behaved differently here — and because a search that returns nothing looks exactly like a search with no matches, nobody could tell the difference. Staff learn to guess capitalisation, or conclude search is broken and stop using it.

You hit it in about a minute: typed a name, got nothing, assumed your typing was wrong, tried another capitalisation, and it worked.

## 2. Who Is Affected

**All nine roles.** This is the widest-reaching defect found so far.

- **Librarian** — 31 searches: books, authors, ISBNs, serial numbers, students, loans.
- **Principal** — 7: subjects, subject codes, teachers, students, sections.
- **Nurse** — 6: complaints, symptoms, diagnoses.
- **Cashier** — 3 filters; the student name search already behaves correctly.
- **Teacher** — 4: class and subject searches.
- **Admin** — 4: audit-log description, event, subject type, category name.
- **Registrar** — 3: admission applicants, emails, application numbers.
- **IT admin** — 2: staff name and email.
- **Directress** — 3: report-card student and section searches.

## 3. Business Flow: Today vs After

- **As-is**: staff type a name, get nothing back, and cannot tell whether the person is missing or their typing was. They try another capitalisation, or give up on the search box and scroll.
- **To-be**: staff type a name however they normally type it, and the record is found.
- **Preserved**: the cashier's student search, which already behaves this way. The internal marker used to find a voided receipt, which must stay exact — see §6.
- **Exceptions**:
  - **Searches will start returning records they previously missed** — including a student or staff member someone had stopped seeing because of a capitalisation slip. That is the fix working, and it will look surprising the first time it happens.
  - **Nothing on any screen changes.** Same fields, same wording, same result order. Only the set of records a search can match grows.

## 4. How It Should Work

1. **Every search a person types ignores capitalisation** — names, titles, codes, numbers, and free text.
2. **One internal marker stays exact**, described in §6. It is written by the system and never typed by a person.
3. **The cashier's student search is left alone.** It already behaves correctly.
4. **The rule is written down where searches get written**, so the next search written here gets it right without anyone remembering.
5. **A check runs before each release**, so a new case-sensitive search is caught before staff find it.

## 5. Look & Feel (UX)

- **Nothing changes on screen.** No new labels, no new settings, no changed wording.
- **The only difference:** searches find records they previously missed.
- **What a staff member sees first** is identical. There is nothing new to learn.
- **No waiting changes.** This is a correctness fix, not a speed one.

## 6. Business Rules

### Must always be true
- **Every search a person types ignores capitalisation.**
- **The void-receipt marker is matched exactly**, and stays case-sensitive.
- No screen's appearance, wording, field order, or result ordering changes.
- Results are otherwise identical to today — only capitalisation-insensitive matches are added.
- The rule is documented where searches are written, and checked before a release.

### Must never happen
- **The void-receipt lookup becomes case-insensitive.** It is the one place a database index could be in use, and loosening it would slow that lookup down for no benefit.
- A search's matching changes in any way beyond ignoring capitalisation.
- The cashier's already-correct student search is altered.
- Correctness depends on a developer remembering the rule. It must be written down and checked.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Two records differ only in capitalisation | Both are returned. They already were whenever the typed capitalisation matched one of them |
| A search that returned nothing now returns results | Expected — this is the fix. Records previously invisible become visible |
| A nurse searches `headache` | Now finds `Headache` and `headache` |
| A subject or application code typed in lower case | Now matches |
| A voided receipt is looked up | Unchanged, still exact, still uses its index |
| A staff member searches for someone they'd stopped seeing | They now find them. Worth telling staff this is expected, so it reads as a fix rather than a fault |

## 7. Out of Scope

- **A full sweep of other database-dialect assumptions.** Checked on 2026-10-05 by inspection: no other live fault was found — the earlier date-format fault is properly fixed, and row locking is already correctly guarded against the production database. That is reassuring but **not proof**, because inspection cannot find what it does not know to look for. A systematic sweep deserves its own specification, and honestly needs a real PostgreSQL test environment to verify against.
- **Making searches faster.** See `cashier-search-speed.md`.
- **Adding indexes or trigram indexes.** Note this fix has no speed cost either way — see §10.
- **Forgiving other typing mistakes** — matching `sntos` to `Santos`, or ignoring stray spaces. Genuinely useful, and a **separate feature**. It is named here so it isn't mistaken for something this does.
- **Changing the void-receipt convention.**

## 8. Success Checks

**Per role — type a name in the wrong capitalisation and confirm it is found**

- [ ] Librarian finds a student by lower-case name; finds a book by lower-case title and author.
- [ ] Principal finds a subject, and a student, in lower case.
- [ ] Nurse finds a clinic record by lower-case symptom or diagnosis.
- [ ] Registrar finds an applicant by lower-case name, and by lower-case application number.
- [ ] Teacher finds a class or subject in lower case.
- [ ] IT admin finds a staff member by lower-case name and email.
- [ ] Cashier filters still work, and the student name search still works as it did before.

**Nothing else moved**

- [ ] A voided receipt is still found and reversed exactly as before.
- [ ] No screen looks or reads differently.
- [ ] Result order within a list is unchanged.

**The rule is recorded**

- [ ] The rule appears where searches are written.
- [ ] A check exists in the release routine that flags a new case-sensitive search.

## 9. Open Questions

None.

## 10. Technical Notes

*Plain-language pointer only — the source of truth is the code and this appendix.*

**The change:** `like` → `ilike` at **70 sites across 13 controllers.**

| Controller | Sites | Controller | Sites |
|---|---|---|---|
| Librarian | 31 | Registrar | 3 |
| Principal | 7 | IT admin (User) | 2 |
| Nurse | 6 | IT admin (Subject) | 2 |
| Teacher | 4 | IT admin (StudentAccount) | 2 |
| Cashier | 4 | Directress (ReportCard) | 3 |
| Admin | 4 | Directress (Withdrawal) | 2 |
| Section | 1 | | |

**The single exclusion — `CashierController.php:810`:**

```php
->where('receipt_number', 'like', 'VOID-' . $payment->receipt_number . '%')
```

Verified safe to leave alone. The void path **writes** the marker at `CashierController.php:822` as the literal `'VOID-'` in uppercase, and this line **reads** it with the same uppercase literal — writer and reader match exactly. It is also the only one of the 71 sites that is not `%term%`: it is a **prefix** match, so a database index can serve it. Making it case-insensitive would stop the index being used, slowing the void path for no gain. It is a machine-written marker, never typed by a person, so exactness is correct rather than merely convenient.

**Why this costs nothing in speed.** All 70 remaining sites use `%term%` — a **leading wildcard**, which no ordinary index can serve in any case. Every one of them is already a full table scan today. Case-insensitive matching cannot use a plain index either, so **no index usage is lost anywhere.** This is not a trade-off between correctness and speed; there is no speed consequence either way. (This also means these searches stay slow for the reasons in `cashier-search-speed.md` — a separate matter.)

**Distribution of what is being changed:** names, book titles and authors, ISBN and serial numbers, subject names and codes, section names, student numbers and legacy numbers, emails, application numbers, clinic complaint/symptom/diagnosis text, audit-log description/event/subject type. All are person-typed free text or codes, and all benefit.

**Where the rule gets written:** a single line in `AGENTS.md` recording that text searches in this project are case-insensitive, because that file is read by both developers and agents before any code is written. The full reasoning stays in this spec.

**Where the check gets added:** `safe-release.md` §4, before the release section. A single command the developer runs, flagging any case-sensitive leading-wildcard search for a deliberate decision:

```powershell
Get-ChildItem app -Recurse -Filter *.php | Select-String -Pattern "'like',\s*[`"']%"
```

Every hit is reviewed and confirmed intentional; the void-receipt line does not match, since it begins `VOID-` rather than `%`.

**Consequence to note:** adding this check amends `safe-release.md`, which resets that spec to `Draft` for re-approval (`spec-rules.md` §6). That is expected and correct.

**Not caught here:** searches implemented outside `app/Http/Controllers` — raw SQL, query builder calls elsewhere, or the 26 API routes. The rule above applies to those too; they were not enumerated for this change.

## 11. Approval

> Approved by user on 2026-10-05. Scope: all 70 case-sensitive searches, excluding the void-receipt marker, plus the documented rule and the pre-release check.

## 12. Implementation Note (2026-10-05)

**Read-only throughout: no migrations, no schema change, no writes. One-word change at each site.**

**Delivered:** `like` → `ilike` at **70 sites across 13 controllers**, all person-typed free-text searches — names, book titles and authors, ISBN and serial numbers, subject names and codes, section names, student numbers and legacy numbers, emails, application numbers, clinic complaint/symptom/diagnosis text, audit-log description/event/subject type. Total `ilike` across `app/` is now 78, the 70 changed plus the 8 that already worked.

**Correction to §10 — there are TWO exclusions, not one.** §10 named only the void-receipt marker and recorded searches outside `app/Http/Controllers` as "not caught here". The verification step then found a second:

- `app/Models/Payment.php:39` — `self::where('ar_number', 'like', "AR-{$year}-%")` in `generateArNumber()`. This is in a **model**, not a controller, so the original inventory missed it.

Both exclusions are the same shape and were verified the same way: a **machine-written identifier, matched by prefix, therefore index-capable, never typed by a person.** `generateArNumber()` writes the uppercase literal `AR-` at `Payment.php:50` and reads it with the same literal at `:39`; `voidPayment()` writes `VOID-` at `CashierController.php:822` and reads it at `:810`. Writer and reader match exactly in both cases, so case-sensitivity is correct rather than merely tolerated, and loosening either would stop a usable index being applied.

The scope decision in §4 was correct and was applied correctly; only the inventory count in §10 was short. Recorded here rather than edited in place, since the approval covered the scope rather than a site count.

**Worth recording as a lesson.** The verification step was written to confirm an expected count, and the expectation was wrong — 2 exclusions, not 1. Had it merely trusted the arithmetic it would have "passed" while a genuine second index-backed lookup sat unexamined. A check that can fail is worth more than a check that agrees.

**Verification.** `ilike` totals 78, `like` totals 2 — both accounted for above. The user confirmed in the browser that searches now find records typed in any capitalisation across the roles checked, and that the void path is unchanged.

**Slices 2 and 3 of the plan were not implemented.** The rule has **not** been written into `AGENTS.md`, and the pre-release check has **not** been added to `safe-release.md`. Both remain outstanding — see §8's last two checks, which are unticked. **This spec is not fully delivered.**