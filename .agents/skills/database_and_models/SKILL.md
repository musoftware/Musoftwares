---
name: Database and Models
description: Guidelines for database schema isolation, soft deletes, Spatie model states, polymorphic relationships, and strict git workflow.
---

# Database and Models Guidelines

This skill dictates the standards and best practices for working with database models, schema isolation, soft deletes, states, and relationships in the Musoftwares project. It also enforces a strict Git workflow for all AI agents.

## 1. Strict Git Workflow for AI Agents (CRITICAL)

When working on tasks, AI agents MUST follow these Git workflow rules to ensure a clean and traceable history:
- **Feature Branches**: Every new feature, fix, or task MUST be developed in its own dedicated branch (e.g., `feature/module-name-task`, `fix/issue-description`). Do not work directly on `main` or `master`.
- **Atomic Commits**: Commit changes after every logical modification. Do not bundle massive, unrelated changes into a single commit.
- **Commit Messages**: Write clear, descriptive commit messages explaining the *why* and *what* of the change.

*(Note: If explicitly instructed by the user to skip branching for a specific task, follow the user's override, but otherwise default to this workflow).*

## 2. Database Schema Isolation Between Modules

Musoftwares uses a Modular Monolith architecture via `nwidart/laravel-modules`. Schema and models must be strictly isolated:
- **Core Schema**: User Management, Financials, and Task Management models and migrations root in `app/Models/` and `database/migrations/`.
- **Modular Schema**: Module-specific models and migrations MUST strictly reside in `Modules/{ModuleName}/Models/` and `Modules/{ModuleName}/Database/Migrations/`.
- **Cross-Module References**: Avoid hard foreign key constraints between separate bounded contexts if it creates tight coupling. Use polymorphic relationships or soft references where appropriate to maintain module independence.

## 3. Mandatory Use of Soft Deletes

Data integrity and historical auditing are paramount.
- **Enforcement**: Soft deletes are MANDATORY on all core and modular tables (e.g., Users, Transactions, Projects, Leads, etc.) to maintain historical integrity and prevent accidental data loss.
- **Implementation**: Always use the `Illuminate\Database\Eloquent\SoftDeletes` trait in your models and add `$table->softDeletes()` in your migrations.
- **Permanent Deletion**: Hard deletes should only be used in specific, well-justified cleanup jobs or temporary tables.

## 4. Spatie Model States

We use `spatie/laravel-model-states` to handle complex state machines (e.g., Onboarding journeys, KYC processes, Financial workflows).
- **Definition**: Define states explicitly as classes rather than using simple string columns or enums when the state transitions contain business logic or specific rules.
- **Transitions**: Define allowed transitions within the model to prevent invalid state changes.
- **Usage**: Use state classes to handle state-specific logic, keeping the model and service layer clean.

## 5. Polymorphic Relationships

To maintain flexibility and decouple modules, heavily utilize polymorphic relationships.
- **Use Cases**: Comments, Attachments, Tags, Activity Logs, Metadata, etc.
- **Implementation**: Use Laravel's `morphTo`, `morphMany`, etc. This allows a single table (e.g., `comments`) to be associated with various models (e.g., `Lead`, `Ticket`, `ProjectTask`) without altering the comments schema.
- **Enforcement**: Always prefer polymorphic relationships over creating multiple similar pivot tables or adding numerous nullable foreign keys to a single table.
