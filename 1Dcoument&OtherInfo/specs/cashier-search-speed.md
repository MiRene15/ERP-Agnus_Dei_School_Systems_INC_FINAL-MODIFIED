# Spec: Cashier Search Speed

- **Status**: Approved
- **Created**: 2026-10-04
- **Approved by**: user on 2026-10-05
- **Revised**: 2026-10-05 — scope changed from *measure only* to **fix the repeated lookup first, then measure what remains**. Originally written as a pure diagnosis; that was unnecessary, because the main cause is visible in the code rather than something that has to be measured to discover. Renamed from `search-speed-diagnosis.md` on the same date, since a document carrying a code change should not be called a diagnosis.
- **Parent**: follow-up to `search-resilience.md` (Implemented — the plumbing works, the speed does not)

## 1. Why We Need This

Student search works, but it is slow. Our own records say so: searches routinely take **2 to 3 seconds**, sometimes nearly **5**, against the school's own threshold of 2 seconds.

The main cause is not a mystery and does not need to be measured to find. **The search repeats the same small lookup once for every student it returns.** A search that shows ten students asks the database for fee details up to ten separate times, on top of the search itself and its related data. So one search costs roughly **a dozen separate requests** instead of three.

That matches the recorded timings almost exactly: if each request to our database costs about a fifth of a second, ten of them is about two seconds — which is the flat minimum we keep seeing, search after search. The pattern is repeated because it is always ten students, whether or not the search itself is slow.

**Why this comes first:** there is a second possible cause that genuinely does need measuring — the unindexed way names are searched. Fixing the repeated lookup first means whatever time is left afterwards points clearly at that second cause, instead of two problems being confused for one.

## 2. Who Is Affected

- **Cashier** — the worst affected. Student search is how a cashier finds the family at the counter, and every payment depends on it.
- **Everyone** — even once fixed, if a flat cost remains on every request, it is paid on every page of every role.

## 3. Business Flow: Today vs After

- **As-is**: staff wait two to five seconds for a list, and the cashier's payments page is worse still because it shows more students.
- **To-be**:
  1. Search asks the database for fee details **once for all the students shown**, not once per student.
  2. Results and amounts are **exactly as before** — nothing about what a cashier sees changes.
  3. Search is faster, and how much faster gets measured.
  4. If it is still slow, what remains gets measured properly — with the repeated lookup already out of the way — and a separate specification decides the second fix.
- **Preserved**: every figure, every filter, and the search behaviour that already shipped — debouncing, rate limiting and usage counting all keep working.
- **Exceptions**:
  - **A student whose class has no grade level shows an assessed total of zero**, exactly as today. No new error, no different display.
  - **The school year in force is still the only year used.** Nothing spans years differently after the change.
  - **Two places do this, and both are fixed together** — the search results and the payments list. Fixing only one would leave the slower of the two untouched.

## 4. How It Should Work

### Part A — Remove the repeated lookup

1. The search asks for its students and their related data, unchanged.
2. Instead of asking for fee details student by student, collect the **grade levels** of all the students shown.
3. Ask for **all** fee schedules for those grade levels and the school year **in one request**.
4. Work out each student's assessed total from that single result.

The visible outcome must be identical: same students, same order, same amounts.

### Part B — Measure what remains

5. Run the same searches again and record how long they now take, several times each.
6. Ask the database to explain how it runs the search, and how many records it reads.
7. Compare a trivial request against the real search.
8. Apply the decision rule and write the second decision down.

### The decision rule

| What the numbers show | What it means | What the second fix will be |
|---|---|---|
| Search is now comfortably under two seconds | **Nothing further is needed.** This specification is finished. | None — stop here |
| Search is faster but still over two seconds | The repeated lookup was part of it, and something else remains | Whatever the measurements point at |
| A trivial request is also slow | Every request pays a flat cost; the search is the smaller half | Something shared — connection handling, database size, or location |

**"Nothing further is needed" is a legitimate and welcome outcome.** If the change is enough, the honest thing is to stop, not to go looking for more work.

## 5. Look & Feel (UX)

- **Where it lives:** nothing new. The cashier sees the same screen, the same list, the same figures.
- **The one primary action:** type a name, get the list faster.
- **How results read:** a short written summary of before and after timings. Plain language a cashier could understand.
- **What must not change:** the columns, the wording, the order results appear in, and the amounts beside each name.

## 6. Business Rules

### Must always be true
- **What each student shows is identical before and after.** Amounts, order and inclusion are unchanged.
- **Fee details for every student shown are retrieved in a single request.**
- Only the **school year in force** is used.
- A student with no grade level still shows an assessed total of **zero**, not an error.
- The search still returns at most the same number of students as before.

### Must never happen
- **A student's displayed figure changes as a result of this work.** If any amount differs, this is broken.
- A request is made per student again. The repeated lookup is the entire point of this work.
- Fee schedules are fetched for grade levels that no student shown actually has.
- **No second problem is "fixed" speculatively.** If the measurement says something remains, that gets its own specification.
- Nothing is measured on a quiet copy instead of the live database.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Two or more students share a grade level | Fee details for that level are fetched once and used for both |
| No student shown has a grade level | One request still happens, or none; the result is the same zeroes as today |
| A grade level has no fee schedule for the year | Assessed total is zero, exactly as today |
| The search returns fewer than ten students | Fewer grade levels, still one request |
| The search returns nothing | No fee request is made at all |
| Time is still slow after the change | That is a finding for Part B, not a reason to widen this work |

## 7. Out of Scope

- **Any fix for whatever remains after Part B** — the unindexed name search, the connection cost, or the database's location. Each is a separate specification written from measurements, not guesses.
- **The other slow pages** — audit logs and the librarian's searches. The same measurement will likely explain them; how wide to go is decided in that later specification.
- **The debouncing, rate limiting and usage counting already shipped.** They work and are not being changed.
- **Rewriting how search works or what it returns.** This is about speed only.

## 8. Success Checks

**The change is correct**

- [ ] Searching for a real student name returns the same students, in the same order, with the same amounts as before the change.
- [ ] The payments page shows the same figures as before the change.
- [ ] A student with no grade level still shows an assessed total of zero.
- [ ] The two places that repeated the lookup are both changed.

**The change is faster**

- [ ] Search time recorded before the change, from at least three runs.
- [ ] Search time recorded after the change, from at least three runs.
- [ ] The improvement is stated plainly, including if it disappoints.

**What remains**

- [ ] A trivial request timed, at least three times.
- [ ] The database's explanation of how it runs the search recorded.
- [ ] The number of student records searched recorded.
- [ ] A decision written down from the decision rule — including "nothing further is needed" if that is the truth.

## 9. Open Questions

None.

## 10. Technical Notes

*Plain-language pointer only — the source of truth is the code and this appendix.*

**The repeated lookup — two places.**

`CashierController::searchStudents()` — `CashierController.php:174-189`. After fetching up to 10 students, the `->map()` callback resolves each student's active enrolment and grade level, then issues `FeeSchedule::where('grade_level', ...)->where('school_year', ...)->get()` **inside the loop**. Ten students means up to ten requests. `->with(['enrollments.section', 'ledger'])` adds two more, plus the search itself — roughly thirteen in total.

`CashierController::payments()` — `CashierController.php:132-139`. The same shape, with `limit(20)` — so up to **twenty** fee requests. This is why the payments page should feel worse than search.

**The shape of the fix.** Three phases instead of one loop: fetch the students with their eager loads; collect the distinct grade levels from the active enrolments; fetch every fee schedule for those levels and the school year in a single query keyed by grade level; then compute each total from that map. The per-row logic that is *not* about fees — picking the active enrolment, reading the ledger — stays exactly where it is, in the callback.

**A structural decision worth naming.** `AGENTS.md` §2.1 asks for domain logic to live in a service rather than a controller. The batched fee lookup qualifies. Extracting it is cleaner and matches the standard; doing it in place is a smaller change and touches nothing else. **Recommendation: in place.** The subject of this work is how many requests are made, not how the code is arranged, and mixing a restructure into a performance fix makes a correctness problem harder to spot. Worth doing separately, and separately means reviewable.

**Why the remaining slowness still needs measuring.** The name search uses `ILIKE '%term%'` (`CashierController.php:165-169`), a leading wildcard that no ordinary index can serve, so the database reads every student row. If the search is still slow after the repeated lookup is gone, that is the likely remainder, and its fix is a trigram index — which Supabase supports in the `extensions` schema. **Do not create it here.** If the flat cost turns out to be the connection instead, an unused index is pure overhead.

**Part B measurements.** Supabase → SQL Editor, live database, read-only, during a quiet period:

```sql
-- The floor: the cost of asking at all. Three runs.
EXPLAIN (ANALYZE, BUFFERS) SELECT 1;
```

```sql
-- The search itself, with a real enrolled name. Three runs.
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM students
WHERE status = 'enrolled'
  AND ( first_name ILIKE '%<real first name>%'
     OR last_name  ILIKE '%<real last name>%'
     OR student_number ILIKE '%<real number>%'
     OR legacy_lrn ILIKE '%<real lrn>%' )
LIMIT 10;
```

```sql
-- How much there is to search, and what already exists.
SELECT count(*) AS enrolled_students FROM students WHERE status = 'enrolled';
SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'students';
```

Read `Execution Time` from each. Compare against the same search measured in the browser, which the application already records: `TrackSearchMetrics` writes a "Slow search list" warning above ~2 seconds (`app/Http/Middleware/TrackSearchMetrics.php:16-23`). **The gap between the two is the cost paid outside the database.**

**Cautions.** `EXPLAIN ANALYZE` actually runs the statement, so keep the `LIMIT` and pick a quiet period. Use a name that exists so the work is real. The database is in **Tokyo** (`aws-0-ap-northeast-1.pooler.supabase.com`) and the school is in Manila — if the trivial request is slow, that is the first thing to weigh. `phpunit.xml` declares MySQL while production is PostgreSQL, so nothing local would catch a dialect mistake; and the suite does not run in this project anyway.

**Not verified here:** the librarian's three searches did not match this pattern on inspection and were not traced. They are named in §7 as something Part B should explain, not as something this work fixes.

## 11. Approval

> Approved by user on 2026-10-05, with the revised scope: fix the repeated lookup first, then measure what remains.