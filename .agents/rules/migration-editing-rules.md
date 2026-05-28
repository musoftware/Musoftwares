# Rule: Migration Editing and Data Transfer

## Problem Statement
When modifying database schemas, it is an absolute requirement to keep module migrations self-contained and pristine. Creating random `add_column_to_table` migrations inside the main Laravel `database/migrations` directory for tables that belong to a module is strictly forbidden. It pollutes the core schema and breaks the modular architecture.

## Rules & Guidelines

### 1. Module Migrations (STRICT POLICY)
- **Anything related to a Module MUST stay inside the Module.**
- If you need to modify a table schema that belongs to a **Module** (e.g., `Modules/{ModuleName}/Database/Migrations/`), **you MUST edit the original migration file directly.**
- **NEVER** create a new external migration (e.g., `add_x_to_module_table`) inside `database/migrations` or anywhere else. If a column needs to be added, open the original module migration file where the table was created and add the column directly there.

### 2. Main Laravel Migrations
- If you need to modify tables or columns defined in the **Main Laravel Migrations** (`database/migrations/` like `users` or `sessions`), **NEVER edit the original file**.
- For main framework tables, you must **create a new migration** (e.g., `php artisan make:migration ...`) to implement your changes, preserving the core framework history.

### 3. Migrating Legacy Data
- When writing migrations that transfer, migrate, or seed data from legacy systems (like old services) into new structures, write these data transfer migrations **inside the relevant Module** (e.g., `Modules/{ModuleName}/Database/Migrations/`).
- **Do not** place legacy data migration scripts in the main `database/migrations` directory.
