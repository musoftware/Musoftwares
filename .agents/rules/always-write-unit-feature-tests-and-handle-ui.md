# Rule: Always Write Unit/Feature Tests and Handle UI

## Problem Statement
Introducing code changes to models, controllers, or services without test coverage increases regression risk and makes it difficult to verify correct behavior. Additionally, failing to complete the implementation cycle by ignoring frontend updates or omitting related supporting classes (requests, resources, migrations) leaves features broken or half-implemented.

## Rules & Guidelines

### 1. Mandatory Test Coverage
- **Unit Tests**: Write unit tests for custom calculations, status checking helper methods, service class logic, or domain rules that can be run in isolation.
- **Feature Tests**: Write feature tests for any new or modified routes, controller actions, or middleware logic to ensure proper response status, redirected routes, views, database state validation, event triggers, and permission boundaries.
- **Test File Organization**:
  - Keep PHPUnit tests under `Tests/Unit` or `Tests/Feature` matching Laravel conventions.
  - For modules, keep tests within the module's own `Tests` folder (e.g., `Modules/ERP/Tests/Feature` or `Modules/ERP/Tests/Unit`).

### 2. Complete UI Handling
- If a backend logic change has any impact on the user interface, you must update or implement the corresponding frontend views (e.g., React/TSX pages, Blade views).
- Handle page state, validation error rendering, success alerts/toasts, loader indicators, and disabled states on forms.
- Ensure Inertia components receive the correct updated props from controllers.

### 3. Create All Related Artifacts
- Never write code in isolation. If a feature needs a data change, create:
  - **Migrations**: Secure schema updates.
  - **Factories & Seeders**: To assist in manual and automated testing.
  - **Request Classes**: For input validation.
  - **Resource Classes**: For structured API/Inertia data representation.
  - **Translation Keys**: In accordance with the `always-use-translatable-text` rule.



---
