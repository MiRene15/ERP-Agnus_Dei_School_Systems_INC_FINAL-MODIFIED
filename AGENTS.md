# AGENTS.md - AI Coding Agent Operational Guidelines

This document defines the mandatory operational boundaries, technical standards, and behavioral constraints for all AI coding agents (OpenCode, Cursor, Windsurf, Claude Code) working on this Laravel project.

---

## 1. Core Directives & Safety Rails

### 1.1 Strict Spec-First Policy (No Spec = No Code)

* **Governing Document**: The full rulebook for creating, approving, storing, implementing, and committing specs is **`spec-rules.md`** at the repo root. All spec-related behavior must comply with it; where this file is ambiguous on spec matters, `spec-rules.md` prevails.
* **ABSOLUTE RULE - NO CODE WITHOUT A SPEC**: The agent is **strictly prohibited** from writing, generating, or modifying implementation code (PHP controllers, models, migrations, views, services, or tests) unless an approved feature specification document (`SPECIFICATION.md` or explicit user-approved spec) exists for the task.
* **Spec Generation Phase First**: If a user asks for a new feature, endpoint, or complex refactor, the agent must first operate in **Specification Mode** (asking calibration questions and drafting the specification) before touching any codebase files.
* **Canonical Spec Location**: All approved specification documents MUST be stored in `1Dcoument&OtherInfo/specs/` (i.e., `<repo-root>\ERP-Agnus_Dei_School_Systems_INC_FINAL-MODIFIED\1Dcoument&OtherInfo\specs`). Name files descriptively, e.g., `1Dcoument&OtherInfo/specs/<feature-name>.md`.
* **Always Check the Specs Folder First**: Before claiming no spec exists, the agent MUST list/search `1Dcoument&OtherInfo/specs/` for a matching or related specification. The absence of `SPECIFICATION.md` at the repo root does not mean no spec exists. If a matching spec is found there, use it as the source of truth; if none is found, proceed with the Spec Generation Phase.
* **HARD RULE - NO SPEC, NO COMMIT**: A valid spec is a mandatory precondition for committing changes. If any code change (PHP, Blade, JS, migration, config, test) in the working tree is not covered by an approved specification in `1Dcoument&OtherInfo/specs/`, the agent MUST NOT propose, stage, or instruct the user to run `git commit` for it. Unspec'd changes must either be reverted or spec'd first. Full commit gating rules: `spec-rules.md` §8.

### 1.2 Zero Direct CLI Execution (Strict User Delegation)

* **NO DIRECT SHELL COMMANDS**: The agent is **strictly forbidden** from automatically or directly executing CLI/terminal commands (e.g., via tool calls, background shells, or subshells).
* **Mandatory Command Delegation**: Every command—including `php artisan`, `composer`, `npm`, `git`, `pest`, `phpunit`, or file system operations—must be provided as a clear, copy-pasteable Markdown code block. The agent must instruct the user to run the command manually in their terminal and report back the output.

### 1.3 Zero Hallucination Policy

* **Fact-Based Context**: Only use verified code in the repository, official Laravel documentation, or installed package interfaces (`composer.json` / `package.json`).
* **No Imaginary APIs**: Never guess method signatures, helper functions, or database schema columns.
* **Missing Context Protocol**: If any context, configuration, or structural pattern is missing, **STOP IMMEDIATELY AND ASK**. Do not fabricate placeholders or mock implementations.

### 1.4 Anti-Auto-Pilot & Anti-Auto-Commit Rules

* **No Auto-Git Execution**: Never attempt to run `git commit`, `git push`, `git checkout`, or alter git history directly or automatically.
* **Spec-Gated Commits**: Never recommend, draft, or stage a commit unless every changed file is covered by an approved spec in `1Dcoument&OtherInfo/specs/`. When in doubt, run `git status`/`git diff` (delegated to the user), map each changed file to a spec, and halt if any file is unspec'd.
* **Incremental Execution**: Break down complex tasks into discrete steps. Present each step, await user validation, and proceed only upon confirmation.

### 1.5 Zero Assumptions & Mandatory Interrogation Protocol

* **Ask Before Writing**: If a business requirement, database relation, variable naming convention, or edge case is ambiguous, halt execution and ask clarifying questions.
* **No Business Rule Guessing**: School/Domain logic (e.g., fee calculations, grade locks, permission cascades) carries real-world consequences. Prompt the user for explicit rules whenever ambiguity exists.

---

## 2. Laravel 11 Technical Architecture Standards

### 2.1 Code Structure & Organization

* **Slim Controllers**: Controllers must only act as HTTP wrappers. Move all domain business logic to dedicated Service or Action classes (`App\Services\...` or `App\Actions\...`).
* **Form Requests**: Do not perform inline controller validation (`$request->validate()`). Always create a dedicated `FormRequest` class in `App\Http\Requests\...`.
* **Eloquent Models & Migrations**:
  * Enforce strict `$fillable` arrays.
  * Define explicit return types on all relationships (e.g., `public function student(): BelongsTo`).
  * Ensure migrations include explicit foreign key constraints, indexes, and cascades where necessary.
* **Routing**: Use tuple array syntax (`[StudentController::class, 'index']`) inside `routes/web.php` or `routes/api.php`.

### 2.2 Quality & Type Safety

* **Strict Types**: Add `declare(strict_types=1);` at the top of every new PHP file.
* **Explicit Typing**: All class methods must declare explicit argument types and return types.
* **Database Transactions**: Any multi-table write operation must be wrapped in `DB::transaction(function () { ... });`.

---

## 3. Agent Task Execution Workflow

Before writing code or editing files, follow this sequence:

```
┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  Received Task  │ ──> │ Spec Check (Req) │ ──> │ Ambiguity Check  │
└─────────────────┘     └──────────────────┘     └──────────────────┘
                                                          │
┌─────────────────┐     ┌──────────────────┐              ▼
│ Delegate Commands│ <── │  User Approval   │ <── ┌──────────────────┐
│   to Human      │     └──────────────────┘     │ Plan / Spec Approval
└─────────────────┘                              └──────────────────┘

```

1. **Spec Check**: Follow the pre-implementation gate in `spec-rules.md` §4 — search `1Dcoument&OtherInfo/specs/` (then `SPECIFICATION.md` at repo root) for a valid feature specification or approved prompt spec. If missing, **refuse code generation** and request/generate a spec first (saving any new spec into `1Dcoument&OtherInfo/specs/` using the template in `spec-rules.md` §5).
2. **Context & Ambiguity Check**: Audit existing models, controllers, services, and migrations. Identify edge cases or missing business rules and ask clarifying questions.
3. **Plan Proposal**: Present a step-by-step implementation plan grounded in the approved specification.
4. **Execution & Command Delegation**: Apply changes incrementally. Output all terminal commands as code blocks for the user to run manually in their CLI.
5. **Verification**: Ask the user to provide terminal or test output to verify changes.

---

## 4. Hard Stop Conditions

Immediately stop and prompt the user if your task involves any of the following:

* **Attempting to write implementation code without an approved Feature Specification (in `1Dcoument&OtherInfo/specs/` or `SPECIFICATION.md`).**
* **Attempting to commit, stage, or propose committing any change that is not covered by an approved spec (No Spec = No Commit).**
* **Attempting to run a CLI/terminal command directly instead of delegating it to the user.**
* Destructive operations (`php artisan migrate:fresh`, `db:wipe`, `rm -rf`).
* Altering `.env`, credentials, or security configurations.
* Installing new Composer or NPM dependencies.
* Modifying broad global scopes or global middleware.

---

## 5. Code Blueprints

### Form Request Template

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-students');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'unique:users,email'],
        ];
    }
}
```

### Controller Template

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\StoreStudentRequest;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\RedirectResponse;

class StudentController extends Controller
{
    public function store(StoreStudentRequest $request, StudentEnrollmentService $service): RedirectResponse
    {
        $student = $service->enrollStudent($request->validated());

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Student enrolled successfully.');
    }
}
```

---
