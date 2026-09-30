# Role & Process Reform Plan — Separation of Duties — 2026-09-30

**Status:** PHASES 1 + 2 IMPLEMENTED LIVE 2026-09-30 (commit pending push — held per request). Phases 3–4 not started.

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
- **Phase 3 — holds engine:** library/clinic/finance holds; student-facing hold reasons; block report card, re-enrollment, promotion.
- **Phase 4 — new features & visibility:** attendance, receipt history, discount breakdown, bulk fee assignment, void/refund payouts, aggregate-only clinic reports for Directress.

## Verification (2026-09-30, live Supabase)

- 21/21 page/permission checks (incl. negatives: admin promotion/subjects/pending-accounts → 404, cashier doc view → 403)
- 16/16 write-flow checks with full revert (discount request→approve→apply→restore; unlock request→approve→restore; propose→approve→delete; sign-off execution inside rolled-back txn; ack→reset) — zero residue in all 3 new tables
- Smoke harness updated for the new route map and re-run: **231/231 PASS** (219 original + 12 new/changed), cleanup verified
- Note: live user/student counts grew +12 during this window from manual browser testing (injection/XSS probe accounts via public inquiry) — unrelated to this change; left untouched
- Deferred: full subject-change approval workflow (Registrar owns CRUD now; Principal has read-only oversight — approval step to be designed with Phase 3/4)

## Acceptance checklist

- [ ] Role-by-role changes reviewed and approved
- [x] Phase 1 permission removals executed + smoke-tested (231-test harness, incl. 404/403 negatives)
- [x] Phase 2 approval workflows live (discount, promotion, grade unlock, announcements)
- [ ] Phase 3 holds engine live (all three hold sources block + student sees reasons)
- [ ] Phase 4 features live (attendance, receipts, breakdowns, bulk assign, refunds, private clinic reports)
- [x] No role can complete a full money loop alone (grant + collect) — verified by permission audit (21 checks: directress cannot mark paid, cashier cannot grant solo, admin has no money routes)
- [ ] Docs updated (CHANGELOG + sessions log), committed, pushed
