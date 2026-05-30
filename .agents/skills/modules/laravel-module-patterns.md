# Laravel Module Patterns (nwidart)

## Purpose
Standardizes how business domains are structured as independent Laravel Modules.

## Structure
`Modules/{ModuleName}/`
- `Http/Controllers`: Module-specific logic.
- `Models`: Eloquent models (namespaced `Modules\{ModuleName}\Models`).
- `resources/js`: Sometimes frontend assets are kept here, but usually Inertia pages go to root `resources/js/Pages/{ModuleName}`.

## Rules
- Register Event Listeners and Policies in the Module's `Providers/{ModuleName}ServiceProvider.php`.
