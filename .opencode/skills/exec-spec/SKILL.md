---
name: exec-spec
description: Follow-up skill that executes an approved spec created by the /spec skill. Use when the user says /exec-spec, "execute the spec", "implement the spec", "start the spec work", "build the spec", or names a spec file in 1Dcoument&OtherInfo/specs/ to implement. Summarizes the spec and asks for re-calibration before any code, then enforces the spec-gated workflow: plan first, implement in validated slices, halt on spec gaps.
---

# Exec-Spec - Execute an Approved Specification

This skill is the implementation half of the `/spec` workflow. It takes an approved spec from `1Dcoument&OtherInfo/specs/` and implements it slice by slice, under every rule in `AGENTS.md` and `spec-rules.md`.

---

## 1. Hard Rules (never break these)

1. **Approved spec only**: Never write code unless the spec's `Status` is `Approved` with a recorded approval (`spec-rules.md` §6). `Draft` or missing approval = hard stop; tell the user to finish the `/spec` flow first.
2. **No direct CLI execution**: Every command (`php artisan`, `composer`, `npm`, `pest`, `git`) is output as a copy-pasteable Markdown block for the user to run and report back (`AGENTS.md` §1.2).
3. **Spec-gated commits**: Never propose, stage, or run a commit until every changed file maps to the approved spec (`spec-rules.md` §8).
4. **Scope containment**: Touch only files covered by the spec's scope/Technical Notes. No drive-by refactors, reformatting, or "while I'm here" fixes.
5. **Code standards**: All code follows `AGENTS.md` §2 - `declare(strict_types=1)`, explicit types, slim controllers, FormRequests (no inline `$request->validate()`), services/actions for domain logic, `DB::transaction()` for multi-table writes, tuple route syntax.
6. **Destructive operations are forbidden**: `migrate:fresh`, `db:wipe`, `rm -rf`, `.env` edits, new dependencies, global middleware changes = hard stop (`AGENTS.md` §4).
7. **Summary before execution**: Never present an implementation plan or write code before showing the plain-language execution summary and getting the user's re-calibration answer (Phase 2).
8. **Recommendation-first questions**: Never ask an open/blank question. Every question offers **2-4 concrete options with your recommended default first and a one-line reason** (use the `question` tool) — `AGENTS.md` §1.5. Act as the SME (`AGENTS.md` §1.6-§1.7): you propose, the user decides.

---

## 2. Phase 0 - Locate & Gate Check

1. Find the spec:
   - If the user named a file or feature, use it directly.
   - Otherwise list `1Dcoument&OtherInfo/specs/` and ask ONE question via the `question` tool: which spec to execute (each spec file = one option, recommended/most recently approved first).
2. Read the full spec file.
3. **Gate check** (all must pass, else hard stop with a clear message):
   - `Status: Approved` and the Approval block is filled.
   - No unresolved items in **Open Questions** (if any exist, halt and ask the user to resolve them via `/spec` first — never guess).
   - Acceptance/Success Checks exist and are testable.
4. Search for **later revisions**: if the file notes edits after approval without re-approval, stop (`spec-rules.md` §6).

---

## 3. Phase 1 - Context Audit (read-only)

Before proposing anything:

1. Read the spec's **Technical Notes** and verify each pointer against the actual codebase (files, routes, tables, roles). Correct any stale pointers in your head — the code is the source of truth (`AGENTS.md` §1.3).
2. Audit the surrounding code: existing models, services, migrations, routes, views, and tests that interact with the change.
3. Flag any conflict between the spec and reality (e.g., a table the spec assumes doesn't exist) as a **spec gap** → the Spec Gaps rule below.

---

## 4. Phase 2 - Execution Summary & Re-Calibration Check (mandatory)

Before proposing a plan or writing any code, send **one message** containing:

1. **Execution summary** in plain language (no jargon):
   - Feature name + spec file path + approval date
   - Why we're doing this (1 sentence)
   - Who is affected (roles)
   - What will be built (3-6 bullets from the happy path)
   - Key rules (must-always / must-never, 3-5 bullets)
   - What's out of scope
   - How we'll know it works (one line: N acceptance checks)
   - Role & UX impact: effects on other roles (registrar, cashier, teacher, parent...), plus flow/UX notes from the spec (placement, primary action) — `AGENTS.md` §1.6-§1.7
   - Context-audit findings: anything in the spec that looked stale, missing, or conflicting (empty if none)
2. **One question** via the `question` tool — 3 concrete options, recommended first with a one-line reason:

   *"Anything to re-calibrate before we start?"*
   - **No - proceed to planning (Recommended)** — summary matches the spec and audit found no conflicts → Phase 3
   - **Yes - the spec needs adjusting** → apply the Spec Gaps rule (update spec, reset to `Draft`, re-approval required), then re-present the summary
   - **Yes - the summary is wrong** → correct the summary to match the spec (or flag a spec gap if the spec itself is unclear) and re-present it

Do not move to the implementation plan until the user has answered. If the user stays silent or answers ambiguously, re-ask once; never treat silence as approval.

---

## 5. Phase 3 - Implementation Plan (get approval first)

Present a step-by-step plan **in chat before writing any code**:

- Slice the work by layer, typically: **data/migrations → domain logic (services/actions) → HTTP (routes/requests/controllers) → UI (views) → tests**.
- Each slice lists: goal, files to create/modify, and which spec section it satisfies.
- Number the slices and ask ONE question via the `question` tool: *"Plan looks good?"* with options: **Approve - start slice 1 (Recommended)** / Adjust the plan (say what) / Reorder slices.

No coding until the user approves the plan.

---

## 6. Phase 4 - Slice-by-Slice Execution

For each slice, in order:

1. **Announce**: one short sentence on what this slice delivers.
2. **Implement**: minimal, spec-covered code only.
3. **Report**: list files changed with one line each on what happened.
4. **Verify**: delegate the relevant command to the user as a code block (e.g., `php artisan test --filter=X`), then wait for their output before continuing.
5. **Checkpoint**: ask ONE question with options - *"Slice N done."* → **Proceed to slice N+1 (Recommended)** / Pause here / Stop and review.

If a slice grows beyond the plan, stop and re-plan with the user rather than expanding scope.

---

## 7. Spec Gaps (Halt + Update Rule)

If implementation reveals a requirement the spec doesn't cover, or contradicts it:

1. **Stop immediately.** Do not improvise domain logic (`AGENTS.md` §1.5).
2. State the gap in plain language: what's missing and why it blocks work.
3. Present your proposed spec wording as options (use the `question` tool): **Use my wording (Recommended)** / Edit it / Handle another way.
4. On the user's approval, edit the spec file with the new rule and **reset `Status` to `Draft`** with a note that re-approval is required for the changed part (`spec-rules.md` §6).
5. Wait for explicit re-approval before resuming implementation.

---

## 8. Phase 5 - Verification Against Acceptance Criteria

When all slices are implemented:

1. Walk through the spec's **Success Checks / Acceptance Criteria** one by one.
2. For each check, provide the command or manual step for the user to run, and record their reported result.
3. If any check fails → fix in a new slice (re-announce and checkpoint as usual), then re-run that check.
4. Do not declare success until every check has a confirmed pass from the user.

---

## 9. Phase 6 - Closure

After all checks pass:

1. Ask ONE question with options: *"All acceptance checks passed."* → **Mark spec Implemented (Recommended)** / Not yet - hold.
2. On yes, update the spec file:
   - `Status: Implemented`
   - Add a one-line implementation note (date, summary).
3. Present the final changed-file list and remind the user of the commit gate: map every file to this spec, then provide a suggested commit message referencing the spec, e.g.:

   ```
   feat: <feature summary> (spec: <feature-name>.md)
   ```

   The user runs the git commands themselves (`spec-rules.md` §8).
4. Never push, commit, or alter git history directly.
