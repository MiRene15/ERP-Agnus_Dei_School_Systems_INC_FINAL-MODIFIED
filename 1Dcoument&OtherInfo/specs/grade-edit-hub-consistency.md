# Spec: Grade Edit Hub Consistency

- **Status**: Implemented
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09
- **Implemented**: 2026-10-09 — Principal and Registrar hubs upgraded with identical tools (search, school year filter, batch approve/reject); principal-scoped routes added; Principal dashboard pending count added; edge case guards added; teacher cancelled-status display fixed. Verified by user.

## 1. Why We Need This

The Principal and Registrar both review grade unlock requests from teachers, but they experience the workflow very differently. The Registrar has a full-featured standalone page with search, filters, and batch tools. The Principal has only a tab in the Approvals hub with no search, no batch operations, and no dashboard visibility. The Principal's approve/reject actions are even recorded under registrar-named routes, making audit logs confusing. This spec makes the grade unlock review experience consistent for both roles: same tools, same placement pattern, same dashboard visibility, and truthful audit trails.

## 2. Who Is Affected

- **Principal** — Gains full grade unlock review capabilities (search, filter, batch) in their Approvals hub. Gains a pending count on their dashboard. Actions are recorded under principal-scoped routes.
- **Registrar** — Moves grade unlock review from a standalone page into their Requests hub. Keeps all existing tools (search, filter, batch). Gains consistent placement with the Principal.
- **Teachers** — No change to their workflow. They still submit grades, request unlocks, and re-submit after approval. They may see clearer status labels if the hub tabs improve consistency.
- **IT Admin** — No direct impact. Audit logs become more truthful as principal actions are recorded under principal routes.

## 3. Business Flow: Today vs After

- **As-is**: Teacher submits grades → grades lock → teacher requests unlock → request appears in Registrar's standalone page (full tools) and Principal's Approvals hub tab (basic tools) → either approves or rejects → grades reopen or stay locked. Principal's actions post to registrar-named routes. Principal has no dashboard visibility. Registrar has a dashboard count.
- **To-be**: Teacher submits grades → grades lock → teacher requests unlock → request appears in both the Registrar's Requests hub and the Principal's Approvals hub with identical tools (search, filter, batch) → either approves or rejects → grades reopen or stay locked. Principal's actions post to principal-scoped routes. Both dashboards show a pending count.
- **Preserved**: The lock/unlock rules (Pending/Submitted statuses), the teacher-initiated-only workflow, the approval chain, and the audit trail. No change to who can request, who can approve, or what happens to grades.
- **Exceptions**: Locked school years block all grade unlock actions. Double-approval is prevented (second action sees "already processed"). If a teacher re-submits grades while an unlock is pending, the request is auto-cancelled.

## 4. How It Should Work

1. Teacher submits grades for a class and term → grades lock (status becomes "Submitted").
2. Teacher needs to correct a grade → submits an unlock request with a reason.
3. The request appears in the Registrar's Requests hub (Grade Edit tab) and the Principal's Approvals hub (Grade Edit tab) with identical tools: search by student/class/teacher, school year filter, batch approve/reject with checkboxes.
4. Either the Principal or Registrar reviews the request → approves or rejects.
5. If approved → grades reopen (status returns to "Pending") → teacher can edit and re-submit.
6. If rejected → grades stay locked → teacher sees the rejection.
7. Both dashboards show a pending grade unlock count with a quick link to the respective hub.
8. The old standalone `/registrar/grade-unlocks` page is removed. All review happens in the hubs.

## 5. Look & Feel (UX)

- **Where it lives**: Grade Edit tab in the Registrar's Requests hub and the Principal's Approvals hub. No standalone pages.
- **Primary action**: Approve or reject a grade unlock request.
- **Key states**:
  - *Default*: List of pending requests with search bar, school year filter, and batch checkboxes.
  - *Empty*: "No pending grade unlock requests" with a note that new requests from teachers will appear here.
  - *Error*: "This school year is locked" or "This request has already been processed."
  - *Success*: "Grade unlock approved — grades are now open for teacher editing" or "Grade unlock rejected — grades remain locked."
  - *Permission-denied*: Teachers and other roles cannot access the Grade Edit tab.
- **Plain-language labels**: "Grade Edit" tab, "Approve" / "Reject" buttons, "Batch Approve" / "Batch Reject" for bulk actions.
- **Fewer clicks**: Dashboard badge links directly to the hub with the Grade Edit tab active.
- **What the user sees first**: Pending requests sorted by most recent, with the search bar and filter visible without scrolling.

## 6. Business Rules

### Must always be true
- Both hubs show the same grade unlock data with the same tools.
- Principal and Registrar can both approve, reject, batch approve, and batch reject.
- Principal actions are recorded under principal-scoped routes in audit logs.
- Both dashboards show a pending grade unlock count.
- The teacher-initiated-only workflow is preserved: no direct grade editing by Principal or Registrar.
- The old standalone `/registrar/grade-unlocks` page is removed.

### Must never happen
- A principal's action is recorded under a registrar-named route.
- A grade unlock request can be approved or rejected twice.
- A grade unlock request can be created or approved for a locked school year.
- A teacher can edit a submitted grade without an approved unlock request.
- The Principal or Registrar can directly change a student's grade without a teacher unlock request.

### Edge cases and what happens then
- **Locked school year**: All grade unlock actions (create, approve, reject) are blocked with a clear message: "This school year is locked. Grade unlocks are not permitted."
- **Double-approval**: If two approvers act on the same request simultaneously, the second action sees: "This request has already been processed."
- **Teacher re-submits while unlock pending**: The unlock request is auto-cancelled with a note: "Grades have been re-submitted. This unlock request is no longer needed."
- **Batch operation with mixed statuses**: If some requests in a batch were already processed, the system processes the pending ones and reports: "X approved, Y already processed."

## 7. Out of Scope

- Direct grade editing by Principal or Registrar (bypassing teacher unlock request).
- Grade formula or assessment workflow changes.
- Report card redesign.
- Changes to the teacher's grade submission or unlock request workflow.
- Nice-to-haves or future enhancements.

## 8. Success Checks

- [ ] Registrar's Requests hub has a Grade Edit tab with search, school year filter, and batch approve/reject.
- [ ] Principal's Approvals hub has a Grade Edit tab with search, school year filter, and batch approve/reject.
- [ ] Both tabs show the same data for the same requests.
- [ ] Principal's approve/reject actions are recorded under principal-scoped routes (visible in audit logs).
- [ ] Both dashboards show a pending grade unlock count.
- [ ] The old standalone `/registrar/grade-unlocks` page is removed (404 or redirect).
- [ ] Locked school year blocks grade unlock actions with a clear message.
- [ ] Double-approval is prevented.
- [ ] Teacher re-submission auto-cancels a pending unlock request.
- [ ] Teachers experience no change to their grade submission or unlock request workflow.

## 9. Open Questions (if any)

None.

## 10. Technical Notes (for developers)

- **Affected screens**: Registrar Requests hub (`portal/registrar/requests.blade.php`), Principal Approvals hub (`portal/principal/approvals.blade.php`), both dashboards, teacher grade unlock request page (no change expected).
- **Likely areas of the codebase**:
  - `GradeUnlockController` — add principal-scoped routes and methods; remove or deprecate standalone registrar page method.
  - `routes/web.php` — add `principal.grade-unlocks.*` routes; remove or redirect `registrar.grade-unlocks.*` standalone page route.
  - `RegistrarController@requests` — add Grade Edit tab with full tools.
  - `PrincipalController@approvals` — enhance Grade Edit tab with full tools.
  - Dashboard controllers — add pending unlock count for Principal.
  - Views: `portal/registrar/requests.blade.php`, `portal/principal/approvals.blade.php`, dashboard partials.
- **Data/records touched**: `grade_unlock_requests` table (no schema change expected), `grades` table (no schema change), `activity_log` (audit entries).
- **Roles/permissions involved**: Principal (role 9), Registrar (role 2). No new permissions needed; both roles already have access to grade unlock review.

## 11. Approval

> Approved by user on 2026-10-09.
