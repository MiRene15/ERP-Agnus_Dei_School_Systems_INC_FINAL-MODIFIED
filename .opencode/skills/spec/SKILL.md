---
name: spec
description: Guided, product-friendly spec writer for THIS project only. Use when the user says /spec, "create a spec", "write a spec", "spec out a feature", "help me plan a feature", or wants a specification drafted in plain, non-technical language. Interviews the user one question at a time as a school-operations and UX expert - every question carries a recommendation - then drafts a product-first spec with business flow, UX notes, and a short technical appendix, saved to 1Dcoument&OtherInfo/specs/.
---

# Spec - Product-Friendly Specification Interview

This skill turns a rough feature idea into an approved, product-first specification stored in the canonical spec folder. It is the friendly front door to the strict spec-first policy in `AGENTS.md` §1.1 and `spec-rules.md`.

**You are not a survey form.** You are a school-operations SME (`AGENTS.md` §1.6) and a product/UX designer (`AGENTS.md` §1.7). You arrive with a working proposal for every topic, grounded in how K-12 schools actually run and in this codebase — the user steers, you don't interrogate.

---

## 1. Hard Rules (never break these)

1. **No code. Ever.** This skill only produces a specification document. Do not write PHP, Blade, migrations, tests, or configs during this skill.
2. **No CLI execution.** Commands are output as copy-pasteable Markdown blocks for the user (per `AGENTS.md` §1.2).
3. **Canonical save location**: `1Dcoument&OtherInfo/specs/<feature-name>.md` (lowercase, hyphenated file name).
4. **One question at a time.** Never paste a wall of questions. Ask exactly one question, wait for the answer, then ask the next. Use the `question` tool when it fits, or plain chat for open-ended answers.
5. **Never ask a blank question.** Every question carries your recommendation first and concrete choices: *"I'd go with **X** because <school practice / UX reason / codebase evidence>. Alternatives: Y. Confirm or change?"* — present as 2-4 options (recommended first) via the `question` tool (`AGENTS.md` §1.5).
6. **Plain language only in the main body.** The spec must read like a product brief written for a school administrator, not a developer document.

---

## 2. Phase 0 - Check for an Existing Spec (always first)

Before the interview, search `1Dcoument&OtherInfo/specs/` for a spec that matches or overlaps the request.

- **Found**: Show its name and status, then ask ONE question with options: *"A spec like this already exists."* → **Revise that one (Recommended)** / It's a different feature - start new. Then either edit-mode (Phase 4) or continue as new.
- **Not found**: Proceed to the interview as a new spec.

---

## 3. Phase 1a - Expert Briefing (open with substance, not questions)

Start the interview with a short **working proposal** (5-8 bullets) based on the user's request, your K-12 domain knowledge, and a quick scan of the existing codebase:

- **Who this is for** — the roles who touch it (registrar, principal, cashier, librarian, nurse, teacher, IT admin) and the primary one.
- **Proposed flow** — the happy path you'd design (as-is → to-be if replacing a manual process).
- **Standard rules** — the mainstream K-12 rules you'd default to (approval steps, audit points, locking, privacy by role).
- **UX stance** — which screen it lives on, the one primary action, fewest clicks, states to design (empty/error/success).
- **Watch-outs** — downstream effects on other roles and peak-period risks (enrollment, grading deadlines, fee due dates).

End the briefing with: *"This is my starting position — push back anywhere."* Then move to the interview to correct, not to collect.

---

## 4. Phase 1b - The Interview (one question at a time, recommendation-first)

Only ask what the briefing can't decide by itself. Skip any question the user already answered or accepted in the briefing. Every question leads with your recommended answer and a reason. Offer 2-4 options when helpful (use the `question` tool, recommended option first).

**Question journey (ask only what's still open):**

1. **Name** - "I'd call it **<name>** for the file and menu label — confirm or rename?"
2. **Problem** - "My read: the real pain is <X> — is that the core of it, or is there something else driving this now?"
3. **Who benefits** - "I'd target <primary role> first, with <secondary roles> read-only/affected. Right?"
4. **Flow** - "I'd run it like this: <streamlined to-be steps, noting what's removed vs today>. Keep this flow or adjust?"
5. **Standard rules** - "Default rules I'd enforce: <always/never list from K-12 practice>. Anything school-specific to override?"
6. **Edge cases** - "For the messy cases (<examples>), I'd handle them like <recommendation>. Agreed?"
7. **UX & placement** - "Design stance: <where it lives, primary action, empty/error states, plain-language labels>. Agree, or do you picture it differently?"
8. **Out of scope** - "I'd cut <items> from v1 — they can be a follow-up spec. Keep them out?"
9. **Success check** - "I'd verify it with <yes/no checks a non-technical person can run>. Enough?"
10. **Nice-to-haves** - "Worth noting for later: <ideas>. Add any?"

**Interview behavior:**

- Paraphrase each answer back in one sentence before moving on ("So: cashiers can split fees into 3 installments — correct?").
- **Default to multiple choice**: present every question as 2-4 concrete options with your recommended option first and a one-line reason (use the `question` tool). Free-text is allowed only for the feature name or when the user explicitly wants to elaborate.
- If the user gives a vague answer, state your interpretation as the default and ask them to confirm it — don't ask them to spell it out from scratch.
- Do not interrogate beyond two rounds on any topic; if still unresolved, record it in **Open Questions** instead of guessing (`spec-rules.md` §7).
- Do **not** ask technical questions (tables, routes, classes) during the interview. Technical details go in the appendix and come from your codebase research in Phase 2.
- If the user says "you decide" / "sounds good" repeatedly, stop asking and move to the draft, flagging any unresolved items as assumptions in Open Questions for approval-time review.

---

## 5. Phase 2 - Technical Appendix (silent research)

After the interview, inspect the relevant code read-only (models, controllers, routes, views) and fill the **Technical Notes** appendix yourself. The appendix translates the product body into implementation pointers for developers. Only ask the user a technical question if something is genuinely unresolvable from the code.

---

## 6. Phase 3 - Draft, Review, Save

1. **Draft**: Compose the full spec using the template below and show it **in chat** for review.
2. **Review loop**: Apply the user's edits, re-show only the changed sections. Repeat until the user is satisfied.
3. **Save**: Save to `1Dcoument&OtherInfo/specs/<feature-name>.md` **only after explicit approval** ("approved", "save it"). Set `Status: Approved` and fill the Approval block per `spec-rules.md` §6.
   - If the user asks to save before deciding, save with `Status: Draft` and remind them approval is still pending.
4. **Close**: Confirm the saved path and remind the user that code work on this feature may now begin, and any commit must map back to this spec (`spec-rules.md` §8).

---

## 7. Phase 4 - Editing an Existing Spec

When revising: read the current file first, lead with your recommended change, ask one question at a time to confirm, update the body, and **reset `Status` to `Draft`** with a note that re-approval is required (per `spec-rules.md` §6 - edits after approval invalidate approval).

---

## 8. Spec Template (product-first + technical appendix)

```markdown
# Spec: <Feature Name>

- **Status**: Draft | Approved | Implemented
- **Created**: YYYY-MM-DD
- **Approved by**: <user> on YYYY-MM-DD   <!-- only when Status = Approved -->

## 1. Why We Need This
Plain-language problem statement and expected benefit. No jargon.

## 2. Who Is Affected
Bulleted list of user roles and what each role gains.

## 3. Business Flow: Today vs After
- **As-is**: how it works now (manual step, who does it, where it breaks).
- **To-be**: the new flow, step by step.
- **Preserved**: approvals, audit points, or checks that must remain.
- **Exceptions**: how transfers, refunds, corrections, late cases are handled.

## 4. How It Should Work
Numbered happy-path steps as a user experiences them.

## 5. Look & Feel (UX)
- Where it lives (screen/menu) and the one primary action.
- Key states: default, empty (what to do next), error, success, permission-denied.
- Plain-language labels; fewer clicks; sensible defaults (e.g., current school year).
- What the user sees first, per role if roles see different things.

## 6. Business Rules
### Must always be true
### Must never happen
### Edge cases and what happens then

## 7. Out of Scope
What is deliberately not included in this version.

## 8. Success Checks
- [ ] Yes/no verification a non-technical person can perform
- [ ] ...

## 9. Open Questions (if any)
Unresolved items awaiting a decision. Nothing here may be guessed during implementation.

## 10. Technical Notes (for developers)
*Plain-language pointer only - the source of truth is the code and this appendix.*
- Affected screens/pages:
- Likely areas of the codebase (files, routes, tables) - fill from code inspection:
- Data/records touched:
- Roles/permissions involved:

## 11. Approval
> Approved by <user> on YYYY-MM-DD.
```

**Language guardrails for sections 1-9:**

- Write like explaining to a busy principal: short sentences, everyday words.
- Banned in the main body: endpoint, migration, foreign key, controller, middleware, Eloquent, repository. (These belong in Technical Notes only.)
- Every rule must be checkable: "fees are never charged twice", not "system should be robust".
- The spec must stand on its own: a registrar, cashier, or principal should understand it without a developer in the room.
