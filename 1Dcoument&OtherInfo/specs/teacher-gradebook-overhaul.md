# Spec: Teacher Gradebook Overhaul (Parent)

- **Status**: Approved
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Revision**: 2026-10-08 — provisional weights replaced with final per-group table (§9); Examination → Quarterly Assessment. Complete-words pass 2026-10-08 (no abbreviations user-facing). Re-approved by user on 2026-10-08.

## 1. Why We Need This
Teachers enter assessment scores one student at a time, hunt classes without search, juggle two grade columns that say nearly the same thing, and walk to a separate page just to mark attendance. At grading-deadline peak that means late nights and copy errors. One connected gradebook — batch entry, auto-compute, one final grade, search everywhere, attendance one tap away — gives evenings back and removes the re-typing where errors breed.

## 2. Who Is Affected
* **Teachers** — only builders; batch-enter a whole class, adjust one final column, find classes fast, jump to attendance.
* **Registrar / Principal** — read the same stored final grades through report cards and unlock reviews; no new screens, no rework.
* **IT Admin** — runs one small additive data update once, checks the deadline-peak load.
* Untouched: cashier, nurse, librarian, students, parents.

## 3. Business Flow: Today vs After
- **As-is**: Open a class → open each student one by one → type scores → open Computed Grades → compare Computed vs Final columns → type finals → Batch Save → open Grade Corrections on mismatch → open Attendance from elsewhere.
- **To-be**:
  1. Open a class → batch-enter the whole class in the Date/LRN/Name/Type/Scores/Remarks sheet → one Save.
  2. Open Computed Grades → one Final column pre-filled from auto-compute → adjust borderlines → Batch Save.
  3. Find any class or student with search; jump to attendance from the master list; request unlocks via Grade Edit.
- **Preserved**: Grading periods, submit-to-lock, unlock-request approvals, report cards, promotion reads — all unchanged.
- **Exceptions**: A failed batch save keeps every entered row on screen with row-level error notes — never a half-saved class, never wiped input.

## 4. How It Should Work
1. Teacher picks a class and grading period, enters the batch sheet, saves once.
2. Scores normalize by raw-score basis (10/10 reads as 100%) and roll into Written Works / Performance Tasks / Quarterly Assessment using the class's row in the final weights table (§9).
3. Computed Grades shows one Final column, adjustable, batch-saved and locked as today.
4. Search filters every class and student list; master list carries an Attendance shortcut; corrections live under Grade Edit.

## 5. Look & Feel (UX)
- Same five teacher screens and shells; one primary action each (Save Scores / Batch Save Final Grades).
- Batch sheet: Date | LRN | Student Name (read-only) | Type (Written Works / Performance Tasks / Quarterly Assessment) | Scores raw/max | Remarks — one row per student, plain words.
- Key states per screen: default, loading, empty (no students/grades yet — what to do next), row error (which row, plain reason), success confirmation, submitted-locked, permission-denied — each designed.
- Fewest clicks: class → sheet is one tap; search narrows as you type; attendance is one tap from the master list.

## 6. Business Rules
### Must always be true
- One Save posts a whole class or nothing — never a half-saved class.
- Raw scores always normalize against their own maximum (10/10 = 100%) before weighting.
- Weights live in exactly one shared spot and always total 100 per the class's §9 row.
- The Final column always starts from auto-compute and stays teacher-adjustable until Batch Save.
- Submitted grades always lock behind the existing unlock approval.
- `Grade Edit` means the same unlock flow as today's Grade Corrections — new name, same rules.

### Must never happen
- A batch save must never wipe entered input on failure.
- Two grade columns must never return; computed-only display with no adjustable final must never ship.
- Weights must never live in two places or total anything but 100.
- Report cards, promotion reads, and principal views must never change shape in this overhaul.
- Attendance marking logic itself must not change — only the shortcut is new.

## 7. Out of Scope
- New grading components beyond Written Works/Performance Tasks/Quarterly Assessment; per-subject weight splits (noted as possible follow-up if you ask).
- Report card redesign; promotion rule changes; attendance logic changes.
- Principal/registrar unlock review pages (teacher-side rename only).
- Parent/student grade views.

## 8. Success Checks
- [ ] Batch sheet saves a full class in one submit with Date/Type/Scores/Remarks intact.
- [ ] 10/10 normalizes to 100% in auto-compute; the class's §9 weights applied.
- [ ] Computed Grades shows one Final column, adjustable, batch-saved, locks as today.
- [ ] All three gap lists filter as you type; master list has a working Attendance shortcut.
- [ ] Corrections read `Grade Edit` in teacher sidebar + titles; routes and unlock rules unchanged.
- [ ] Report cards and principal views read on undisturbed.

## 9. Open Questions (if any)
- ~~Provisional weights~~ — **resolved 2026-10-08**: final per-group weights below, provided by you. Child 2 §8 check 2 now verifies these exact splits instead of the provisional set.
- The third component is named **Quarterly Assessment** (was Examination in the first draft) to match your official weights table. Children 1–2 use Quarterly Assessment throughout; old `Exam` rows count inside Quarterly Assessment.

### Final weights (all rows total 100 — verified at spec time)
**Grades 1–10:**
| Group | Written Works | Performance Tasks | Quarterly Assessment |
|---|---|---|---|
| Languages / AP / ESP | 30% | 50% | 20% |
| Science / Math | 40% | 40% | 20% |
| MAPEH / EPP / TLE | 40% | 40% | 20% |

**SHS:**
| Group | Written Works | Performance Tasks | Quarterly Assessment |
|---|---|---|---|
| Core subjects | 25% | 50% | 25% |
| All other subjects | 25% | 45% | 30% |
| Work Immersion / Research / Business Enterprise Simulation / Exhibit Performance | 35% | 40% | 25% |

**TVL (TVL / Sports / Arts and Design tracks):**
| Group | Written Works | Performance Tasks | Quarterly Assessment |
|---|---|---|---|
| All other subjects | 20% | 60% | 20% |
| Work Immersion / Research / Exhibit Performance | 20% | 60% | 20% |

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: teacher Grade Assessment (+batch page), Computed Grades, List of Classes, master list, Grade Edit — five teacher screens.
- Likely areas (from code inspection): `TeacherController` grade-assessment/computed methods + `assessments`/`gradeAssessmentStudent`/`storeGradeAssessmentStudent`/`computedGrades`/`batchSubmitGrades` routes; `grade-assessment-results` + `computed-grades-results` partials (4-type columns today); `sidebar-teacher` Classes group + Grade Corrections entry; `grade-unlocks/index` titles; `class-students` master list + `teacher.attendance` shortcut target; assessment records gain date + remarks (additive only); one shared weights spot.
- Data/records touched: assessment rows (additive columns only); final grades stored as today. No rewrites, no backfill.
- Roles/permissions involved: teachers (build); registrar/principal (read finals as today); IT Admin (one additive update). Submit-lock + unlock approval unchanged.

## 11. Children (independent gates — one at a time)
| # | Child spec | Delivers | Depends on | Status |
|---|-----------|----------|-----------|--------|
| 1 | `assessment-batch-entry.md` | Batch sheet + 3-type + date/remarks | Parent | Not started |
| 2 | `computed-single-grade.md` | Weights + formula + single adjustable Final | Parent + child 1 | Not started |
| 3 | `teacher-lists-search-attendance.md` | 3 searches + attendance shortcut | Parent | Not started |
| 4 | `grade-edit-rename.md` | Labels-only rename | Parent | Not started |

Each child gets its own Scope, Business Rules, Success Checks, and Approval. `/exec-spec` runs one child at a time. No code without its child approved.

## 12. Approval
> Approved by user on 2026-10-08.
