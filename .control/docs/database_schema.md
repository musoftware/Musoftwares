# Database Schema Design

The application utilizes standard Laravel Migrations and Eloquent models with a heavily decoupled approach to support the modular monolith architecture.

## Organization
1. **Core Schema:** 
   - Rooted in `app/Models/` and `database/migrations/`.
   - Responsibilities: User Management, Financials/Wallets, Task Management, CRM/Support, Automations.
2. **Modular Schema:** 
   - Located in `Modules/{ModuleName}/Models/` and `Modules/{ModuleName}/Database/Migrations/`.
   - Responsibilities: Strictly domain-specific tables isolated to their respective bounded contexts.

## Design Patterns
- **Traits & Packages:** Extensive use of Laravel traits and Spatie packages (e.g., `spatie/laravel-permission`, `spatie/laravel-model-states`).
- **Accessors/Mutators:** Used to handle data transformation consistently at the model level.
- **Polymorphic Relationships:** Utilized heavily to allow flexible associations (e.g., attaching notes, files, or activities to multiple different entity types).
- **Soft Deletes:** Enforced across all critical tables to prevent accidental data loss and maintain historical integrity.
