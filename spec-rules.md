# Spec Rules - Creating & Implementing Specifications

This document is the authoritative rulebook for how feature specifications are created, approved, stored, and consumed during implementation in this repository. It expands on `AGENTS.md` §1.1 (Strict Spec-First Policy). In case of conflict, this document governs spec-related behavior.

---

## 1. Core Principle

**No Spec = No Code. No Spec = No Commit.**

No implementation code (PHP, Blade, JS, migration, config, test) may be written, modified, staged, or committed unless a valid, approved specification covering that change exists in the canonical spec location.

---

## 2. Canonical Location & Naming

* **Storage folder**: `<repo-root>\ERP-Agnus_Dei_School_Systems_INC_FINAL-MODIFIED\1Dcoument&OtherInfo\specs\`
* **Relative path**: `1Dcoument&OtherInfo/specs/`
* **File naming**: `<feature-name>.md` — descriptive, lowercase, hyphenated (e.g., `student-enrollment-wizard.md`, `fee-installment-plan.md`).
* **One spec per feature/task**. Unrelated changes require separate specs.
* Specs are Markdown files, UTF-8, and are committed to the repository like any other project document.

---

## 3. Spec Lifecycle

### Phase 1 - Discovery (Calibration Questions)

Before drafting anything, the agent must:

1. Check `1Dcoument&OtherInfo/specs/` (and `SPECIFICATION.md` at repo root) for an existing or related spec.
2. Audit the relevant existing code (models, controllers, services, migrations, routes).
3. Ask the user clarifying questions about ambiguous business rules, edge cases, naming, and scope. **Never guess domain logic.**

### Phase 2 - Drafting

Draft the spec using the template in §5. Save it to `1Dcoument&OtherInfo/specs/<feature-name>.md`.

### Phase 3 - Approval

* A spec is **approved** only when the user explicitly confirms it (e.g., "approved", "looks good, proceed").
* Drafting, saving, or mentioning a spec does **not** make it approved.
* The approval must be recorded in the spec file itself (see §5, Approval block).

### Phase 4 - Implementation

Implement strictly within the approved spec's scope (see §7).

### Phase 5 - Verification & Closure

1. Present results/tests output to the user.
2. Update the spec's Status to `Implemented` and note the commit/PR reference.
3. Only after the spec is satisfied may changes be committed (see §8).

---

## 4. Pre-Implementation Gate (Mandatory Checklist)

Before writing a single line of code, the agent MUST be able to answer **yes** to all of:

- [ ] I searched `1Dcoument&OtherInfo/specs/` for an existing matching spec.
- [ ] A spec exists for this exact task (existing or newly drafted).
- [ ] The spec Status is `Approved` with an explicit user approval recorded.
- [ ] All ambiguous business rules were clarified with the user.
- [ ] The files I am about to touch are all within the spec's **In-Scope** list.

If any answer is **no**: stop, and either draft/request a spec or request approval. Do not write code.

---

## 5. Spec Document Template

```markdown
# Spec: <Feature Name>

- **Status**: Draft | Approved | Implemented
- **Author**: <agent or user>
- **Created**: YYYY-MM-DD
- **Approved by**: <user> on YYYY-MM-DD   <!-- only when Status = Approved -->

## 1. Problem / Goal
What problem is being solved and why.

## 2. Scope
### In-Scope
- Explicit list of files, tables, routes, behaviors to be changed/added.
### Out-of-Scope
- Explicit list of what must NOT be touched.

## 3. Business Rules
Explicit, unambiguous rules (fee logic, permissions, edge cases). No assumptions.

## 4. Data Model
Tables/columns/relationships affected (if any).

## 5. API / Routes / UI
Endpoints, route names, screens, and payloads (if any).

## 6. Acceptance Criteria
- [ ] Testable condition 1
- [ ] Testable condition 2

## 7. Verification Plan
How the change will be verified (commands, test files, manual steps).

## 8. Approval
> Approved by <user> on YYYY-MM-DD.
```

---

## 6. Approval Rules

* Only the **user** can approve a spec.
* Verbal/implied approval (e.g., "go ahead" sent while discussing something else) is ambiguous — restate what is approved and get a clear confirmation.
* Editing a spec after approval **invalidates approval** for the changed parts; re-approval is required.
* Status must be updated to `Approved` in the file when approval is granted.

---

## 7. Implementation Rules (Traceability & Scope)

* **Traceability**: every code change must map to at least one item in the spec's Scope or Acceptance Criteria. Changes with no spec coverage are forbidden.
* **Scope containment**: do not refactor, reformat, or "clean up" code outside the spec's In-Scope list.
* **Spec drift**: if implementation reveals a requirement not covered by the spec, **stop**, update the spec, get re-approval, then continue.
* **Incremental execution**: implement in small steps; present each step for user validation before proceeding.
* **Zero assumptions**: unresolved ambiguity = hard stop and ask (see `AGENTS.md` §1.5).

---

## 8. Commit Rules (Spec-Gated Commits)

1. Before recommending any commit, map **every** changed file to an approved spec.
2. If any changed file is unspec'd: halt. Revert the file or extend/get approval for a spec covering it.
3. Never stage or run `git commit` directly — delegate the command to the user (see `AGENTS.md` §1.2), but only after the spec mapping check passes.
4. Commit messages should reference the spec (e.g., `feat: student enrollment wizard (spec: student-enrollment-wizard.md)`).

---

## 9. Exceptions

* There are **no implicit exceptions** (no "it's just a typo" bypass).
* If the user wants to waive the spec requirement for a change, the waiver must be **explicit and recorded** as a minimal spec entry in `1Dcoument&OtherInfo/specs/` (e.g., `waivers.md` noting the change, date, and user approval).

---

## 10. Hard Stop Conditions (Spec-Related)

Stop immediately and prompt the user if:

* Asked to write code with no matching spec in `1Dcoument&OtherInfo/specs/`.
* Asked to commit changes that are not fully covered by an approved spec.
* A required business rule is missing from the spec.
* The user requests a scope change not reflected in the spec.
