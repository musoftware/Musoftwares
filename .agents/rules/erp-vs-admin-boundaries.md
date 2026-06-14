# Rule: ERP vs Admin Boundaries

## Problem Statement
There is a clear architectural boundary between the "Admin/Main System" and the "ERP Module". Confusing the two leads to incorrect edits—for example, editing an Admin controller when the user intended to modify an ERP tenant feature, or vice versa. This rule enforces strict separation of contexts based on file paths, users, and functionality to prevent accidental modifications in the wrong scope.

## Rules & Guidelines

### 1. Admin / Main System (Platform Management)
- **Purpose**: Used by the platform owners (Super Admins, Moderators) to manage the overall platform, users, global subscriptions, marketplace, addons, and support tickets.
- **File Locations**:
  - Backend Controllers: `app/Http/Controllers/Admin/`
  - Frontend Views (React/Inertia): `resources/js/Pages/Admin/`
  - Routes: `routes/admin.php` or `routes/web.php` (for global non-module specific routes).
- **Target Audience**: Platform Administrators.
- **Key Entities**: Users, Global Subscriptions, Support Tickets, Marketplace Plugins.

### 2. ERP Module (Tenant Business Management)
- **Purpose**: Used by the end Users (Business Owners/Tenants) to manage their own clients, projects, invoices, expenses, and internal business operations.
- **File Locations**:
  - Backend Controllers: `Modules/ERP/Http/Controllers/`
  - Frontend Views (React/Inertia): `Modules/ERP/resources/js/Pages/`
  - Routes: `Modules/ERP/routes/`
  - Models: `Modules/ERP/Models/`
- **Target Audience**: Business Owners (Tenants) and their employees.
- **Key Entities**: ERP Clients, Projects, Invoices, Transactions, Expenses.

### 3. Context Isolation Rules
- **Strict Boundary**: **NEVER** modify a file in `Modules/ERP/` when the user asks to change something in the "Admin Dashboard".
- **Strict Boundary**: **NEVER** modify a file in `app/Http/Controllers/Admin/` or `resources/js/Pages/Admin/` when the user asks to change something in the "ERP" or for user-level "Clients/Projects/Invoices" unless specifically requested.
- **Ambiguity Check**: If the user request is ambiguous (e.g., "fix the dashboard" or "update the invoice page"), use context clues (like currently open files or error logs) or ask for clarification before making any code changes.



---
