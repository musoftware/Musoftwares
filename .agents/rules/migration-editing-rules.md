# Rule: Migration Editing and Data Transfer

## Problem Statement
When modifying database schemas, it is crucial to manage migrations properly depending on their location. Module schemas should be consolidated, whereas the core Laravel migrations must remain immutable to preserve the system's history and ensure safe deployments. Furthermore, data migration from legacy systems should be encapsulated in module-specific migrations to maintain logical boundaries.

## Rules & Guidelines

### 1. Editing Module Migrations
- If you need to modify a table schema that was created by a migration located within a **Module** (e.g., `Modules/{ModuleName}/Database/Migrations/`), you must **edit the original migration file directly**.
- **Do not** create a new "add_column_to_table" migration for tables that belong to a module. Modify the existing module migration file instead.

### 2. Editing Main Laravel Migrations
- If you need to modify tables or columns defined in the **Main Laravel Migrations** (`database/migrations/`), **never edit the original file**.
- Instead, **create a new migration** (e.g., `php artisan make:migration ...`) to implement the changes you want. This preserves the core framework and base system schema history.

### 3. Migrating Data from Legacy Systems
- When writing migrations that transfer, migrate, or seed data from legacy tables (like old services) into new structures, write these data transfer migrations **inside the relevant Module** (e.g., `Modules/{ModuleName}/Database/Migrations/`).
- Do not place legacy data migration scripts or data transfer logic in the main `database/migrations` directory.
