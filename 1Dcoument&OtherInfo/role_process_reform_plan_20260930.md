# Role & Process Reform Plan — Separation of Duties — 2026-09-30

**Status:** ALL PHASES IMPLEMENTED LIVE AND PUSHED 2026-10-01 (commits `2a0911a` phases 1+2, `394f40f` phases 3+4, `0e85c7c` follow-ups).

## Guiding principle

**Directress decides · Registrar records · Cashier collects · Principal leads academics · IT supports.**

No single role should both grant a benefit and execute it (discount + collect, price + paid-mark), and no role should decide outside its domain (IT deciding pass/fail, Principal acting alone on school-wide comms).

---

## 1. Admin (IT Support)

- **Today:** IT confirms payments, clears students, runs promotion to next grade, manages subjects.
- **Problem:** IT touching money and pass/fail decisions creates risk and blame confusion when something goes wrong.
- **Change:** IT only creates accounts, resets passwords, keeps the system running, and keeps audit logs. All approvals move to the offices below.
- **System impact:** strip payment-confirm, clearance, promotion-run, and subject-decision powers from the admin role; keep user management + audit-log visibility.

## 2. Registrar (Records Owner)

- **Today:** admissions, requirements checks, new-student approvals, sections, withdrawals, report cards. Good fit — keep.
- **Changes:**
  - Own **subjects + sections together**, with Principal approving (currently split — consolidate).
  - **Initiate promotion** (who passed / who repeats); Principal approves; IT fully out of this flow.
  - New process: **grade re-open** for teacher errors — request → unlock → correct → re-lock. Currently no process exists.
- **System impact:** promotion initiation moves from `/admin/promotion` to Registrar with Principal approval step; new grade-unlock request flow.

## 3. Cashier (Money Handler)

- **Today:** collects payments, prints receipts, collections reports; can change discounts alone; can view admission documents.
- **Problem:** one person grants the discount and collects the money alone.
- **Changes:**
  - **Only Cashier marks anything Paid** (remove paid-marking from Directress).
  - **Discounts become 2 steps:** Cashier or Registrar requests with proof (ESC, Honor, Sibling) → Directress approves → Cashier applies.
  - Remove admission-document viewing from Cashier (not needed for collection).
  - Add a **void/refund payout step** so withdrawals settle with a real payout, not a paper adjustment.
- **System impact:** discount request/approval workflow; paid-marking permission restricted to cashier role; refund transaction type + receipt.

## 4. Teacher

- **Today:** classes, quizzes/exams, grade encoding, grade submission. Correct — keep.
- **Missing:**
  - **Attendance tracking** (new feature).
  - **Grade correction after submission:** Teacher requests correction → Principal/Registrar unlocks → Teacher corrects → re-lock.
- **System impact:** new attendance module; grade statuses gain Submitted → Locked, with unlock-request flow.

## 5. Librarian

- **Today:** books, borrowing, returns, visits. Reporting only, no blocking — keep the core.
- **Change:** **unreturned books create a Hold** that blocks clearance and re-enrollment until returned or paid. Student sees the hold.
- **System impact:** holds engine (first consumer: library overdue loans); hold visibility on student dashboard.

## 6. Nurse (Clinic)

- **Today:** records clinic visits; details flow to Directress reports.
- **Changes:**
  - **Open clinic cases** (e.g., pending referral) also create a **Hold for clearance**.
  - **Directress sees totals and trends only**, not full diagnosis details — student privacy.
- **System impact:** clinic-case status (open/closed) feeds holds engine; Directress clinic report limited to aggregates.

## 7. Student / Parent

- **Today:** online application, requirement uploads, re-enrollment, withdrawal requests, grades/schedule/balance views. Good self-service — keep.
- **Missing visibility — students need to see:**
  1. Receipts,
  2. Why they are blocked (finance / library / clinic hold),
  3. Discount breakdown (currently they see a balance but not the reason).
- **System impact:** receipt history page, hold banner with reasons, per-line discount display on ledger.

## 8. Directress (Top Approver)

- **Today:** sets fees, assigns graduation fees per student, marks students as paid, opens/locks school years, views all reports.
- **Problem:** setting the price and marking as paid must not be the same person.
- **Changes:**
  - Sets prices and approves adjustments, but **never marks Paid** — Cashier only.
  - **Stop one-by-one fee assignment** — Registrar/Cashier assign in bulk from enrollment.
  - Keep school-year lock and overall reports (correct as-is).
- **System impact:** remove paid-marking from directress role/permissions; bulk fee-assignment from enrollment; approval inbox for discounts (and promotion sign-off if needed).

## 9. Principal (Academic Head)

- **Today:** class schedules, grade viewing, announcements. Correct — keep.
- **Changes:**
  - **Approve promotion:** Registrar prepares list → Principal approves → Directress signs off if needed.
  - **Approve schedule changes and grade corrections; own teacher-to-class assignments** (currently owned by no one).
  - **Whole-school announcements need Directress awareness**, not Principal acting alone.
- **System impact:** promotion approval step; assignment ownership; announcement workflow with Directress visibility/awareness.

---

## End-to-end improved flow

1. Inquiry → Apply → **Registrar verifies → Approve**
2. Registrar/Cashier **assign fees in bulk** from enrollment → Cashier collects (**only Cashier marks Paid**)
3. Discounts need **approval**, not direct edit
4. Teacher teaches → grades → submits → **Principal locks**
5. **Library / Clinic / Finance holds auto-block** report card, re-enrollment, and promotion until cleared
6. **Promotion:** Registrar initiates → Principal approves → system moves students

## Separation-of-duties matrix

| Role | Decides | Executes / Records | Must NOT |
|------|---------|-------------------|----------|
| Admin (IT) | nothing operational | accounts, password resets, uptime, audit logs | touch payments, run promotion, decide pass/fail |
| Registrar | admissions outcomes, promotion list (draft) | admissions, requirements, subjects+sections (draft), report cards, withdrawals | approve discounts, mark paid |
| Cashier | nothing (executes approvals) | collect, receipts, apply approved discounts, voids/refunds | grant discounts solo, view admission docs |
| Teacher | subject grades | teach, encode, attendance | edit grades after lock without approval |
| Librarian | — | books, loans, visits; raise overdue holds | clear blocks outside library rules |
| Nurse | — | visit records; raise open-case holds | expose diagnosis details in reports |
| Directress | fees, discounts, adjustments, sign-offs | school-year lock, overall reports | mark Paid, assign fees one-by-one |
| Principal | promotion, schedules, corrections, announcements (Directress-aware) | teacher-to-class assignments | act alone on school-wide announcements |
| Student/Parent | requests | self-service (apply, re-enroll, view) | — |

## Proposed implementation phases

- **Phase 1 — permission removals (quick wins) — DONE 2026-09-30:** Cashier admission-doc view off (route `role:2,3`→`role:2` + `viewRequirement` role check); Directress paid-marking off (toggle moved to Cashier financial page, Directress sees badge only); Admin payment/promotion/subject powers off (confirm routes+methods+views deleted, dashboard Verification panel → Students/SY stats, tutorial modal link fixed); subjects moved to Registrar (`registrar/subjects`, views moved to `portal/registrar/`), Principal gets read-only browser.
- **Phase 2 — approval workflows — DONE 2026-09-30:** 2-step discounts (`discount_requests`: Cashier/Registrar request w/ proof → Directress approves → Cashier applies; direct edit route deleted); promotion handoff (`promotion_proposals`: Registrar proposes → Principal approves → Directress signs off + executes via new `PromotionService`; old `Admin\PromotionController` deleted); grade unlock (`grade_unlock_requests`: Teacher requests → Principal/Registrar approves → Submitted→Pending → correct → re-submit); announcements (`directress_seen_at`: Directress acknowledges, Principal edits reset it, Principal list shows awareness badge).
- **Phase 3 — holds engine — DONE 2026-10-01:** computed `HoldService` (library = overdue unreturned loans via existing `isOverdue()`; clinic = open cases via new `clinic_logs.is_open/closed_at`, nurse marks open at log creation and closes from the logs list; finance = ledger balance > 0); student sees reasons in a dashboard banner; blocks report card (student 302 + reasons), re-enrollment request + registrar approval, and promotion sign-off (registrar propose page shows HOLD badges, batched in 3 queries).
- **Phase 4 — new features & visibility — DONE 2026-10-01:** attendance (`attendances` table, per-class date marking UI for teachers); receipts (type badges Payment/Refund/Void + AR numbers on student ledger) and discount breakdown (approval date/approver/proof shown); bulk fee assignment (`FeeAssignmentController` for Registrar — missing tuition ledgers + per-fee grad-fee bulk; Directress one-by-one assign removed, list kept); withdrawal split (Registrar approves → computes refund due, moves no money; Cashier releases payout on new Refunds page with `refund_released_by`) + payment void (offsetting VOID- reversal, original kept); clinic reports aggregate-only (diagnosis panel + per-student rows + detail export columns removed, open-cases count added).

## Verification (2026-09-30 → 2026-10-01, live Supabase)

Phases 1+2: 21/21 page/permission checks (incl. 404/403 negatives), 16/16 write-flow checks with full revert (zero residue), harness 231/231.

Phases 3+4:
- Holds: 14/14 (service per-source computation, report-card 302 + reasons, re-enrollment blocked with no admission created, signoff refused with nothing executed, nurse open→hold→close→lifted cycle with student-side visibility, loan aging cycle with restore)
- Phase 4: 21/21 (all new pages load, attendance save+delete, bulk endpoints run with zero missing ledgers left, withdrawal approve-without-money → cashier release → full revert, void → revert, clinic aggregates + export, one-by-one assign → 404)
- Bugs found by verification and fixed: Withdrawal missing `refund_processed_at` datetime cast (500'd the Refunds page); student dashboard had no error-flash block (block reasons invisible on landing — added)
- Smoke harness updated for the new map (assign removal, fee-assignment/refunds/attendance/grade-unlock tests, holds-aware report-card test) → **235/235 PASS**, cleanup verified
- Live demo note: the fixed harness student (juan.delacruz) carries a real overdue library hold, so his report card is blocked by design — the harness asserts the block + reasons

- Live demo note: live user/student counts grew +12 during the Phase 1+2 window from manual browser testing (injection/XSS probe accounts via public inquiry) — unrelated, left untouched
- Deferred: full subject-change approval workflow (Registrar owns CRUD now; Principal has read-only oversight)

## Acceptance checklist

- [ ] Role-by-role changes reviewed and approved
- [x] Phase 1 permission removals executed + smoke-tested (231-test harness, incl. 404/403 negatives)
- [x] Phase 2 approval workflows live (discount, promotion, grade unlock, announcements)
- [x] Phase 3 holds engine live (all three hold sources block + student sees reasons)
- [x] Phase 4 features live (attendance, receipts, breakdowns, bulk assign, refunds, private clinic reports)
- [x] No role can complete a full money loop alone (grant + collect) — verified by permission audit (21 checks: directress cannot mark paid, cashier cannot grant solo, admin has no money routes; withdrawal payouts split registrar-approve/cashier-release)
- [x] Docs updated (CHANGELOG + sessions log), committed, pushed

## Follow-ups (2026-10-01, live Supabase)

- **Clearance auto-update (Phase 1 loose end, fixed):** nothing set `Cleared` after IT confirmation was removed, so reminders would nag settled accounts and the ledger showed a stale "IT Confirmation". New `LedgerService::refreshClearance()` derives clearance from balance (cents-rounded) after every money mutation (payment, refund release, void, discount apply, promotion fee carry, bulk assign); backfilled live (17 → Cleared, 66 → Uncleared); student ledger row relabeled to Clearance. Verified 16-check flow incl. full-payment→Cleared→void→Uncleared cycle. Bug found: float dust (1.8E-12) kept settled accounts Uncleared — fixed with cents rounding.
- **Teacher-class assignment (Principal owns it now):** verified no UI ever wrote `classes.teacher_id` (the schedule form even validated-then-dropped it). New Principal "Teachers" page (grade filter, per-row teacher select with Unassigned option, non-teacher users rejected); assignments are audit-logged and feed the existing schedule conflict detection. Verified assign → restore → reject-non-teacher.
- **Subject-change approval:** Registrar create/update/delete/import now stage `subject_change_requests` instead of touching live subjects; Principal inbox approves (applies with re-validation) or rejects; Registrar sees pending items atop the subjects index. Verified full cycle create→approve→delete→approve (zero residue) plus update→reject.
- **Infra note (not code):** public inquiry POST 500'd twice from combined slowness (Resend API SSL hang — cURL error 60, missing CA bundle on the Windows PHP — plus slow Supabase queries) exceeding the 30 s budget; synchronous `Mail::send` in the request is the exposure. Re-run passed 237/237. Proper fixes: configure `curl.cainfo` CA bundle in php.ini and/or move inquiry mail to a queue worker — left for a hosting/infra pass.
