# Sweep Worklist

- **Spec**: `portal-page-sweep.md` (Approved 2026-10-04)
- **Environment**: live site, read-mostly
- **Started**: ____________  **Finished**: ____________

## How to use this

Tick every box. **Fix nothing while you sweep** — record only, then triage at the end.

- [ ] means not yet opened.
- A page that fails: leave the box **unticked**, write what it said in the Findings table at the bottom, and move on.
- A page that legitimately denies access, or shows a correct empty state, **counts as a pass**. Note it if unsure.
- Open forms and **cancel** rather than saving. Where a save must be proven, use an obviously temporary record and delete it before leaving that role.
- A redirect to the login screen is **not** a pass — your session probably ended.

## 1. Registrar (17 pages)

- [ ] `registrar.admissions.index`
- [ ] `registrar.admissions.show`
- [ ] `registrar.dashboard`
- [ ] `registrar.fee-assignment.index`
- [ ] `registrar.promotion.index`
- [ ] `registrar.report-cards.index`
- [ ] `registrar.report-cards.print`
- [ ] `registrar.report-cards.show`
- [ ] `registrar.requirements.view`
- [ ] `registrar.sections.create`
- [ ] `registrar.sections.edit`
- [ ] `registrar.sections.index`
- [ ] `registrar.subjects.create`
- [ ] `registrar.subjects.edit`
- [ ] `registrar.subjects.index`
- [ ] `registrar.subjects.template`
- [ ] `registrar.withdrawals.index`

## 2. IT Admin (12 pages)

- [ ] `admin.audit-logs`
- [ ] `admin.dashboard`
- [ ] `admin.exports.collections`
- [ ] `admin.exports.enrollments`
- [ ] `admin.exports.grades`
- [ ] `admin.settings`
- [ ] `admin.student-accounts.index`
- [ ] `admin.system-health`
- [ ] `admin.system-health.show`
- [ ] `admin.users.create`
- [ ] `admin.users.edit`
- [ ] `admin.users.index`

## 3. Principal (14 pages)

- [ ] `principal.announcements`
- [ ] `principal.announcements.create`
- [ ] `principal.announcements.edit`
- [ ] `principal.dashboard`
- [ ] `principal.grades`
- [ ] `principal.promotion.index`
- [ ] `principal.schedules`
- [ ] `principal.schedules.edit`
- [ ] `principal.schedules.manage`
- [ ] `principal.schedules.template`
- [ ] `principal.subject-approvals.index`
- [ ] `principal.subjects.index`
- [ ] `principal.teacher-assignments.index`
- [ ] `profile.edit`

## 4. Directress (26 pages)

- [ ] `directress.announcements.index`
- [ ] `directress.cashier-reports`
- [ ] `directress.cashier-reports.export`
- [ ] `directress.dashboard`
- [ ] `directress.demographics`
- [ ] `directress.discount-requests.index`
- [ ] `directress.fees`
- [ ] `directress.fees.create`
- [ ] `directress.fees.edit`
- [ ] `directress.graduation-fees`
- [ ] `directress.graduation-fees.assigned`
- [ ] `directress.graduation-fees.create`
- [ ] `directress.graduation-fees.edit`
- [ ] `directress.library-reports`
- [ ] `directress.library-reports.export`
- [ ] `directress.promotion.index`
- [ ] `directress.reports`
- [ ] `directress.reports.clinic`
- [ ] `directress.reports.clinic.export`
- [ ] `directress.reports.collections`
- [ ] `directress.reports.library`
- [ ] `directress.reports.receivables`
- [ ] `directress.reports.receivables.export`
- [ ] `directress.reports.students`
- [ ] `directress.reports.students.export`
- [ ] `directress.school-years`

## 5. Cashier (14 pages)

- [ ] `cashier.collections-report`
- [ ] `cashier.collections-report.export`
- [ ] `cashier.dashboard`
- [ ] `cashier.discounts`
- [ ] `cashier.payment`
- [ ] `cashier.payments`
- [ ] `cashier.projections`
- [ ] `cashier.receipt.print`
- [ ] `cashier.refunds.index`
- [ ] `cashier.reports`
- [ ] `cashier.reports.receivables`
- [ ] `cashier.reports.receivables.export`
- [ ] `cashier.search`
- [ ] `cashier.student-financial`

## 6. Teacher (12 pages)

- [ ] `teacher.assessments`
- [ ] `teacher.attendance`
- [ ] `teacher.classes`
- [ ] `teacher.classes.show`
- [ ] `teacher.class-list`
- [ ] `teacher.class-list.students`
- [ ] `teacher.computed-grades`
- [ ] `teacher.dashboard`
- [ ] `teacher.grade-assessment`
- [ ] `teacher.grade-assessment.student`
- [ ] `teacher.grade-unlocks.index`
- [ ] `teacher.schedule`

## 7. Nurse (3 pages)

- [ ] `nurse.dashboard`
- [ ] `nurse.logs`
- [ ] `nurse.logs.create`

## 8. Librarian (13 pages)

- [ ] `librarian.books`
- [ ] `librarian.books.create`
- [ ] `librarian.books.edit`
- [ ] `librarian.books.search`
- [ ] `librarian.dashboard`
- [ ] `librarian.history`
- [ ] `librarian.inactive-logs`
- [ ] `librarian.loans`
- [ ] `librarian.loans.borrow`
- [ ] `librarian.loans.return-form`
- [ ] `librarian.loans.search`
- [ ] `librarian.students.search`
- [ ] `librarian.visits`

## 9. Student (10 pages)

- [ ] `student.admission.create`
- [ ] `student.admission.requirements.view`
- [ ] `student.admission.status`
- [ ] `student.cor`
- [ ] `student.dashboard`
- [ ] `student.enrollment.create`
- [ ] `student.ledger`
- [ ] `student.report-card`
- [ ] `student.schedule`
- [ ] `student.withdrawal.create`

## 10. Registrar + Cashier (1 page)

- [ ] `discount-requests.index`

## 11. Registrar + Principal (1 page)

- [ ] `registrar.grade-unlocks.index`

## 12. Everyone (2 pages)

- [ ] `dashboard (redirects to your role page)`
- [ ] `profile.edit`

## Findings

Every failure, with the exact words on the page. Nothing gets fixed until this table is complete.

| # | Role | Page | Exactly what it said | Verdict |
|---|------|------|---------------------|---------|
| 1 |  |  |  | needs a spec / slow / working as intended |

## Triage (fill in last)

- [ ] Every box above is either ticked or listed in Findings.
- [ ] Every finding is marked *needs a spec*, *slow but working*, or *working as intended*.
- [ ] No temporary records left behind.
- [ ] Each *needs a spec* finding has a spec written or a recorded decision not to fix.
