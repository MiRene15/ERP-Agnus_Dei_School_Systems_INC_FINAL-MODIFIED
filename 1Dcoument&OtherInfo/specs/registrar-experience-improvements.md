# Spec: Registrar Experience Improvements

- **Status**: Implemented (2026-10-07 — all 9 changes built across 5 slices + hub parity, 12/12 success checks confirmed by user)
- **Created**: 2026-10-07
- **Approved by**: user on 2026-10-07 (covering changes #1–#7; changes #8–#9 pending re-approval)

## 1. Why We Need This

The registrar's daily work is slowed by inconsistency and manual repetition. Search bars look and behave differently on every page — some have school year dropdowns, some don't, some use instant search, others need a button press. Report cards have a duplicate "Grade Grade" heading. Discount requests have no search at all. End-of-year promotion requires selecting each student one by one. Grade unlocks are approved one at a time. Subjects still offer CSV upload when the school prefers manual entry. And during enrollment, assigning students to sections is one-by-one.

This spec unifies the search experience, adds batch actions for repetitive work, removes the CSV upload path, and adds bulk section assignment — so the registrar works faster at peak periods (enrollment season, end-of-year promotion).

## 2. Who Is Affected

- **Registrar** — the only role whose screens change. All seven improvements directly affect their daily workflow.
- **Principal** — approves promotions and grade unlocks. Batch actions mean more proposals arrive at once; the approval screen must handle the volume.
- **Directress** — signs off on promotions. Same as Principal: batch sign-off must work cleanly.
- **Cashier** — shares the discount requests page. The new search bar must work for both roles.
- **Teachers** — no visible change, but grade unlock batching means their correction requests are processed faster.

## 3. Business Flow: Today vs After

- **As-is**: Each registrar page has its own search pattern (or none). Promotion requires per-student action selection. Grade unlocks are one-by-one. Subjects offer CSV upload. Discount requests have no search. Section assignment is one-by-one during admission approval.
- **To-be**:
  1. Every registrar list page has the same search bar: school year dropdown + text input + Search/Clear buttons, consistent styling and behavior.
  2. Report cards are grouped by grade level with clean headings (no duplicate "Grade Grade").
  3. Discount requests page has a working search bar.
  4. Promotion page has a "Batch Qualified — Level Up Only" button beside "Send Proposals to Principal" — one click proposes promotion for all qualified students.
  5. Subjects page has no CSV upload — manual add/edit/delete only.
  6. Grade unlocks page allows selecting multiple pending requests and approving/rejecting in one action.
  7. Sections page has a "Bulk Assign" mode — select multiple students and assign them to a target section in one action.
  8. Withdrawal requests show who decided them as "Approved/Rejected by Head Registrar — {name}" and offer a floating details modal per row.
  9. Every registrar search shows a visible loading state while searching (amber "Searching…" notice + dimmed old results), like the cashier's payments page.
- **Preserved**: Promotion proposals still require Principal approval → Directress sign-off. Grade unlocks still require Principal or Registrar approval. Locked school years still block all modifications. The shared discount requests page still works for both registrar and cashier.
- **Exceptions**: Batch actions on locked school years are blocked with a clear message. Batch Level Up with zero qualified students shows a calm notice. Already-decided grade unlocks cannot be selected for batch action. Students already enrolled in a section cannot be re-assigned without explicit confirmation.

## 4. How It Should Work

1. Registrar opens any list page — admissions, report cards, withdrawals, sections, subjects, grade unlocks, discount requests — and sees the same search bar at the top: school year dropdown, text input, Search/Clear buttons.
2. Registrar types in the search box — results filter after a short pause (600ms), no button press needed.
3. Registrar opens report cards — students are grouped by grade level (Grade 7, Grade 8, etc.) with clean headings.
4. Registrar opens discount requests — a search bar filters the list by student name or discount type.
5. Registrar opens promotion page — beside "Send Proposals to Principal" is a "Batch Qualified — Level Up Only" button. Clicking it shows a confirmation: "This will propose promotion for 47 qualified students. Unqualified students will be skipped." Registrar confirms — proposals are created for qualified students only.
6. Registrar opens subjects — no CSV upload option. Manual add/edit/delete only.
7. Registrar opens grade unlocks — checkboxes appear beside each pending request. Registrar selects multiple, then clicks "Approve Selected" or "Reject Selected" — all selected requests are processed in one action.
8. Registrar opens sections during enrollment — clicks "Bulk Assign" to toggle selection mode. Students appear with checkboxes. Registrar selects students, chooses a target section from a dropdown, and clicks "Assign Selected (N)". Confirmation shows: "This will assign 25 students to Grade 7 — St. Joseph. Continue?" Registrar confirms — all students are assigned in one action.
9. Registrar opens withdrawal requests — decided rows read "Approved by Head Registrar — {name}" (or "Rejected by Head Registrar — {name}"). Each row has a "View" button that opens a floating modal with the full details: student + number, section, full reason, status, refund amount/date, processed by/at, and remarks.
10. Registrar types in any search box — the old results dim and an amber "Searching…" notice appears until the new results arrive. The list is never blanked while searching.

## 5. Look & Feel (UX)

- **Search bars**: Same position (above the table), same height, same placeholder pattern across all registrar pages. School year dropdown pre-selected to current year. Text input with placeholder text relevant to the page. Search and Clear buttons styled consistently.
- **Batch Level Up button**: Sits on the same row as "Send Proposals to Principal" — right beside it, same size, but visually distinct (secondary/outline style vs. primary). Label: "Batch Qualified — Level Up Only".
- **Confirmation modal**: Before any batch action, shows count of students/requests to be affected in plain language. "This will propose promotion for 47 qualified students. Unqualified students will be skipped." Cancel and Confirm buttons.
- **Batch grade unlock**: Checkboxes in each row of the pending table. When one or more are selected, a floating action bar appears at the bottom: "Approve Selected (3)" and "Reject Selected (3)" buttons.
- **Bulk section assignment**: On the sections page, a "Bulk Assign" button toggles selection mode. Students appear with checkboxes. Registrar selects students, chooses a target section from a dropdown, and clicks "Assign Selected (N)". Confirmation modal shows count and target section before executing.
- **Empty states**: "No results match your search" for search bars. "No qualified students found for this school year" for batch Level Up with zero qualified.
- **Locked year message**: "School year 2025-2026 is locked — modifications are not allowed."
- **Withdrawal modal**: floating card centered on screen with backdrop blur, close button + closes on backdrop click. Shows student, section, full reason, status badge, refund details, decided-by/at, and remarks. One modal shell reused for all rows.
- **Search loading state**: amber "Searching…" notice above dimmed old results while a search is in flight; skeleton blocks only on first load (no results yet).

## 6. Business Rules

### Must always be true
- Every registrar list page has a search bar matching the cashier's payments pattern (school year dropdown + text input + Search/Clear).
- Search bars use a 600ms pause before filtering — no results fire on every keystroke.
- Batch Level Up only proposes promotion for students who meet the passing criteria (GWA ≥ passing grade, no failing subjects).
- Batch grade unlock only processes pending requests — already-decided ones are locked from selection.
- Bulk section assignment shows a confirmation with the exact count of students and the target section before executing.
- Bulk section assignment cannot assign students to a locked school year.
- Students already enrolled in a section cannot be re-assigned without explicit confirmation.
- All batch actions show a confirmation with the exact count before executing.
- Locked school years block all batch actions with a clear message.
- The shared discount requests page works identically for registrar and cashier.
- Report card group headings show the grade level exactly once (e.g., "Grade 7", not "Grade Grade 7").
- Decided withdrawal rows name the decider as "Approved/Rejected by Head Registrar — {name}".
- Old search results stay visible but dimmed while a new search loads; an amber "Searching…" notice shows until new results arrive.

### Must never happen
- A batch action executes without confirmation.
- An unqualified student is included in a Batch Level Up proposal.
- A locked school year allows any modification.
- A cashier sees different behavior on the shared discount requests page.
- A grade unlock batch action processes an already-decided request.
- A student is assigned to a section that doesn't match their grade level.
- A search blanks the list while the new results are still loading.

### Edge cases and what happens then
| Case | What happens |
|---|---|
| Batch Level Up with zero qualified students | Button shows calm notice: "No qualified students found for this school year" — no empty submission |
| Batch Level Up with some unqualified | Only qualified students are proposed; unqualified are skipped silently (confirmation shows count before sending) |
| Batch grade unlock with mixed pending/approved | Only pending requests can be selected; already-decided ones are locked from selection |
| Bulk assign with no students selected | Button is disabled — "Select students to assign" |
| Bulk assign with no target section chosen | Button is disabled — "Choose a target section" |
| Student already in a section | Confirmation warns: "12 students are already enrolled. Reassign them?" |
| Batch action on a locked school year | Blocked with clear message: "School year X is locked — modifications are not allowed" |
| Discount request search with no results | Standard empty state: "No discount requests match your search" |
| Search with fewer than 2 characters | No search fires — waits for meaningful input |
| Withdrawal modal opened, then search reloads | Modal stays open with its snapshot; closing and reopening shows fresh data |
| Slow search on any registrar list | Old results dim with amber "Searching…" notice; never a blank list |

## 7. Out of Scope

- LRN management — no dedicated LRN interface
- Transfer credentials — formal transfer-in/out workflow
- Academic calendar — school year calendar definition
- Transcripts — consolidated academic records across years
- Clearance process — formal sign-off workflow
- Mass enrollment — bulk enrollment of returning students
- Report card template customization — format stays fixed
- Payment reminder UI — cashier-owned, separate spec

## 8. Success Checks

- [ ] Every registrar list page has a search bar matching cashier's payments (same layout, same behavior)
- [ ] Report cards are grouped by grade level with no duplicate "Grade Grade" text
- [ ] Discount requests page has a working search bar
- [ ] "Batch Qualified — Level Up Only" button sends only qualified students, skips unqualified
- [ ] Subjects page has no CSV upload option — manual add/edit/delete only
- [ ] Grade unlock page allows selecting multiple pending requests and approving/rejecting in one action
- [ ] Bulk section assignment allows selecting multiple students and assigning them to a section in one action
- [ ] Bulk section assignment shows confirmation with count and target section before executing
- [ ] Batch actions show a confirmation with count before executing
- [ ] Locked school years block batch actions with a clear message
- [ ] Decided withdrawal rows read "Approved/Rejected by Head Registrar — {name}" and each row opens a floating details modal
- [ ] Typing in any registrar search dims old results and shows an amber "Searching…" notice until new results arrive

## 9. Open Questions

None — all decisions made during interview.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*

- **Cashier search pattern to replicate:** `resources/views/portal/cashier/payments.blade.php` lines 19–34 (search bar HTML) + lines 108–202 (Alpine `searchPayments()` component). Uses `window.AgnusSearch.debounce()` for 600ms pause, `AbortController` for request cancellation, handles HTTP 429 rate limiting.

- **All registrar list pages currently use `ajaxTable`** (defined in `resources/js/app.js` lines 78–231): admissions, report-cards, withdrawals, sections, subjects. These need their search bar markup updated to match the cashier pattern (add school year dropdown, align styling). Grade unlocks and discount requests have **no ajaxTable** — they are server-rendered tables and need the component added.

- **Pages to update:**
  - `resources/views/portal/registrar/admissions-index.blade.php` — add school year dropdown to search bar
  - `resources/views/portal/registrar/report-cards/index.blade.php` — verify grouping, fix any remaining "Grade Grade" text
  - `resources/views/portal/registrar/withdrawals-index.blade.php` — add school year dropdown
  - `resources/views/portal/registrar/sections/index.blade.php` — add school year dropdown, add Bulk Assign mode
  - `resources/views/portal/registrar/subjects/index.blade.php` — add school year dropdown, remove CSV import section (lines 74–94)
  - `resources/views/portal/registrar/grade-unlocks/index.blade.php` — add ajaxTable + search bar, add checkboxes + batch action bar
  - `resources/views/portal/discount-requests/index.blade.php` — add ajaxTable + search bar (shared with cashier)
  - `resources/views/portal/registrar/promotion/index.blade.php` — add "Batch Qualified — Level Up Only" button beside existing "Send Proposals to Principal"

- **Promotion batch endpoint:** New route — `POST /registrar/promotion/batch-qualified` → `PromotionWorkflowController@registrarBatchQualified`. Reuses existing `PromotionService` logic but auto-selects only qualified students (GWA ≥ passing, no failing subjects). Must check `school_year_locked()`.

- **Grade unlock batch endpoints:** New routes — `POST /registrar/grade-unlocks/batch-approve` and `POST /registrar/grade-unlocks/batch-reject` → `GradeUnlockController@batchApprove` / `batchReject`. Accepts array of request IDs. Must check `school_year_locked()`.

- **Bulk section assignment endpoint:** New route — `POST /registrar/sections/bulk-assign` → `SectionController@bulkAssign`. Accepts array of student IDs + target section ID. Must check `school_year_locked()`. Should validate that target section's grade level matches students' grade levels. Reuses existing enrollment creation logic from `RegistrarAdmissionController@approve`.

- **Subjects CSV removal:** Remove from `resources/views/portal/registrar/subjects/index.blade.php` (lines 74–94), remove routes `registrar.subjects.template` and `registrar.subjects.import` from `routes/web.php`, remove `template()` and `import()` methods from `app/Http/Controllers/Admin/SubjectController.php`.

- **Report cards grouping:** `resources/views/portal/registrar/partials/report-cards-results.blade.php` — already groups by `$grade` variable from `ReportCardController@index` line 50 (`groupBy(fn($e) => $e->section?->grade_level ?? 'Unknown')`). The "Grade Grade" fix was already applied (line 3 now renders `{{ $grade }}` without prefix).

- **Locked school year check:** `app/helpers.php` line 57 — `school_year_locked(?string $schoolYear): bool`. Used in controllers via `if (school_year_locked($year)) { return back()->with('error', ...); }`.

- **Shared discount requests page:** `resources/views/portal/discount-requests/index.blade.php` — used by both registrar (role 2) and cashier (role 3). Breadcrumb checks `role_id === 3` for cashier dashboard link. Any changes must work for both roles.

- **Routes file:** `routes/web.php` lines 145–194 contain all registrar routes. New routes for batch promotion, batch grade unlock, and bulk section assignment go here.

## 11. Approval

> Approved by user on 2026-10-07.
