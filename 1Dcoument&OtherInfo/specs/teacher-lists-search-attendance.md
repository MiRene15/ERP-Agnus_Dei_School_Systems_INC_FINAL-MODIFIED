# Spec: Teacher Lists Search + Attendance Shortcut

- **Status**: Implemented
- **Created**: 2026-10-08
- **Approved by**: user on 2026-10-08
- **Revision**: 2026-10-08 — List-of-Classes cards gain an Attendance action (footer two-action row). Search-specs compliance recorded (§10). Pickers use Grade/Section dropdowns with immediate server fetch + loading, filters above skeleton. Re-approved by user on 2026-10-08.
- **Implemented**: 2026-10-08 — picker dropdowns with server fetch + loading above skeleton, master-list search, attendance buttons (header + cards); verified visually by user (all 5 checks pass).
- **Parent**: `teacher-gradebook-overhaul.md` (child 3 of 4 — depends on parent only)

## 1. Why We Need This
A teacher with six classes scrolls to find the right one, then walks menus to reach attendance — twice daily, every school day. Search boxes that narrow as you type plus a one-tap attendance shortcut remove the hunting.

## 2. Who Is Affected
* **Teachers** — only users; find classes and students fast, jump to attendance.
* Untouched: every other role, and attendance marking rules themselves.

## 3. Business Flow: Today vs After
- **As-is**: Grade Assessment and Computed Grades class pickers have no search; the master list has no search and no attendance shortcut (attendance lives at a separate address).
- **To-be**:
  1. Both class pickers offer Grade Level and Section dropdowns; changing either fetches the filtered class list immediately with the page loading state. The master list filters students by typed name/LRN.
  2. The master list filters students by name or LRN as you type.
  3. The master list header carries an Attendance button to that class's attendance page.
- **Preserved**: Picker behavior (links, periods), master-list contents, attendance marking logic — all unchanged.
- **Exceptions**: No match → plain `no match — clear the search` note, never a blank page.

## 4. How It Should Work
1. Teacher changes a picker dropdown → loading shows → filtered class list arrives; master-list typing narrows instantly with no page load.
2. Teacher taps Attendance on a master list or a List-of-Classes card → that class's attendance page opens.

## 5. Look & Feel (UX)
- Same search-box styling for the master list; pickers use two compact dropdowns (Grade Level, Section) in the same filter-row language as List of Classes. Changing a picker choice fetches at once with loading.
- Attendance button sits in the master-list header beside the class name, and as a second footer action on every List-of-Classes card beside View Master List — same navy primary style as Save buttons.
- Key states: default, filtering, no-match note, loading, dark mode intact.
- Fewest clicks: type-to-narrow; attendance one tap from where the teacher already is.

## 6. Business Rules
### Must always be true
- Picker dropdowns always fetch their filtered list immediately with loading; the master-list box filters instantly without a page load.
- Class pickers match the chosen Grade Level and Section exactly (blank choice = all).
- Master-list search matches student name and LRN case-insensitively.
- Attendance buttons always open that exact class's attendance page (header button and card actions alike).

### Must never happen
- Picker links, periods, master-list rows, and attendance logic must not change.
- Search must never hide the active/selected state or break dark styling.
- Any other role's lists must not gain or lose search.

### Edge cases and what happens then
- No match → plain note + clear action.
- Special characters in names → matched literally, never an error.
- Saved page from before → refresh shows the boxes; nothing to reconcile.

## 7. Out of Scope
- Batch entry, formula, rename (children 1, 2, 4); attendance logic; other roles.

## 8. Success Checks
- [ ] Both class pickers fetch on dropdown change with loading shown; combined and cleared states work.
- [ ] Master list filters as you type (name/LRN).
- [ ] No-match states show the plain note.
- [ ] Attendance buttons (master-list header + every class card) open the right class's attendance page.
- [ ] Picker links, periods, and attendance marking work exactly as before.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens: Grade Assessment + Computed Grades class pickers, teacher master list.
- Likely areas (from code inspection): pickers gain Grade Level + Section dropdowns wired into the pages' existing server reload (same filter-row language as List of Classes; loading skeleton shows, choices preserved across reloads); master list (`class-students` + `class-students-results`) keeps the typed name/LRN box from the proven inline pattern; header button links the existing `teacher.attendance` address for that class; class cards (`class-list-results`, one big link today) gain a footer two-action row — View Master List plus Attendance — without nested interactive elements.
- Data/records touched: none (filtering is display-only).
- Roles/permissions involved: owning teacher only (same page guards as today).
- Search-specs compliance: the master-list box is a display-only client filter — query and row text are lowercased before matching, so `santos` finds `Santos` (per `case-insensitive-search.md`); picker dropdowns ride the pages' existing server reload, so the debounce/abort/latest-wins rules (`search-debounce-ux.md`) apply through the same shared path as every other list. The underlying server lists were already converted under `case-insensitive-search.md`.

## 11. Approval
> Approved by user on 2026-10-08.
