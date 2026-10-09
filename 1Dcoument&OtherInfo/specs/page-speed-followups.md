# Spec: Page Speed Follow-Ups (note only)

- **Status**: Draft — parking note only. NOT calibrated, NOT approved, NOT implementable. Each item needs its own `/spec` flow.
- **Created**: 2026-10-07

## Why this note exists
During the page-speed family (Children 1–3 plus fullname-lists), several ideas were deliberately parked to keep each gate provable. This file records them so none get lost. Nothing here may be built from this file — each needs its own spec, approval, and gate.

## Parked items

### 1. Dashboard and counter cache
What: cache principal/directress counts and totals with a visible "as of HH:MM" plus Refresh.
Why parked: a stale morning total is a trust problem, not a speed problem. Needs its own staleness design and trust gate.
Needs: UX design for staleness display + per-role approval.

### 2. Short-TTL result cache
What: cache search result lists for 30–60 seconds per filter.
Why parked: highest stale-money risk — a cashier collecting against a 60-second-old balance. Needs instant flush on every payment, enrollment, and approval.
Needs: flush wiring on all write paths + own proof gate. Ships last, if ever.

### 3. Per-page index review
What: add database indexes per slow search on proof.
Why parked: last protocol showed the query itself at 3–4ms — an index buys ~2ms but taxes every enrollment write at peak. Only worth it per page on measurement.
Needs: per-page 4-number run and an explicit buy-vs-tax decision.

### 4. Connection pooling run
What: persistent or pooled database connections to cut per-request setup cost.
Why parked: infrastructure layer with a different owner and blast radius from query shape. Needs its own protocol run first.
Needs: candidate-cause runs (persistent connections, pooler/transaction mode, DNS) letting numbers pick the fix.

### 5. Parent vision spec (`page-speed-combined.md`)
What: umbrella tying Children 1–3 together — vision, the page list, the shared 2-second identical-figures gate, status of each child.
Why parked: children were built first so the parent can record what actually shipped.
Needs: drafted last, after Child 3 lands.

### 6. Optimistic row skeletons per section
What: finer skeleton blocks per list section instead of whole-list skeleton.
Why parked: polish on top of the converged pattern — invisible value until Child 3 lands.
Needs: per-section design + proof it doesn't mask errors.

### 7. Per-list pause tuning on proof
What: adjust the shared search pause per list on measurement (e.g. longer for heavy report lists).
Why parked: the uniform pause is a proven default; tuning per list needs per-list proof it helps without feeling laggy.
Needs: per-list timings + UX sign-off.

### 8. Consolidated list-health counts on the existing health screen
What: surface search hits, slow searches, and limit trips per list where IT already looks.
Why parked: visibility extension with its own UX plus the standing privacy rule (counts and slows only, never typed words).
Needs: health-screen design + privacy confirmation.

## Explicitly not in this note
Scope, rules, acceptance criteria, or any implementation mechanism for any item above — those come from calibration with the user, not from this stub. Do not build from this file.
