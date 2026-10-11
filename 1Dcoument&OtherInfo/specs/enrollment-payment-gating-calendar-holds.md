# Spec: Enrollment Payment Gating — Calendar Holds

- **Status**: Approved
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11
- **Parent**: `enrollment-payment-gating.md` (child 3 of 3 — depends on child 2)

## 1. Why We Need This

Holds and clearance signals confuse families at re-enrollment, and nobody can state the school year or when grading closes. This child sets three plain rules: the school year runs July to March, grading closes one week after exams, holds appear only when a new school year is announced, and the old clearance status goes away — holds carry the signal instead.

## 2. Who Is Affected

- **Families and students (primary)** — see holds only when they matter (new year announced), never a stale clearance label.
- **Registrar** — announces the new school year, which raises holds; no per-student hold editing.
- **Cashier / Librarian / Clinic** — raise the underlying dues (fees, books, visits) that the hold summarizes; they don't toggle holds directly.
- **IT Admin** — owns the year setting and the clearance-status removal. Principal/Directress read-only.

## 3. Business Flow: Today vs After

- **As-is**: clearance status shows alongside holds; hold timing rules are unwritten; grading-close timing lives outside the system.
- **To-be**: year set to July–March → grading for each period closes 1 week after exams (teachers notified, late edits need the grade-edit chain from `grade-edit-approval-chain.md`) → when Registrar announces the new school year, account holds appear for returnees with dues → family clears dues → hold lifts → re-enrollment follows the child-1/child-2 gates. Clearance status is removed from all screens.
- **Preserved**: who-may-see-what, audit of hold raise/lift, school-year locks, the child-2 gate wording (which references hold states).
- **Exceptions**: graduates finishing their final level never get re-enrollment holds; mid-year transferees get holds only for their own dues.

## 4. How It Should Work

1. IT Admin (or Registrar, per current permissions) sets the school year period: July to March; the year label shows everywhere the old label showed.
2. After each exam week, teachers have 1 week to post grades; the grade list shows "Closes <date>"; after close, changes need a Grade Edit Request.
3. Registrar announces the new school year → holds appear on returnee dashboards as "Settle <what> with <office> to re-enroll" (never a peso amount where the rule says status-only — wording finalized with #4's "status not balance" rule).
4. Family clears dues → hold lifts automatically on confirmation → re-enrollment proceeds through child-1/child-2 gates.
5. Clearance status appears nowhere: student dashboard, lists, reports, emails.

## 5. Look & Feel (UX)

- Where it lives: existing dashboard banners + existing hold displays + year setting — no new menu. One primary action per step: Registrar `Announce New School Year`, family `View What to Settle`.
- Key states: no-hold ("You're clear for <year>"); hold ("Settle <what> with <office>"); grading-open ("Closes <date>"); grading-closed ("Closed — request an edit"); year-locked (prior year read-only).
- Plain labels: Account Hold, What to Settle, Grading Closes <date>, Clear for <year>. No jargon, no amounts where status-only applies.

## 6. Business Rules

### Must always be true

- School year period is July to March for all levels.
- Grading closes exactly 1 week after each examination week, per period.
- Account holds show if and only if a new school year is announced AND the returnee has dues; new applicants never see holds.
- Every hold names what and with which office; lifting is automatic on confirmed clearance.
- No screen, report, or email shows clearance status.

### Must never happen

- A hold before the new school year is announced.
- A hold on a fully-settled returnee or a first-time applicant.
- Grading edits after close without a Grade Edit Request.
- Clearance status appearing anywhere, including exports.
- Two conflicting year periods active at once.

### Edge cases and what happens then

- Announced year with a family mid-child-2-flow → their in-flight application continues under the new year label; dues computed for the new year.
- Exam rescheduled → close date moves with it (1 week after the actual last exam day), teachers notified.
- Hold raised by mistake → the owning office clears the due; the hold lifts with history kept ("raised/lifted in error" noted).
- Graduate of final level → no re-enrollment hold ever; dashboard shows completion instead.
- Mid-year transferee → hold covers only their own dues, pro-rated per #4 rules.

## 7. Out of Scope

- Fee amounts, installments, discounts (all in #4).
- The grade-edit chain itself (`grade-edit-approval-chain.md`, Approved).
- New hold types beyond dues-driven re-enrollment holds.
- Auto-announcement scheduling (manual announce in v1).

## 8. Success Checks

- [ ] Year label reads July–March everywhere the old label showed.
- [ ] After exams, grade list shows "Closes <date>" = 7 days after the last exam day; after close, edits need a Grade Edit Request.
- [ ] Before announcement: returnee with dues sees no hold. After announcement: the hold appears naming what and which office.
- [ ] Settled returnee and first-time applicant never see a hold.
- [ ] Clearance status appears nowhere (dashboard, lists, reports, emails, exports).
- [ ] Cleared due → hold lifts without staff hand-editing the hold.

## 9. Open Questions (if any)

None — July–March, 1-week close, announce-gated holds, clearance removal all confirmed 2026-10-11.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: year setting (admin/school-year), teacher grade lists (close-date banner), student dashboard hold banner, re-enrollment checklist wording, every view/report/email that prints clearance status (remove).
- Likely areas of the codebase (from read-only inspection):
  - Year: school-year setting + `active_school_year()` defaulting + `school_year_locked()` semantics — set July–March period; single active year; locks keep prior year read-only.
  - Close: per-period exam-end date → close = +7 days; grade/assessment writes after close blocked with "request a correction unlock" path (reuse `GradeUnlockController` chain); teacher banner shows the date.
  - Holds: `HoldService` + dashboard hold display — gate visibility on announced-new-year AND dues-present; auto-lift on confirmed clearance with history; never hand-editable.
  - Clearance removal: strip clearance-status reads/writes from views, exports, and emails; holds are the only signal; reports split per `role-reports-hub.md` conventions.
- Data/records touched: year settings, exam/close dates, hold raise/lift history; clearance-status values retired (read-paths removed, stored history untouched).
- Roles/permissions involved: registrar (announce), teachers (post before close), offices (dues truth), IT admin (settings + removal); no permission-model change. Depends on child 2 for gate wording.
- Peak-load note: announcement day is the spike — holds computed in as few passes as possible, banner reads cached, dues writes never block hold reads.

## 11. Approval

> Approved by user on 2026-10-11.
