---
name: laravel-spec-generator
description: Triggers whenever a user asks to design, plan, architect, or spec out a feature, database model, API endpoint, or workflow for a Laravel application. Enforces a strict "Calibration First, Spec Second, Code Never" workflow.
---

# Laravel Feature Specification & Calibration Skill

This skill governs the phase between receiving a feature request and producing a formal `SPECIFICATION.md` file for a Laravel 11 application. 

---

## 1. Core Operating Principles

1. **STRICT NO-CODE RULE**: While this skill is active, you are **strictly forbidden** from generating implementation code (PHP models, controllers, services, blade views, JS/Vue, or migrations). Your sole deliverable is technical calibration and specification drafting.
2. **NO CLI EXECUTION**: Never run terminal or CLI commands directly. All required commands (`php artisan`, `composer`, `pest`, `git`) must be output as copy-pasteable Markdown blocks for human execution.
3. **CALIBRATION BEFORE SPECIFICATION**: You must NEVER output a `SPECIFICATION.md` document on your first response. You MUST first ask a structured set of calibration questions to uncover edge cases, state transitions, performance bottlenecks, and architectural constraints.

---

## 2. Phase 1: Calibration Interrogation

When a user submits a feature request, halt and respond immediately with a section titled `## Calibration Questions`. Present **4 to 6 precise, high-impact questions** distributed across the following four core vectors:

### A. Business Logic & Edge Cases
- What state transitions exist for this entity? (e.g., Draft ➔ Submitted ➔ Approved ➔ Archived)
- What soft-delete, cancellation, or rollback rules apply?
- What financial, audit-trail, or regulatory locks must be enforced?

### B. Database & Data Modeling
- What index/unique constraints are required to prevent race conditions or duplicate entries?
- Are there cascade deletion rules or foreign key restrictions across related tables?
- Does this require multi-tenant, multi-branch, or role-isolated data boundaries?

### C. Laravel Architecture & Security
- What specific FormRequest validation rules and authorization Policies apply?
- Should this process run synchronously or be dispatched to a background Queue Job?
- Are there domain events (`Dispatched Events`) that other modules need to listen for?

### D. Performance & Scalability
- What are the expected record volume and concurrency expectations?
- How will we prevent N+1 query issues on list/export endpoints?
- Should multi-table mutations be wrapped inside an explicit `DB::transaction()`?

---

## 3. Phase 2: Specification Generation

Once the user has answered the calibration questions, generate a comprehensive `SPECIFICATION.md` document adhering to the exact template below:

```markdown
# SPECIFICATION: [Feature Name]

## 1. Overview & User Intent
* **Goal**: [Brief summary of the feature purpose]
* **Target Roles**: [Impacted user roles, e.g., Registrar, Cashier, Student]
* **Scope Limits**: [What is explicitly OUT of scope]

## 2. Database Schema & Migration Spec
* **Tables Modified/Created**:
  * `[table_name]`:
    * `id`: `ulid()` / `bigIncrements()`
    * `[column_name]`: `[type]` | [constraints: nullable, unique, indexed]
    * `created_at`, `updated_at`, `deleted_at`
* **Foreign Key Constraints & Cascades**: [List explicitly]
* **Indexes & Composite Keys**: [List for query optimization]

## 3. Domain Layer (Actions & Services)
* **DTOs**: `App\DataTransferObjects\[Name]Data` (Strictly typed array or object map)
* **Services/Actions**: `App\Actions\[Name]Action` or `App\Services\[Name]Service`
* **Transaction Bounds**: Wrap multi-table updates in `DB::transaction()`

## 4. HTTP & Authorization Specs
* **Routes**:
  * `VERB /path` ➔ `[ControllerClass::class, 'method']` | Middleware: `['auth', 'can:...']`
* **Form Requests**: `App\Http\Requests\[Name]Request`
  * Validation matrix (attribute, rule list, custom error messages)
* **Policies**: `App\Policies\[Name]Policy`
  * Abilities: `viewAny`, `view`, `create`, `update`, `delete`

## 5. Event & Queue Specifications
* **Dispatched Events**: `App\Events\[Name]`
* **Listeners**: `App\Listeners\[Name]Subscriber`
* **Queue Jobs**: `App\Jobs\[Name]Job`
  * Connection: `redis`/`database` | Tries: 3 | Timeout: 60s

## 6. Testing Matrix (Pest / PHPUnit)
* **Feature Tests**:
  * [ ] Guest cannot access endpoint.
  * [ ] Unauthorized role receives 403 Forbidden.
  * [ ] Valid request successfully persists data and dispatches event.
  * [ ] Invalid payload triggers 422 Unprocessable Entity.
* **Unit Tests**:
  * [ ] Service handles calculation/edge case accurately.
