---
name: spec
description: Guided, product-friendly spec writer for THIS project only. Use when the user says /spec, "create a spec", "write a spec", "spec out a feature", "help me plan a feature", or wants a specification drafted in plain, non-technical language. Interviews the user one question at a time, drafts a product-first spec with a short technical appendix, then saves it to 1Dcoument&OtherInfo/specs/.
---

# Spec - Product-Friendly Specification Interview

This skill turns a rough feature idea into an approved, product-first specification stored in the canonical spec folder. It is the friendly front door to the strict spec-first policy in `AGENTS.md` §1.1 and `spec-rules.md`.

---

## 1. Hard Rules (never break these)

1. **No code. Ever.** This skill only produces a specification document. Do not write PHP, Blade, migrations, tests, or configs during this skill.
2. **No CLI execution.** Commands are output as copy-pasteable Markdown blocks for the user (per `AGENTS.md` §1.2).
3. **Canonical save location**: `1Dcoument&OtherInfo/specs/<feature-name>.md` (lowercase, hyphenated file name).
4. **One question at a time.** Never paste a wall of questions. Ask exactly one question, wait for the answer, then ask the next. Use the `question` tool when it fits, or plain chat for open-ended answers.
5. **Plain language only in the main body.** The spec must read like a product brief written for a school administrator, not a developer document.

---

## 2. Phase 0 - Check for an Existing Spec (always first)

Before the interview, search `1Dcoument&OtherInfo/specs/` for a spec that matches or overlaps the request.

- **Found**: Show its name and status. Ask ONE question: *"A spec like this already exists — should we revise that one, or is this a different feature?"* Then either edit-mode (Phase 4) or continue as new.
- **Not found**: Proceed to the interview as a new spec.

---

## 3. Phase 1 - The Interview (one question at a time)

Follow this question journey in order. Skip a question only if the answer is already obvious from an earlier answer or from the codebase. Keep each question short, warm, and jargon-free. Offer 2-4 concrete options when helpful (use the `question` tool).

**Question journey:**

1. **Name** - "What should we call this feature?" (becomes the spec file name)
2. **Problem** - "What problem does this solve? Why do we need it now?"
3. **Who benefits** - "Who will use this? (e.g., Registrar, Cashier, Teacher, Parent, Student)"
4. **Current pain** - "How do people handle this today? What goes wrong or takes too long?"
5. **Happy path** - "Walk me through how it should work, step by step, from start to finish."
6. **Must-hold rules** - "What must ALWAYS be true when this runs? (e.g., parents see only their own children's fees)"
7. **Must-never rules** - "What must NEVER happen? (e.g., a student is never charged twice)"
8. **Edge cases** - "What could go wrong or look unusual? What should happen then?"
9. **Out of scope** - "What should this NOT try to do, at least for now?"
10. **Success check** - "How will we know it works? Describe it as a yes/no check a non-technical person could verify."
11. **Nice-to-haves** - "Anything else you'd love it to do later, but not in this first version?"

**Interview behavior:**

- Paraphrase each answer back in one sentence before moving on ("So: cashiers can split fees into 3 installments — correct?").
- If the user gives a vague answer, ask ONE follow-up for a concrete example. Do not interrogate beyond two rounds; mark the item as an open question instead.
- Note open questions in the draft under a clearly marked section so nothing is silently guessed (per `spec-rules.md` §7 - spec drift).
- Do **not** ask technical questions (tables, routes, classes) during the interview. Technical details go in the appendix and come from your own codebase research in Phase 2.

---

## 4. Phase 2 - Technical Appendix (silent research)

After the interview, inspect the relevant code read-only (models, controllers, routes, views) and fill the **Technical Notes** appendix yourself. The appendix translates the product body into implementation pointers for developers. Only ask the user a technical question if something is genuinely unresolvable from the code.

---

## 5. Phase 3 - Draft, Review, Save

1. **Draft**: Compose the full spec using the template below and show it **in chat** for review.
2. **Review loop**: Apply the user's edits, re-show only the changed sections. Repeat until the user is satisfied.
3. **Save**: Save to `1Dcoument&OtherInfo/specs/<feature-name>.md` **only after explicit approval** ("approved", "save it"). Set `Status: Approved` and fill the Approval block per `spec-rules.md` §6.
   - If the user asks to save before deciding, save with `Status: Draft` and remind them approval is still pending.
4. **Close**: Confirm the saved path and remind the user that code work on this feature may now begin, and any commit must map back to this spec (`spec-rules.md` §8).

---

## 6. Phase 4 - Editing an Existing Spec

When revising: read the current file first, ask one question at a time about what should change, update the body, and **reset `Status` to `Draft`** with a note that re-approval is required (per `spec-rules.md` §6 - edits after approval invalidate approval).

---

## 7. Spec Template (product-first + technical appendix)

```markdown
# Spec: <Feature Name>

- **Status**: Draft | Approved | Implemented
- **Created**: YYYY-MM-DD
- **Approved by**: <user> on YYYY-MM-DD   <!-- only when Status = Approved -->

## 1. Why We Need This
Plain-language problem statement and expected benefit. No jargon.

## 2. Who Is Affected
Bulleted list of user roles and what each role gains.

## 3. How It Should Work
Numbered happy-path steps as a user experiences them.

## 4. Business Rules
### Must always be true
### Must never happen
### Edge cases and what happens then

## 5. Out of Scope
What is deliberately not included in this version.

## 6. Success Checks
- [ ] Yes/no verification a non-technical person can perform
- [ ] ...

## 7. Open Questions (if any)
Unresolved items awaiting a decision. Nothing here may be guessed during implementation.

## 8. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
- Data/records touched:
- Roles/permissions involved:

## 9. Approval
> Approved by <user> on YYYY-MM-DD.
```

**Language guardrails for sections 1-7:**

- Write like explaining to a busy principal: short sentences, everyday words.
- Banned in the main body: endpoint, migration, foreign key, controller, middleware, Eloquent, repository. (These belong in Technical Notes only.)
- Every rule must be checkable: "fees are never charged twice", not "system should be robust".
