# Spec: Future-Year Enrolled Dashboard

- **Status**: Implemented
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09
- **Implemented**: 2026-10-09 — dashboard defaults to student's latest Active enrollment year, hides never-joined years, filters Enrolled to Active status, shows Pending for unapproved, and "No fees posted yet" when no FeeSchedule exists.

## 1. Why We Need This
Plain-language problem statement and expected benefit. No jargon.

A new student who enrolled and paid for 2027-2028 looks "not enrolled" on the dashboard, while 2026-2027 students look correctly enrolled. Families worry their payment didn't count, and staff get extra questions. Once fixed, any student with an approved enrollment — current or future year — clearly shows as enrolled.

## 2. Who Is Affected
Bulleted list of user roles and what each role gains.

- **Parents/students (primary):** see a correct "Enrolled for 2027-2028" status instead of a confusing "no balance" or "enroll now" message.
- **Registrar:** fewer "am I enrolled?" follow-ups; enrolled lists and dashboard agree.
- **Cashier:** fewer payment-confusion visits; balance wording stays honest when fees aren't posted yet.
- **Principal/IT admin:** enrolled counts stay trustworthy during early enrollment.

## 3. Business Flow: Today vs After
- **As-is**: family enrolls + pays for 2027-2028 → dashboard opens on 2026-2027 (current year) → finds no 2026-2027 enrollment → shows "Enroll Now" / empty balance instead of the real 2027-2028 enrollment.
- **To-be**:
  1. Student enrolls/pays for 2027-2028 → enrollment saved as approved for 2027-2028.
  2. Dashboard looks across active + upcoming years, finds the 2027-2028 enrollment → headline shows "Enrolled for 2027-2028."
  3. Second line shows the balance if fees exist, or "No fees posted yet" if they don't — still enrolled either way.
- **Preserved**: registrar approval stays required before anyone shows as Enrolled; cancellation history stays kept.
- **Exceptions**: paid-but-not-approved shows as Pending; cancelled/transferred/refunded drops the Enrolled status but keeps history; fees posted late appear on their own without changing status.

## 4. How It Should Work
Numbered happy-path steps as a user experiences them.

1. A new student completes enrollment + payment for 2027-2028 and gets registrar approval.
2. The student opens the dashboard.
3. The dashboard shows "You are enrolled in [Grade] – [Section] for 2027-2028" with the four cards (Enrollment, Balance, Subjects, Status) — same layout as 2026-2027 students.
4. The Balance card shows the amount owed, or "No fees posted yet" when 2027-2028 fees aren't set up — never a bare "no balance."
5. If this is the student's first-ever enrollment (only 2027-2028), the dashboard shows only 2027-2028 — 2026-2027 is hidden, not shown as an empty row.
6. A 2026-2027 student sees no change at all.

## 5. Look & Feel (UX)
- Where it lives (screen/menu) and the one primary action.
- Key states: default, empty (what to do next), error, success, permission-denied.
- Plain-language labels; fewer clicks; sensible defaults (e.g., current school year).
- What the user sees first, per role if roles see different things.

- **Where it lives:** the same dashboard status card 2026-2027 students see. One headline status, no extra screens.
- **Primary status:** headline "Enrolled for 2027-2028"; second line is balance or "No fees posted yet."
- **States:**
  - Default: four cards as today (Enrollment, Balance, Subjects, Status).
  - Empty (first-time 2027-2028 student): that same enrolled card — never a blank box or "Enroll Now" button for an already-enrolled student.
  - Pending approval: "Pending approval for 2027-2028" with a View Status link.
  - Error: "We couldn't load your enrollment — try again" with retry.
  - Permission-denied: unchanged from today.
- **Labels:** plain words ("Enrolled for…", "No fees posted yet", "Pending approval") — no "no balance" as a status.
- **Defaults:** opens on the student's own latest enrollment year, not always on 2026-2027.

## 6. Business Rules
### Must always be true
- An approved enrollment in any active or upcoming year counts as Enrolled, with the school year shown.
- If no fees are posted yet for that year, the dashboard says "No fees posted yet" and still shows Enrolled.
- A first-time 2027-2028 student sees only 2027-2028; years they were never part of stay hidden.
- 2026-2027 students see exactly what they see today.

### Must never happen
- Never label an approved future-year enrollment as "not enrolled" or bare "no balance."
- Never invent or copy a balance from another school year.
- Never show Enrolled before registrar approval.

### Edge cases and what happens then
- **Enrolled in both 2026-2027 and 2027-2028:** headline is "Enrolled for 2027-2028," with 2026-2027 listed underneath.
- **Paid but not yet approved:** "Pending approval for 2027-2028" — not Enrolled.
- **Cancelled / transferred / refunded future enrollment:** Enrolled status drops; the cancellation stays in history.
- **Fees posted late:** balance appears on its own once fees exist; status stays Enrolled throughout.
- **First enrollment is 2027-2028:** 2026-2027 is hidden entirely.

## 7. Out of Scope
What is deliberately not included in this version.

- Setting up the actual 2027-2028 fee amounts/schedules (separate cashier task).
- Changing historical records or re-labeling past years.
- New reports or counts beyond making the dashboard consistent.
- Auto-promotion / rollover logic between school years.

## 8. Success Checks
- [ ] A new student enrolled + paid for 2027-2028 shows "Enrolled for 2027-2028" (not "no balance" / not enrolled).
- [ ] That same student's dashboard hides 2026-2027 entirely.
- [ ] A 2026-2027 student looks exactly as before — nothing changed for them.
- [ ] A 2027-2028 student with no fees posted yet still shows Enrolled + "No fees posted yet."
- [ ] A paid-but-not-yet-approved 2027-2028 student shows Pending, not Enrolled.

## 9. Open Questions (if any)
Unresolved items awaiting a decision. Nothing here may be guessed during implementation.

None.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages: student portal dashboard (`portal/student/dashboard.blade.php` + `partials/dashboard-results.blade.php`), including the greeting line, year picker, and the four cards.
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
  - `app/Http/Controllers/Portal/StudentController@index` — currently defaults the viewed year to `active_school_year()` (2026-2027) and queries enrollments where school_year = viewed year; this is why a 2027-2028-only student gets null and falls into the "Enroll for …" branch.
  - `app/helpers.php` (`active_school_year()`, `all_school_years()`) — year list currently merges the student's years with every year in the system, so 2026-2027 appears even for first-time 2027-2028 students.
  - `resources/views/portal/student/dashboard.blade.php` greeting branch and year-picker block; `partials/dashboard-results.blade.php` "Enroll Now" empty branch and enrolled cards, Balance card second line.
  - Read-only reads of enrollment records (school year + approval status) and the student money record for the balance line; no fee-amount changes in this spec.
- Data/records touched: enrollment records (read to decide status + year shown); student money/balance record (read to decide balance line vs "No fees posted yet"). No writes to fees or enrollments in v1.
- Roles/permissions involved: student (sees own dashboard); registrar + cashier (need matching lists, no new permissions); principal/IT admin (counts stay consistent).
- Nice-to-haves noted for later (not in v1): year-picker defaulting to student's latest enrollment year; small "upcoming enrollment" badge in registrar lists.

## 11. Approval
> Approved by user on 2026-10-09.
