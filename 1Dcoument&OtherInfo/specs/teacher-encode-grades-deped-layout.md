# Spec: Teacher Encode Grades — DepEd Layout

- **Status**: Implemented
- **Created**: 2026-10-09
- **Revision**: 2026-10-10 — Option A split: Encode = Draft/Save (build), Computed = viewing + Post (lock). Plus MATATAG weights + interim linear transmutation (70→75) + `–` empty + no QA split + raw-score examples.
- **Approved by**: user on 2026-10-10 (Option A split + interim transmutation A)
- **Implemented**: 2026-10-10 — card+column Encode (3 MATATAG buckets, fixed-name rows, live preview, Draft/Save) + Computed Post polish in system palette; all §8 checks passed by user.

## 1. Why We Need This

Teachers today see four wrong buckets (Written Tasks, Seatworks, Quizzes, Exam) instead of the three DepEd uses. Encoding means hopping between pages, and the computed table doesn't read like the official sheet — so grading nights are slow and copy errors creep in. One Encode page per class and quarter — three cards (Written Works, Performance Tasks, Quarterly Assessment), custom work columns inside each card, and a wide computed table that does the math live — turns encoding into minutes and makes the final grade easy to trust.

## 2. Who Is Affected

- **Teachers** — the only builders. Add assessments, name custom works (Quiz 1, Seatwork 2), encode scores per student, then draft, save, or post grades.
- **Registrar / Principal** — read the same stored final grades through report cards and unlock reviews. No new screens, nothing new to learn.
- **IT Admin** — checks the page stays fast on grading-deadline nights.
- Untouched: cashier, nurse, librarian, students, parents.

## 3. Business Flow: Today vs After

- **As-is**: Open Grade Assessment (header still says "Written Work, Quiz, Seatwork, Exam") → pick students one by one or open a separate batch sheet with per-row type dropdowns → open Computed Grades on another page (one number per bucket + Final) → Batch Save.
- **To-be**:
  1. Open the class + quarter → one **Encode Grades** page.
  2. Per card, tap **+ Add Assessment** → name the work (e.g. Quiz 1, Seatwork 2, Activity 1, Periodical Exam), set total points + date → a new score column appears under that card's bucket.
  3. Encode each student's score in that column (add-row per student where needed; same student repeatable across works).
  4. The computed table below updates live: per bucket (works count | total | percentage | weighted), then Initial Grade → Quarterly Grade.
  5. Finish with **Save as Draft** (keep editing), **Save Grades** (store), or **Post Grades** (lock).
- **Preserved**: Grading periods, school-year lock, posted-grade lock with the existing unlock-request approval, report cards and promotion reads (same stored finals, same shape).
- **Exceptions**: A failed save keeps every entered score on screen with a plain row note — never a half-saved class, never wiped input. Old Quiz/Seatwork rows count inside Written Works; old Exam rows count inside Quarterly Assessment — history stays readable, nothing is dropped.

## 4. How It Should Work

1. Teacher picks a class + quarter and lands on Encode Grades. The header shows subject, subject code, quarter, and school year, with Help and Back.
2. Three cards show: Written Works (weight %), Performance Tasks (weight %), Quarterly Assessment (weight %) — each with its live assessment count and a **+ Add Assessment** button.
3. Tapping + Add Assessment opens a small form: work name (free text, e.g. Quiz 1), total points, date (defaults today). Saving adds one score column under that card.
4. Teacher types each student's score in the new column (raw score; never more than the total). Empty cells stay empty — they are not zeros.
5. The computed table shows one row per student: per bucket — number of works, total raw, percentage score, weighted score — then Initial Grade and Quarterly Grade.
6. Teacher finishes on Encode with two buttons: **Save as Draft** (keeps everything editable) and **Save Grades** (stores the quarter's grades). Locking happens on Computed Grades (Option A split 2026-10-10): Computed is the viewing page with one **Post Grades** button (asks "Post these grades? Locked quarters need an unlock request to change." before locking).
7. Success confirms plainly (how many saved/posted, how many skipped because already posted). Posted rows show locked with an unlock hint.

## 5. Look & Feel (UX)

- Where it lives: teacher Grades area, one page per class + quarter, titled **Encode Grades – DepEd K-12** (build page) plus **Computed Grades** (viewing + lock page). One primary action per zone (per card: + Add Assessment; Encode bottom: Draft / Save; Computed bottom: Post).
- **Palette: our system's own** — navy `#24225C`, lilac `#A39FE9`, gold `#E5C06A`, off-white `#F8F9FA`, Outfit type. The reference screenshots give the *layout only*; none of their green ships.
- Layout top to bottom (Encode): header card (subject, code, quarter, school year + Help/Back) → three bucket cards → formula strip (the 3 DepEd steps in plain words) → wide computed table (live preview) → two bottom buttons (Draft / Save). Computed Grades mirrors the same wide table read-only → one Post button.
- Card subtitles hint at what belongs: Written Works ("Quizzes, Seatworks, Summative Tests, Assignments"), Performance Tasks ("Activities, Projects, Presentations"), Quarterly Assessment ("Periodical Exams, Final Tests").
- Table columns per bucket: works count | total | percentage | weighted; then Initial Grade | Quarterly Grade. On small screens the table scrolls sideways with student name + Quarterly Grade pinned.
- Key states: default, loading, empty (no assessments yet → "Add your first assessment" per card; no students → plain next step), row error (which student + plain reason), draft-saved / saved / posted confirmations, posted-locked rows (read-only + unlock hint), permission-denied (another teacher's class).
- Plain words only: `Written Works`, `Performance Tasks`, `Quarterly Assessment`, `Save as Draft`, `Save Grades`, `Post Grades`. No abbreviations user-facing.

## 6. Business Rules

### Must always be true

- Buckets for all new work are exactly Written Works, Performance Tasks, Quarterly Assessment. Custom names (Quiz 1, Seatwork 2) are labels *inside* a bucket and never change which bucket counts them.
- Weights come from the tables below (MATATAG DO 15, s. 2026) — resolved automatically from the class's subject (and level/track); the teacher never picks weights. Every row totals 100, except the two QA-None rows which total 100 across WW + PT.
- Raw scores always normalize against their own total: score ÷ total × 100 (e.g. Quiz 1 10/10 = 100%, 8/10 = 80%, 45/50 = 90%, Exam 38/50 = 76%). Bucket percentage = (sum of raw ÷ sum of max) × 100 across that bucket's works.
- Weighted = percentage × bucket weight. All three buckets compute the same way (no inner split — per teacher interview, ST1/ST2/Term Exam are simply QA works averaged by raw totals). Initial Grade = sum of weighted scores. Quarterly Grade = Initial Grade through transmutation: INTERIM linear map until the official DO 15 band table lands (anchors 100→100, 70.00→75 passing, 0→60; 70–100: 75 + (Initial−70)×25/30; below 70: 60 + (Initial÷70)×15; rounded, clamped 60–100). Empty buckets show `–` per source and add nothing.
- An empty bucket (no assessments, or no scores) shows `–` and adds nothing — it never drags the grade down.
- Raw score never exceeds its total; future dates are flagged; both hold the save with a plain per-row note.
- One save posts the whole class or nothing. Double-tapping Draft/Save/Post never saves or posts twice.
- Posted quarters lock exactly like today's Submitted lock — changes need the same unlock request and approval.

### Must never happen

- Written Tasks, Seatworks, Quizzes, or Exam must never appear as buckets or choices for new work.
- Weights must never live in two places or total anything but 100 (QA-None rows: WW + PT = 100, QA skipped).
- Old Quiz/Seatwork scores must never be dropped or zeroed — they count inside Written Works; old Exam rows count inside Quarterly Assessment.
- A failed save must never wipe entered scores.
- Report cards, promotion reads, and principal views must never change shape in this spec.

### Edge cases and what happens then

- 40+ students with one bad cell → save held, bad cells flagged, all input kept.
- All cells empty → plain `nothing to save` note, no write.
- Date in the future or raw above total → flagged per row, save held.
- QA-None subjects (SHS Work Immersion, SHS Research/Design tracks) → no QA card math; the QA columns show `–` and the Initial Grade comes from WW + PT only.
- Kinder–Grade 3 descriptive grading (MATATAG Key Stage 1) → out of scope for this Encode page; this spec covers numeric grading for Grades 4–12 only.
- Already-posted students in the batch → skipped with a plain count; the rest save/post.
- Another teacher's class address → denied, same ownership check as today.

### Weights — Grades 4–10 (MATATAG, DO 15 s. 2026)

| Group | Written Works | Performance Tasks | Quarterly Assessment (= Examinations) |
|---|---|---|---|
| English, Filipino, Math, Science, AP, GMRC / Values Ed | 20% | 50% | 30% |
| EPP / TLE / MAPEH | 20% | 60% | 20% |

### Weights — Senior High

| Group | Written Works | Performance Tasks | Quarterly Assessment |
|---|---|---|---|
| Core subjects & Other Academic Electives | 20% | 50% | 30% |
| Field Exposure, Arts Apprenticeship, Creative Production & Innovation | 15% | 70% | 15% |
| Arts, Sports, Health & Wellness Electives | 20% | 60% | 20% |
| Research Electives & Design and Innovation | 40% | 60% | None (skip) |
| TechPro Electives | 15% | 65% | 20% |
| Work Immersion | 20% | 80% | None (skip) |

*Note: the Grades 4–10 rows follow MATATAG DO 15, s. 2026 (replacing the old DO 8 rows 30/50/20 etc.). The SHS rows above were already MATATAG-correct and are unchanged; they replace the old SHS rows (Core 25/50/25 etc.) on approval of this spec.*

## 7. Out of Scope

Report-card redesign; promotion rule changes; attendance logic changes; principal/registrar review pages; parent/student grade views; CSV import/export of scores; Kinder–Grade 3 descriptive grading (MATATAG Key Stage 1). Noted as follow-up candidates: CSV import/export, per-student grade history view, low-score / at-risk flags, print-friendly class summary.

## 8. Success Checks

- [ ] Only Written Works, Performance Tasks, Quarterly Assessment appear as buckets; custom Quiz/Seatwork names sit inside them.
- [ ] Each card shows its live weight % and assessment count; + Add Assessment adds a named column with total points + date.
- [ ] Table shows per bucket (works count | total | percentage | weighted), then Initial Grade and Quarterly Grade.
- [ ] The weights tables above apply (MATATAG DO 15, s. 2026) — spot-check one Grades 4–10 class (20/50/30 or 20/60/20), one SHS class, and one QA-None class (QA shows `–`, WW + PT total 100).
- [ ] 10/10 reads as 100%; empty bucket shows `–` and drags nothing.
- [ ] Save as Draft / Save Grades / Post Grades each confirm plainly; Post asks once before locking; posted rows lock with an unlock hint.
- [ ] Bad cell holds the save, flags the row plainly, keeps all input; double-tap never double-saves or double-posts.
- [ ] Old Quiz/Seatwork history counts inside Written Works, old Exam inside Quarterly Assessment.
- [ ] Page uses our system palette and type — none of the reference green ships.
- [ ] Report cards and principal views read on undisturbed.

## 9. Open Questions (if any)

- ~~Transmutation lookup~~ — **resolved interim 2026-10-10**: no official sheet on hand; approved interim linear map (100→100, 70.00→75, 0→60) in `TransmutationService::INTERIM`. Swap to official DO 15 bands the day the registrar supplies them.
- ~~SHS subject-to-group mapping~~ — **resolved 2026-10-10**: SHS rows in §6 already match MATATAG; spot-check registrar subject names against the 6 SHS rows at build time. Grades 4–10 use the 2 MATATAG rows in §6.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: teacher Grade Assessment pages (`grade-assessment`, per-student page, batch sheet) and Computed Grades (`computed-grades`) — merged into one Encode flow; per-student page keeps working with the same three buckets.
- Likely areas of the codebase (from code inspection): `Portal/TeacherController` assessment + computed + batch-submit actions with existing `teacher.grade-assessment*` / `teacher.computed-grades*` / `teacher.assessments*` routes; `portal/teacher/partials/grade-assessment-results` (header still names the old four types) and `computed-grades-results` (single-number-per-bucket table today) partials rebuilt to the card + wide-table layout; the one shared weights spot (`GradeWeightService` — today's 8-row DO-8 table becomes the 2 + 6 MATATAG rows from §6, with QA-None rows skipping QA math, NO QA inner split per teacher confirmation, adjusted transmutation 70→75 with new transmutation table/service, and the resolver covering Grades 4–10 + new SHS groups); assessment records keep additive date/remarks with a free-text work-name label; ownership, school-year lock, and posted-lock + unlock-request checks unchanged; whole-class writes stay single-transaction with per-row errors re-rendered from submitted input. Save-as-Draft keeps everything editable; Computed Grades (Save) stores; Post locks — 3 buttons + transaction + FormRequest validation + posted-lock hint.
- Data/records touched: assessment rows (named work + date + scores per student) and stored final grades (same shape as today — Draft/Save map to today's editable/stored states, Post maps to today's Submitted lock). No rewrites of other tables, no backfill; legacy type folding (Quiz/Seatwork → Written Works, Exam → Quarterly Assessment) happens at read/compute time only.
- Roles/permissions involved: owning teacher only (same checks as today); registrar/principal read stored finals as today. Styling from the existing portal shell variables (navy `#24225C`, lilac `#A39FE9`, gold `#E5C06A`, off-white `#F8F9FA`, Outfit) — reference greens are layout reference only.

## 11. Approval

> Approved by user on 2026-10-10 (Option A split + interim transmutation A).
