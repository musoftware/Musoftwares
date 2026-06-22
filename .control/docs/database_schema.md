# Database Schema & Data Layer

Musoftwares uses standard Laravel Migrations and Eloquent models for its database schema, designed with heavy decoupling.

## Schema Organization

### 1. Core Schema
Located in:
- `app/Models`
- `database/migrations`

**Responsibilities:**
- User Management
- Financials/Wallets
- Task Management
- CRM/Support
- Automations

### 2. Modular Schema
Located in:
- `Modules/{ModuleName}/Models`
- `Modules/{ModuleName}/Database/Migrations`

**Responsibilities:**
- Domain-specific tables and models (e.g., ERP, Booking, Marketplace tables).

## Architectural Patterns
- **Traits & Spatie Packages:** Heavy reliance on traits and Spatie packages (e.g., laravel-permission, laravel-model-states) to keep models clean.
- **Accessors/Mutators:** Utilized for data formatting and virtual attributes.
- **Polymorphic Relationships:** Used extensively for flexible, reusable relationships across domains.
- **Soft Deletes:** Enforced across critical tables to prevent accidental data loss.
