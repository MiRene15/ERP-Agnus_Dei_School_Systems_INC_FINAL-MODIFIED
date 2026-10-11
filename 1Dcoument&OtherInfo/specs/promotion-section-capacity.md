# Spec: Promotion Section Capacity

- **Status**: Approved
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11

## 1. Why We Need This

End-of-year promotion is slow and scary today. Filtering by grade is unreliable, the select-all box can grab students from every grade at once, full sections silently overflow past 30, and duplicate names make staff afraid to press Confirm. This change makes promotion fast, countable, and safe: counts first, rows in chunks, one action per student with Principal approval, sections that auto-create at 30 and lock when full, and duplicate handling that never loses grades or payments.

## 2. Who Is Affected

- **Registrar (primary)** — runs promotion: filters, searches, selects, previews counts, proposes; picks surviving record for duplicates.
- **Principal** — approves or rejects proposals (single or bulk); nothing executes before approval.
- **Directress** — final sign-off where required; reads the year-over-year summary.
- **Teachers** — read-only; see resulting class lists.
- **Students and parents** — see only the resulting grade/section next year. No new work.
- **IT Admin** — no daily work; helps only when a duplicate pair needs history review.

## 3. Business Flow: Today vs After

- **As-is**: Registrar opens Promotion → per-grade tables load slowly all at once → header checkbox can select every student in the system → propose → Principal approves → Directress signs off → system executes. Capacity (30) exists on paper but full sections accept more; duplicates sit side by side; Transfer and Dropout collapse into one bucket in reports.
- **To-be**:
  1. Registrar picks school year (current pre-filled) → filters grade + searches name → counts appear first, rows stream in chunks.
  2. Registrar ticks many (per-grade Select All + one clearly-labeled "All grades" global) → picks one action (Promote / Retain / Graduate / Transfer / Dropout) → previews counts including new sections → Proposes.
  3. Principal approves/rejects (bulk allowed, partial allowed) → Directress signs off → system executes; students past 30 spill into auto-created sections; full sections lock.
  4. Directress/Principal read the year-over-year summary (counts by action per school year).
- **Preserved**: strictly approve-then-execute (Registrar can never self-execute), school-year locks, GWA + Qualified badges, Batch Qualified button, per-ticket history, press protection.
- **Exceptions**: a student who transfers mid-batch is excluded and flagged for re-run; late enrollments after execution are handled as single proposals, not re-runs.

## 4. How It Should Work

1. Registrar opens Promotion → school year pre-filled → grade filter + search row on top → counts show first ("Grade 6: 45 students"), rows load in chunks.
2. Registrar ticks students (per-grade Select All ticks only that grade; the global box is labeled "All grades — all students" and asks for confirmation) → picks Promote / Retain / Graduate / Transfer / Dropout → preview strip shows the outcome ("45 promote → Section A 30 Locked + Section B 15, 1 section auto-created").
3. Registrar taps Propose → proposal waits as Awaiting Principal Approval; execution is locked until approval.
4. Principal opens Promotion approvals → Approves (all or selected) or Rejects with reason → Directress signs off → system executes; each student gets exactly one outcome; full sections show Locked and refuse new picks with guidance.
5. Duplicate names appear with LRN, photo, and birthdate side by side → Registrar picks the survivor → grades, attendance, and payments history follow the survivor; the loser is archived, never deleted when it holds history.
6. Re-running a batch after interruption skips already-moved students — never double-promotes.
7. Saint names are verify-only: every section dropdown shows saints; any leftover virtue name is reported, not renamed here (renames shipped earlier).

## 5. Look & Feel (UX)

- Where it lives: the existing Promotion page — no new menu. One primary action per step: Registrar `Propose`, Principal `Approve / Reject`, Directress `Sign Off`.
- What the user sees first: school-year + grade filter + search, then counts, then tables. Counts-first loading with a "Still loading rows…" hint; rows never blank the counts.
- Key states: default (counts + tables with Qualified badges); loading (counts first, rows streaming); empty ("No students in this grade"); preview ("45 promote → 2 sections"); success ("312 promoted, 2 sections created, 4 locked"); error ("Section full — placed in Section B" / "Row 4: missing action"); denied ("Awaiting Principal approval — execution locked").
- Plain labels: Propose, Approve, Reject, Sign Off, Promote, Retain, Graduate, Transfer, Dropout, Locked, All grades — all students. Transfer and Dropout always appear as separate words, never merged.
- Year-over-year summary (v1, read-only): Directress/Principal see counts by action per school year (promoted, retained, graduated, transferred, dropped out) with the current year pre-selected.

## 6. Business Rules

### Must always be true

- One action per student per school year; re-runs skip already-moved students.
- Strictly approve-then-execute: nothing moves before Principal approval (and Directress sign-off where required).
- Graduate only from final levels (Kinder, Grade 6, Grade 10, Grade 12).
- Transfer and Dropout are recorded as distinct outcomes in lists, reports, and history.
- Sections cap at 30 for all levels; the 31st student spills to an auto-created section; full sections lock from new picks.
- Duplicate handling: staff picks the survivor using LRN/photo/birthdate; all grades, attendance, and payment history follow the survivor; the loser is archived, never hard-deleted when it holds history.
- GWA + Qualified badge (GWA at or above passing grade, default 75, no failing subject) shows on every promotion row.

### Must never happen

- Execution without Principal approval.
- A select-all in one grade selecting students in another grade.
- A section holding more than 30 active students after execution.
- A new enrollment or promotion pick landing in a Locked section.
- Two records for the same child both staying active after a duplicate decision.
- History (grades, payments) orphaned or wiped by a duplicate merge or a re-run.
- Transfer and Dropout merged into one bucket.

### Edge cases and what happens then

- Per-grade Select All vs global: per-grade ticks only that grade; global ticks all and shows a confirmation naming the total before proceeding.
- Section hits exactly 30 mid-batch → next student goes to the auto-created section; the full one shows Locked immediately.
- Principal approves part of a batch → approved part executes on sign-off; rejected part returns to Registrar with reasons.
- Student transfers or withdraws mid-batch → excluded from execution, flagged "moved — file a single proposal for this student."
- Duplicate loser has zero history → archived (kept for audit, hidden from lists); loser with history → archived with history linked to survivor; never hard-deleted in v1.
- Interruption mid-execution (connection lost) → re-run resumes, moved students skipped, counts reconcile before finishing.
- Leftover virtue section name spotted → shown as a data flag for IT; no rename happens in this spec.

## 7. Out of Scope

- Manual drag-and-drop movement between sections.
- Per-level cap tuning (uniform 30 in v1).
- Auto-merging duplicate names without staff pick.
- Section renames (shipped earlier — verify-only here).
- Per-level custom promotion chains.

## 8. Success Checks

- [ ] Filter Grade 6 + search a name → only that grade's matches show, counts match rows.
- [ ] Per-grade Select All ticks only that grade; global box names its total and asks to confirm.
- [ ] Promote 45 in a grade → sections of 30 Locked + 15 open; exceeding 30 auto-creates the next section.
- [ ] A Locked section refuses new picks with guidance naming the open section.
- [ ] Duplicate pair → survivor picked, grades + payments intact on survivor, loser archived and hidden.
- [ ] Execute before Principal approval → blocked with "Awaiting Principal approval."
- [ ] Re-run the same batch → moved students skipped, nothing double-promotes.
- [ ] Transfer and Dropout appear as separate outcomes in the list and summary.
- [ ] Year-over-year summary shows counts by action per school year with current year pre-selected.
- [ ] Promotion list still shows GWA + Qualified badges and the Batch Qualified button works as before.

## 9. Open Questions (if any)

None — uniform 30 cap confirmed; approve-then-execute confirmed; badges verify-only confirmed; year-over-year summary in v1 confirmed.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages:
  - Registrar: `portal/registrar/promotion/index.blade.php` (filter + search + counts-first + per-grade/global select + preview + propose), `portal/registrar/requests.blade.php` (promotion proposal list + Batch Qualified button + GWA/Qualified badge), proposal history.
  - Principal: `portal/principal/promotion/index.blade.php` + `portal/principal/approvals.blade.php` (approve/reject + bulk + GWA column), pending counts.
  - Directress: `portal/directress/promotion` sign-off view + year-over-year summary (new read-only counts view, current year default).
  - Shared: section dropdowns everywhere (enrollment, class lists, schedules) show new + Locked states identically.
- Likely areas of the codebase (from read-only inspection):
  - Proposal chain: `PromotionWorkflowController` (`registrarIndex`, `registrarPropose`, `registrarBatchQualified`, `principalApprove/Reject`, `directressSignoff`) + `promotion_proposals` store + `PromotionService` execution; keep strictly approve-then-execute; add partial-approve (approved subset executes, rejected subset returns with reason) and resume-safe execution (skip already-moved, reconcile counts).
  - Readiness badges (verify-only): existing GWA + `Qualified` badge + `registrar.promotion.batch-qualified` route + passing-grade setting (default 75, `admin/settings`); no logic change in this spec.
  - Capacity: `classes.capacity` (seed default 30) + enrollment guard in `RegistrarAdmissionController` (blocks over capacity with error); add uniform-30 enforcement at promotion execution (spill to auto-created section, mark full Locked, refuse picks into Locked with guidance naming the open section); auto-created sections must appear in enrollment dropdowns, class lists, schedules, and fee-schedule lookups identically.
  - Speed: promotion list is the "Promotion too slow" + N+1 item — counts-first query plus chunked/paginated rows, trim list reads to shown columns, gather related records in as few passes as the cashier list fix did; reuse `ajaxTable` pause/cancel/kept-list behavior; never change a figure to go fast.
  - Duplicates: survivor-pick flow (LRN/photo/birthdate disambiguation), history-follow (grades, attendance, ledger/payments), loser archived-not-deleted; audit every merge; reports read survivor only.
  - YoY summary: read-only grouped counts by action per school year from executed proposals/enrollments; no new write path.
- Data/records touched: promotion proposals + execution outcomes, sections/classes (new + Locked states), enrollments (section placement), archived duplicate shells; grades/payments follow survivor, never wiped.
- Roles/permissions involved: registrar (propose, survivor-pick), principal (approve/reject), directress (sign-off + summary read), teachers/students read-only; no permission-model change. Relatives: `promotion_workflow_proposal.md` (superseded, history), `promotion_batch_select_fix_20260925.md` (narrow checkbox fix, absorbed), `section-seeder-consistency.md` + `jhs-pure-saint-sections.md` (names — verify-only here).
- Peak-load note: EOY run is the worst day — counts-first, chunked rows, single-confirmation bulk, resume-safe execution; bulk actions keep per-student history.

## 11. Approval

> Approved by user on 2026-10-11.
