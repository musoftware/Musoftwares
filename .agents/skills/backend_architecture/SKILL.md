---
name: backend_architecture
description: Enforces strict Laravel 12 modular architecture, thin controllers/services, domain isolation, and strict git workflow rules for backend development.
---

# Backend Architecture Guidelines

This skill defines the mandatory architectural constraints and workflows for backend development in the Musoftwares ecosystem.

## 1. Modular Architecture (`nwidart/laravel-modules`)
- **Strict Domain Isolation:** The system is divided into distinct modules (e.g., `ERP`, `CRM`, `Marketplace`, `Freelance`). 
- **No Cross-Domain Leakage:** You must maintain complete logical separation between these domains. Never mix logic or create tight couplings between strictly separated modules.
- **Module Independence:** Each module should encapsulate its own controllers, models, services, events, and routes.

## 2. Thin Architecture
- **Thin Controllers:** Controllers should only handle HTTP request validation, authorization, and returning responses. They must not contain business logic.
- **Thin Services:** Service classes should be focused and adhere to the Single Responsibility Principle. Avoid "fat" architecture and logic bloat. If a service becomes too complex, break it down into smaller, focused action classes or use event-driven patterns.
- **Routing:** Route files (`web.php`, `api.php`) must only define routes and map them to controllers. Strictly forbid placing any business logic, database queries, or complex anonymous functions inside route definition files.

## 3. Strict Git Workflow for AI Agents
**CRITICAL RULE FOR ALL FUTURE AI AGENTS:**
When implementing features or making changes, you MUST follow this strict git workflow:
1. **Branching:** Every new feature, bug fix, or task MUST be developed in its own separate git branch. Never work directly on `main` or `master` unless explicitly instructed otherwise by the user.
2. **Atomic Commits:** Make a commit immediately after every logical modification or step in your implementation plan.
3. **Commit Messages:** Write clear, descriptive commit messages for each change.
4. **No Giant PRs:** Keep branches focused on a single task to ensure easy review and rollback if necessary.

## 4. General Backend Rules
- **Multi-Currency System:** Never hardcode currencies in invoices, expenses, or transactions. The multi-currency system must be used dynamically based on the active tenant or business settings.
- **Tri-Path Validation:** Every interactive route must account for the Happy Path, Edge Cases, and Security Limits.
- **Testing:** Always write unit and feature tests to ensure the stability of the module and adherence to these architectural rules.
