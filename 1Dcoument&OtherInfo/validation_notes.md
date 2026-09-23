# Validation Notes - Agnus Dei School ERP

**Source:** Progress Report: Validation - Notes
**Generated:** 2026-09-21
**Status:** Pre-implementation documentation

---

## Legend

| Symbol | Meaning |
|--------|---------|
| DONE | Fully implemented |
| PARTIAL | Exists but needs enhancement |
| NOT-DONE | Requires implementation |

---

## I. General System Improvements

### I-1. More Data for Final Presentation/Defense

**Status:** PARTIAL | **Priority:** HIGH | **Existing MDs:** workflow_priority_plan.md, polish_suggestions.md

**Current State:**
- Seeders exist in database/seeders/
- StudentsAndFeesSeeder.php: 13 students (Kinder-Grade 12)
- GradesAssessmentsSeeder.php: Assessments + grades for 3 grading periods
- LibraryAndClinicSeeder.php: 20 books, 15 transactions, 10 clinic logs
- FeeSchedulesSeeder.php: Fee schedules for all grade levels x 3 terms

**What Needs to Be Done:**
- Increase student count to 30-50+ with diverse profiles
- Add students with: zero balances, partial payments, full payments, overdue balances, discounts (ESC/honor/sibling), scholarships
- Add multiple library transactions per student (borrows, returns, overdue, damaged/lost)
- Add multiple nurse visits per student (different complaints, treatments)
- Add withdrawal students, graduated students, transferred students
- Add students across all grade levels with realistic enrollment patterns

**Files to Modify:** StudentsAndFeesSeeder.php, GradesAssessmentsSeeder.php, LibraryAndClinicSeeder.php

---

### I-2. More Sample Accounts

**Status:** PARTIAL | **Priority:** HIGH

**Current State:**
- SystemRolesAndStaffSeeder.php: 8 staff users + 20 teachers
- Only 13 student accounts

**What Needs to Be Done:**
- Add more student accounts (30-50+)
- Add variety: active, inactive, archived, withdrawn students
- Add students with different grade levels, strands, and sections

**Files to Modify:** StudentsAndFeesSeeder.php, SystemRolesAndStaffSeeder.php

---

### I-3. More Account-Based Data and Transactions

**Status:** PARTIAL | **Priority:** HIGH

**Current State:**
- Each student has basic enrollment + ledger + payments
- Library transactions: 15 total; Clinic logs: 10 total

**What Needs to Be Done:**
- Each student should have multiple transactions:
  - 3+ payment records across different terms
  - 2+ library borrows (at least 1 overdue, 1 on time)
  - 1+ nurse visits with varying severity
  - Grade records across all 3 terms
  - Assessment records across all types
- Edge cases: failing grades, perfect grades, missing grades, overdue books, outstanding balance, full payment, multiple discounts

**Files to Modify:** StudentsAndFeesSeeder.php, GradesAssessmentsSeeder.php, LibraryAndClinicSeeder.php

---

### I-4. General UI Design Enhancement

**Status:** PARTIAL | **Priority:** MEDIUM | **Existing MDs:** polish_suggestions.md

**Current State:** Tailwind CSS + Alpine.js, CSS variables, ajaxTable with skeleton loading, dark mode toggle

**What Needs to Be Done (from polish_suggestions.md):**
- Add favicon + proper page titles
- Add breadcrumb navigation consistently
- Improve empty state messages
- Standardize date formats across all views
- Consistent button styles, table zebra striping, hover states
- Responsive design improvements for mobile

**Files to Review:** portal/layouts/app.blade.php, all dashboard.blade.php, all partials/*.blade.php

---

### I-5. Dashboard Filters

**Status:** DONE | **Priority:** HIGH

All 9 dashboards use ajaxTable with search/filter. No action needed.

---

### I-6. Batch Updates (Checkboxes)

**Status:** NOT-DONE | **Priority:** HIGH | **Existing MDs:** promotion_workflow_proposal.md

**Current State:**
- No checkbox-based batch operations in any module
- Promotions processed individually
- Library returns processed one at a time

**What Needs to Be Done:**
- Add checkbox column to all relevant AJAX tables
- Implement Select All checkbox in table headers
- Add batch action buttons
- Required in: Library (batch return), Promotions (batch promote/retain/graduate), Admin (batch activate/deactivate), Registrar (batch admission approval)

**Files to Create/Modify:**
- librarian/loans.blade.php
- admin/promotion/index.blade.php
- admin/pending-accounts.blade.php
- LibrarianController.php (add batchReturn)
- PromotionController.php (add batch methods)
- AdminController.php (add batch methods)

---

## II. Admin / System Configuration

### II-1. School Year Configuration

**Status:** PARTIAL | **Priority:** HIGH | **Existing MDs:** admin_settings_proposal.md

**Current State:**
- DirectressController allows adding school years and setting active year
- Admin settings page has: school_name, school_address, directress_name, principal_name, passing_grade, library fees
- No locking mechanism

**What Needs to Be Done:**
- Admin must set prerequisites before school year starts:
  - School year (2026-2027) - DONE
  - School-year duration (start/end dates) - NOT-DONE
  - Grade computation (DepEd weights) - PARTIAL hardcoded
  - Other academic settings (grading periods, terms) - NOT-DONE
- Locking mechanism: Once set, configurations no longer editable
  - Add is_locked or status field
  - Prevent editing of locked settings
  - Show lock icon/status in UI

**Files to Modify:** DirectressController.php, school-years.blade.php, AdminController.php, settings.blade.php, Setting.php

---

### II-2. Audit Logs Enhancement

**Status:** DONE | **Priority:** HIGH | **Existing MDs:** workflow_priority_plan.md (H4)

Full audit logs page in admin with filters. log_activity() used throughout. No action needed.

---

## III. Teacher / Grading

### III-1. Improve Teacher Grade Encoding

**Status:** PARTIAL | **Priority:** HIGH | **Existing MDs:** workflow_priority_plan.md (Section 9)

**Current State:**
- Two separate workflows:
  1. Final Grade Entry (grades.blade.php): single final_grade per student per term
  2. Assessment Entry (assessments.blade.php): assessment scores per student per type
- computedGrades calculates weighted average but teacher must manually enter final_grade
- Weights hardcoded: Written Work 20%, Quiz 20%, Seatwork 20%, Exam 40%

**What Needs to Be Done:**
- Connect assessment scores to final grade computation
- Auto-calculate final grade from weighted assessment averages
- Allow teacher to override auto-calculated grade if needed
- Show both auto-calculated and manually-entered grades
- Pre-fill final grade field with computed value

**Files to Modify:** TeacherController.php, grades.blade.php, computed-grades.blade.php

---

### III-2. Table-Based Grade Encoding

**Status:** NOT-DONE | **Priority:** HIGH

**Current State:**
- Assessment entry: Tabbed interface per type, per student
- Flow: Select class > Select student > Enter scores per type
- Not a consolidated spreadsheet view

**What Needs to Be Done:**
- Create single table view: Students in rows x Assessment types in columns
- Inline editing (click cell to edit)
- Auto-save on blur or save button
- Show running computed grade as scores are entered
- Support multiple assessment items per type (WW1, WW2, WW3, etc.)

**Files to Create/Modify:**
- teacher/grade-assessment.blade.php (major redesign)
- TeacherController.php (new method for table-based saving)
- teacher/partials/grade-table-results.blade.php (new partial)

---

## IV. Student / Report Card

### IV-1. Use Actual Agnus Dei Documents

**Status:** PARTIAL | **Priority:** HIGH | **Existing MDs:** capstone_master_document.md (Section IX)

**Current State:** CoR EXISTS, Report Card EXISTS (DomPDF), Official Receipt EXISTS

**What Needs to Be Done:**
- Obtain actual Agnus Dei document templates/formats
- Redesign report card and COR layouts to match actual format
- Ensure school branding, logos, and formatting match actual documents

**Files to Modify:** report-cards/print.blade.php, cor.blade.php, receipt-print.blade.php

---

### IV-2. Report Card Enhancement - Add Adviser

**Status:** NOT-DONE | **Priority:** HIGH

**Current State:**
- adviser_id column exists on sections table
- Section model has adviser() relationship
- Report card print template does NOT display adviser name

**What Needs to Be Done:**
- Add Adviser: [Name] field to report card print template
- Pull adviser name from section relationship
- Position: Below student info or in signature area

**Files to Modify:** report-cards/print.blade.php, ReportCardController.php

---

### IV-3. Report Card Enhancement - Add Teachers

**Status:** NOT-DONE | **Priority:** HIGH

**Current State:**
- Classes have teacher assignments (classes table with teacher_id)
- Report card does NOT show subject teachers

**What Needs to Be Done:**
- Add Teacher: [Name] column per subject on report card
- Format: Subject | Teacher | 1st Term | 2nd Term | 3rd Term | Final | Remarks

**Files to Modify:** report-cards/print.blade.php, ReportCardController.php

---

### IV-4. Make Teacher/Adviser Info Viewable

**Status:** NOT-DONE | **Priority:** HIGH

**Current State:** Student report card view exists but no teacher/adviser information displayed

**What Needs to Be Done:**
- Display adviser name on student report card view
- Display subject teachers on student report card view
- Make information easily visible

**Files to Modify:** student/report-card.blade.php, report-cards/show.blade.php

---

## V. Library

### V-1. Borrowing Limit - Make Visible

**Status:** NOT-DONE | **Priority:** HIGH

**Current State:**
- LibrarianController::storeBorrow processes borrows
- No maximum books per student setting enforced
- No visible limit indicator in borrow form or student portal

**What Needs to Be Done:**
- Add max_books_per_student setting to admin settings (default: 3-5)
- Display borrowing limit prominently in borrow form and student portal
- Prevent borrowing when limit reached
- Show current borrow count vs. limit

**Files to Modify:** LibrarianController.php, borrow.blade.php, AdminController.php, settings.blade.php, StudentController.php

---

### V-2. Urgent Notices in Borrowing/Returns

**Status:** PARTIAL | **Priority:** HIGH

**Current State:**
- Overdue tracking exists (late fees calculated per day)
- Dashboard shows overdue count as stat card
- Loans page has filter for status

**What Needs to Be Done:**
- Add prominent warning banner at top of Borrowing/Returns tab when overdue books exist
- Show overdue books in red/highlighted rows
- Add Urgent badge/label for overdue items
- Sort overdue items to top of list by default
- Add notification count badge on sidebar Library link

**Files to Modify:** librarian/loans.blade.php, sidebar-librarian.blade.php, LibrarianController.php

---

### V-3. Book Replacement

**Status:** NOT-DONE | **Priority:** MEDIUM

**Current State:**
- When a book is damaged/lost, librarian charges fees
- No replace book action that creates a new book record
- No linking between original and replacement books

**What Needs to Be Done:**
- Add Replace Book option in library management
- Workflow: Original book marked Damaged/Lost > New book created > Linked to original > Transaction updated > Quantity adjusted
- Add replacement_of column to books table
- Add replacement history view

**Files to Create/Modify:** LibrarianController.php, books.blade.php, new migration for replacement_of column, Book.php

---

### V-4. Batch Return Processing

**Status:** NOT-DONE | **Priority:** MEDIUM

**Current State:**
- Returns processed one at a time via return-form.blade.php
- No checkboxes for selecting multiple loans

**What Needs to Be Done:**
- Add checkbox column to loans table
- Add Select All checkbox in header
- Add Return Selected batch action button
- Process multiple returns in single action

**Files to Create/Modify:** librarian/loans.blade.php, LibrarianController.php (add batchReturn), loans-results.blade.php

---

## VI. Cashier / Finance

### VI-1. Breakdown of Expenses UI

**Status:** PARTIAL | **Priority:** MEDIUM

**Current State:**
- student-financial.blade.php shows ledger with fee breakdown
- collections-report.blade.php has date-range filter and summary stats

**What Needs to Be Done:**
- Create dedicated Breakdown of Expenses section
- Show per-student itemized expenses: tuition per term, miscellaneous items, discounts, amount paid, outstanding balance
- Visual breakdown (pie chart or bar graph if possible)

**Files to Create/Modify:** cashier/student-financial.blade.php, CashierController.php

---

### VI-2. Refund / Withdrawal

**Status:** PARTIAL | **Priority:** HIGH | **Existing MDs:** workflow_priority_plan.md (Section 13)

**Current State:**
- WithdrawalController handles withdrawal approval/rejection
- Approval sets enrollment status to Withdrawn
- No financial refund processing

**What Needs to Be Done:**
- Calculate refund amount when withdrawal is approved (based on amount paid, refund policy percentage, outstanding balance)
- Create refund transaction in student ledger
- Update payment records accordingly
- Notify cashier of pending refund
- Add refund receipt generation
- Cashier and Registrar should both have refund/withdrawal option

**Files to Create/Modify:** WithdrawalController.php, CashierController.php, cashier/payment.blade.php, registrar/withdrawals-index.blade.php, new migration for refund columns

---

### VI-3. Daily Collections

**Status:** DONE | **Priority:** HIGH

Cashier dashboard shows today's collection total and receipt count. No action needed.

---

### VI-4. Monthly Collection Breakdown

**Status:** PARTIAL | **Priority:** MEDIUM

**Current State:**
- collections-report.blade.php has date-range filter and summary stats
- CSV export available

**What Needs to Be Done:**
- Add dedicated monthly breakdown view
- Show daily collection totals within selected month
- Compare against daily collection goals (if set)
- Allow filtering by payment type

**Files to Create/Modify:** cashier/collections-report.blade.php, CashierController.php

---

### VI-5. Payment Notifications

**Status:** PARTIAL | **Priority:** MEDIUM

**Current State:** PaymentReminderMail, GradesSubmittedMail, AdmissionCredentialsMail exist

**What Needs to Be Done:**
- Add real-time payment notification to cashier upon processing
- Add payment confirmation notification to student after payment
- Email notification (new mailable needed)

**Files to Create/Modify:** new PaymentConfirmationMail.php, new email template, CashierController.php

---

### VI-6. Complete Payment History

**Status:** PARTIAL | **Priority:** HIGH

**Current State:**
- student-financial.blade.php shows current school year ledger
- Payments scoped to active school year

**What Needs to Be Done:**
- Show payment history across all school years
- When searching for student, display complete payment history
- Include: date, amount, receipt number, payment type, school year
- Allow filtering by school year

**Files to Create/Modify:** cashier/student-financial.blade.php, CashierController.php

---

## VII. Head Directress

### VII-1. Library Reports Access

**Status:** NOT-DONE | **Priority:** HIGH

**Current State:**
- Directress portal has: dashboard, demographics, fees, graduation fees, school years
- No library module access

**What Needs to Be Done:**
- Add Library Reports page to Directress portal
- Show: total books, borrowed, overdue, popular books, borrowing trends
- Allow date-range filtering and export capability

**Files to Create/Modify:** DirectressController.php (add libraryReports method), new library-reports.blade.php, new partial, sidebar-directress.blade.php, web.php (add route)

---

### VII-2. Cashier Reports Access

**Status:** NOT-DONE | **Priority:** HIGH

**Current State:**
- Directress portal has no cashier/financial reports access

**What Needs to Be Done:**
- Add Cashier Reports page to Directress portal
- Show: total collections, daily/monthly breakdown, outstanding balances, payment trends
- Allow date-range filtering and export capability

**Files to Create/Modify:** DirectressController.php (add cashierReports method), new cashier-reports.blade.php, new partial, sidebar-directress.blade.php, web.php (add route)

---

## VIII. School Nurse / Health Records

### VIII-1. Diagnosis Terminology

**Status:** NOT-DONE | **Priority:** LOW

**Current State:** create-log.blade.php has Diagnosis field label; ClinicLog model has diagnosis column

**What Needs to Be Done:**
- Replace Diagnosis with Sickness/Injury in UI labels
- Keep database column as diagnosis (no schema change needed)
- Update all references in nurse views

**Files to Modify:** nurse/create-log.blade.php, nurse/logs.blade.php, nurse/partials/logs-results.blade.php

---

### VIII-2. Patient Filters

**Status:** PARTIAL | **Priority:** MEDIUM

**Current State:** Logs page has: search by student name, incident type filter, date range filter

**What Needs to Be Done:**
- Add filter by Sickness/Injury type (dropdown from existing data)
- Add filter by Month (January-December)
- Add filter by Grade Level (Kinder-Grade 12)
- Make filters collapsible

**Files to Modify:** nurse/logs.blade.php, NurseController.php, nurse/partials/logs-results.blade.php

---

### VIII-3. Treatment Notes

**Status:** PARTIAL | **Priority:** MEDIUM

**Current State:**
- treatment and notes fields exist in ClinicLog model
- Create form has: Complaint, Diagnosis, Treatment, Notes, Referral fields

**What Needs to Be Done:**
- Verify treatment notes is separate from treatment
- If not, add dedicated Treatment Notes textarea field
- Ensure treatment notes are visible in logs list and detail view

**Files to Review:** ClinicLog.php, nurse/create-log.blade.php, nurse/logs.blade.php

---

## IX. Registrar

### IX-1. Refund / Withdrawal

**Status:** PARTIAL | **Priority:** HIGH | **See also:** VI-2 (Cashier Refund/Withdrawal)

**Current State:**
- WithdrawalController handles withdrawal approval/rejection
- No financial refund processing

**What Needs to Be Done:**
- Add refund/withdrawal option to Registrar portal
- Calculate refund amount when withdrawal is approved
- Create refund transaction in student ledger
- Notify cashier of pending refund
- Both Registrar and Cashier should have this capability

**Files to Create/Modify:** WithdrawalController.php, registrar/withdrawals-index.blade.php, CashierController.php, cashier/payment.blade.php

---

## Summary Scorecard

| Section | Total | DONE | PARTIAL | NOT-DONE |
|---------|-------|------|---------|----------|
| I. General System | 6 | 1 | 4 | 1 |
| II. Admin / Config | 2 | 1 | 1 | 0 |
| III. Teacher / Grading | 2 | 0 | 1 | 1 |
| IV. Report Card | 4 | 0 | 1 | 3 |
| V. Library | 4 | 0 | 1 | 3 |
| VI. Cashier / Finance | 6 | 1 | 4 | 1 |
| VII. Head Directress | 2 | 0 | 0 | 2 |
| VIII. Nurse | 3 | 0 | 2 | 1 |
| IX. Registrar | 1 | 0 | 1 | 0 |
| **TOTAL** | **30** | **3** | **15** | **12** |

---

## Cross-Reference with Existing MD Documents

| MD Document | Relevance |
|-------------|-----------|
| workflow_priority_plan.md | Overall bug/feature status tracking, completed fixes, MEDIUM/LOW items |
| feature_enhancements.md | 13 library/cashier enhancements implemented |
| polish_suggestions.md | UI quick wins and small features |
| admin_settings_proposal.md | School year config, passing_grade, library fees, enrollment toggle |
| promotion_workflow_proposal.md | Batch promotion actions, status lifecycle |
| capstone_master_document.md | Architectural blueprint, DepEd weights, document layouts, module specs |
| bug_report.md | 8 bugs found and fixed (Aug 2026) |
| change_requests.md | 33 change requests - all marked Done |
| STUDENT_ADMISSION_BUGS.md | 6 admission bugs documented |
| PERFORMANCE_OPTIMIZATION.md | 56 performance issues fixed |

---

## Priority Implementation Order (Recommended)

### Phase 1 - Quick Wins (< 1 day each)
1. VIII-1: Diagnosis terminology change (label swap)
2. IV-2: Add adviser name to report card
3. IV-3: Add teacher names to report card
4. IV-4: Make teacher/adviser info viewable on student report card
5. V-2: Urgent notices banner in library

### Phase 2 - Small Features (1-2 days each)
6. V-1: Borrowing limit visibility
7. VI-6: Complete payment history (cross-year)
8. II-1: School year locking mechanism
9. VIII-2: Patient filters (month, grade level, sickness)

### Phase 3 - Medium Features (2-3 days each)
10. III-1: Connect assessments to final grade computation
11. VI-2/IX-1: Refund/Withdrawal financial processing
12. VII-1: Directress library reports
13. VII-2: Directress cashier reports
14. VI-4: Monthly collection breakdown
15. VI-5: Payment notifications

### Phase 4 - Large Features (3-5 days each)
16. III-2: Table-based grade encoding (spreadsheet view)
17. I-6: Batch updates (checkboxes across modules)
18. V-3: Book replacement workflow
19. V-4: Batch return processing

### Phase 5 - Data and Polish (Ongoing)
20. I-1/I-2/I-3: Expanded seed data for defense
21. I-4: UI design enhancements
22. IV-1: Match actual Agnus Dei document formats
23. VI-1: Breakdown of expenses UI
24. VI-4: Monthly collection breakdown
