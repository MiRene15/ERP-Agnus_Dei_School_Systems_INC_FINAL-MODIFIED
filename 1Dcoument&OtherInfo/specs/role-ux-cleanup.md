# Spec: Role UX Cleanup

- **Status**: Approved
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11

## 1. Why We Need This

Small role frictions pile up. The Directress taps announcements one by one, a library-visits feature nobody uses confuses staff, and nobody has answered what demographics the system should capture. This change clears all three: bulk acknowledge across all announcement types, full retirement of library visits, and a widened-but-lean system-wide demographics set (core student fields + trend charts, no section anywhere).

## 2. Who Is Affected

- **Directress (primary)** — acknowledges all announcement types in bulk; reads system-wide demographics.
- **Librarian** — visits tab, clock-in/out, and visits views gone; borrow/return work untouched.
- **Registrar** — captures mandatory core demographics at admission.
- **Principal** — publishes announcements as today; reads demographics charts.
- **IT Admin** — retires visits code paths; owns demographics fields. Families see nothing new except the admission fields.

## 3. Business Flow: Today vs After

- **As-is**: Principal publishes announcements; Directress acknowledges each one individually; librarian has visits clock-in/out views backed by a visits table and seeders; demographics are unanswered (gender gated at admission, nothing else enforced).
- **To-be**: Directress ticks many (or Acknowledge All with count-confirm) across all announcement types; each item keeps its own stamp → visits routes, actions, views, menu links, and seeder writes retired; existing visit rows archived (read-only, never new writes) → admission requires core demographics (level auto, gender, age band auto, area, enrollment type, scholar flag, each with escape options) → hubs show the chart set below.
- **Preserved**: announcement publishing/editing, borrow/return + fines + holds rails, admission draft/submit/upload flow, per-role report-hub pattern, permissions, audit.
- **Exceptions**: announcements published mid-batch are excluded and appear next time; archived visit rows remain queryable for audit but appear in no list, report, or export.

## 4. How It Should Work

1. Directress opens announcements → sees unacknowledged count → ticks items or Acknowledge All → confirms the count → each item stamped (who/when); re-running covers only the rest.
2. Librarian opens the sidebar → no Visits entry; any bookmarked visits URL redirects to the library dashboard with "this page was retired."
3. Family/staff files admission → core demographics dropdowns (area, enrollment type, scholar flag; level/gender/age pre-filled or auto) → submit blocked until filled, escape options always available ("prefer not to say" style).
4. Principal/Directress/Registrar open the Demographics view in their report hub (current year pre-filled) → charts: enrollment by level, by gender, by area, by enrollment type, by age band, scholar share; trends: enrollment by year, new-vs-returning share, retention/promotion/graduation rates (reading #2 outcomes). No section dimension anywhere.
5. Slices with fewer than 5 students read "fewer than 5," never a number.

## 5. Look & Feel (UX)

- Where it lives: existing announcements list (+ bulk bar), existing admission record (+ demographics block), existing report hubs (+ Demographics view). No new menus. One primary action each: `Acknowledge All`, `Submit Application`, `View Demographics`.
- What the user sees first: counts (unacknowledged n; enrollment total), then items/charts.
- Key states: empty ("No announcements" / "No enrollments this year yet"); all-done ("You're caught up"); retired-link redirect; chart-tiny-slice ("fewer than 5"); denied (visits actions gone entirely, not permission-gated).
- Plain labels: Acknowledge All, Acknowledged <date>, Area, Enrollment Type, Scholar, Prefer not to say, You're caught up. Same light/dark look; narrow screens stack charts singly.

## 6. Business Rules

### Must always be true

- Bulk acknowledge covers all announcement types; every item stamped separately; interrupted batches resume on the remainder only.
- Visits retirement removes routes, actions, views, menu links, and seeder writes; existing rows archived read-only with no new writes from anywhere.
- Core demographics mandatory at admission with escape options; submit blocked until each has a value-or-escape.
- Charts read-only, current year pre-filled; tiny slices masked as "fewer than 5."
- No section dimension in any demographics capture or chart.

### Must never happen

- One role's acknowledge stamping another scope's items, or a single stamp standing in for many.
- A visits write succeeding after retirement (clock-in/out gone, not denied).
- A new admission without core demographics (escape counts as filled).
- Demographics blocking re-enrollment or payment flows once captured.
- Section appearing in demographics capture, charts, or exports.

### Edge cases and what happens then

- Announcement published mid-batch → excluded, surfaces next visit with the new count.
- Bookmarked visits URL / old email link → redirect to library dashboard with the retired notice; no error page.
- Old exports referencing visits → visits columns dropped in v1 formats; archived rows available only via audit read.
- Escape-option overuse → shown as its own chart slice ("not stated") so it is visible, never merged into a real value.
- Area list changes (new barangay) → new option added going forward; past records keep their recorded value.

## 7. Out of Scope

- Per-section demographics (explicitly out).
- Extended sensitive fields — religion, IP, 4Ps, special needs, distance/transport (parked follow-up with role-gating).
- Staff demographics (parked follow-up).
- Public-facing demographic dashboards; announcement read-receipts per recipient (noted for later).
- Changes to announcement publishing itself.

## 8. Success Checks

- [ ] Directress Acknowledges All across types → every item stamped, counts match, re-run finds nothing left.
- [ ] Visits tab/menu/route gone; bookmarked URL lands kindly on the library dashboard; no new visit rows creatable.
- [ ] Admission submit blocked until core demographics filled; escapes accepted; no section field anywhere.
- [ ] Hub Demographics view shows the six mix charts + three trend charts, current year pre-filled, no section.
- [ ] Tiny slices read "fewer than 5"; escape answers form their own "not stated" slice.

## 9. Open Questions (if any)

None — all-types bulk, full visits retirement with archived rows, mandatory-with-escape A-set, A+D chart slate all confirmed 2026-10-11. B+C parked.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: `directress/announcements` list (+ bulk bar), `principal/announcements*` (publishing untouched), admission apply views (demographics block), per-role report hubs (+ Demographics view), librarian sidebar (drop Visits), retired `portal/librarian/visits*` views.
- Likely areas of the codebase (from read-only inspection):
  - Bulk ack: `DirectressController@acknowledgeAnnouncement` + route `directress.announcements.acknowledge` — add batch acknowledge (all-types scope, count-confirm, per-item stamps, resume-remainder); keep single-ack behavior; reuse press/idempotency guards.
  - Visits retirement: routes `web.php:290-292` + `LibrarianController@visits/clockIn/clockOut` + views `portal/librarian/visits.blade.php` + `partials/visits-results.blade.php` + sidebar link + seeder writes (`StudentLinkedInfoSeeder`, `LibraryAndClinicSeeder`) — remove all; keep `library_visits` rows archived (no writes, audit-read only); confirm `HoldService`/reports read borrow-return transactions, not visits, before closing.
  - Demographics: admission record + `admissions`/`students` fields (area, enrollment type, scholar flag; level/gender/age-derived) with escape options and submit-blocking validation; hub chart reads grouped by those fields + yearly trends + #2 promotion outcomes; mask counts under 5; no section key anywhere.
- Data/records touched: announcement ack stamps, demographics fields, archived visit rows (read-only); no grade/fee/payment writes.
- Roles/permissions involved: directress (bulk ack + charts), principal (publish + charts), registrar (capture), librarian (visits gone); no permission-model change. Relatives: `role-reports-hub.md` + children, `directress-reports-toggle.md`, `librarian-library-reports.md` (all Implemented — hub pattern reused).
- Peak-load note: announcement + enrollment peaks — bulk ack single-confirmation with per-item history; charts grouped in few passes with short-TTL caching; capture writes never block list reads.

## 11. Approval

> Approved by user on 2026-10-11.
