# Spec: Cashier Search Overhead (follow-up)

- **Status**: Draft — hypothesis note only. NOT calibrated, NOT approved, NOT implementable. Awaiting the full `/spec` flow.
- **Created**: 2026-10-06
- **Origin**: decision in `cashier-search-speed.md` §12 (Part B, 2026-10-06).

## What Part B proved (the starting facts, not guesses)

- Post-Part-A browser timings: 3.2–7.4 s against a ~2 s baseline — no improvement observed.
- The query itself: 3–4 ms stable. Trivial floor: 0.1 ms. Table: ~450 enrolled rows.
- Index ruled out (would buy ~2 ms at write-overhead cost).
- Conclusion: the binding cost is per-request overhead outside the query. Everything below is hypothesis.

## Hypothesis (unproven)

Unpooled per-PHP-request DB connection setup to Tokyo (TCP + TLS + auth on every request under `serve`) — high and variable, fits the 3–7 s swings; the pooled SQL Editor never pays it, which is why the floor looks fast. Not excluded: per-request DNS, framework boot costs under `serve`, render/JSON costs.

## What the future spec must do first

Re-run the same 4-number protocol against candidate causes (persistent connections, pooler/transaction mode, DNS behavior) and let the numbers pick the fix. No fix is pre-decided here.

## Explicitly not in this note

Scope, rules, acceptance criteria, or any implementation mechanism — those come from calibration with the user, not from this stub. Do not build from this file.
