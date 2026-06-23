# Musoftwares Project Specification

## 1. Project Overview
Musoftwares is a comprehensive, modular, multi-tenant Business Management Platform that serves as a SaaS, ERP, CRM, and Marketplace. It is designed to handle core business operations, financial transactions, project management, and specialized domain workflows in a single deployable repository.

## 2. Architecture
The application follows a **Modular Monolith** architecture powered by `nwidart/laravel-modules`. This allows the system to remain cohesive while physically grouping domain logic into distinct bounded contexts (e.g., Core, ERP, CRM, Billing, Booking, Marketplace, Fbmb, Freelance).

The frontend operates as a modern Single-Page Application (SPA) that seamlessly integrates with the Laravel backend using Inertia.js, removing the need for a standalone API layer for the core web application.

## 3. Required Technologies

### Backend
- **Framework:** Laravel ^12.0
- **Language:** PHP ^8.2
- **Authentication & Security:** Laravel Sanctum ^4.3, Laravel Breeze ^2.4, Laravel Socialite ^5.27
- **Authorization:** `spatie/laravel-permission` ^6.10 || ^7.4
- **State Machines:** `spatie/laravel-model-states` ^2.12 || ^3.8
- **Real-Time Communication:** Laravel Reverb (via Laravel Echo & Pusher JS)
- **Search Engine:** Laravel Scout ^11.2 (Meilisearch)
- **Database ORM:** Eloquent ORM (MySQL, PostgreSQL, SQLite, etc.)
- **Caching & Queues:** Redis (phpredis client)
- **Key Packages:** `barryvdh/laravel-dompdf` (PDF Generation), `laravel-notification-channels/fcm`, `league/flysystem-aws-s3-v3`

### Frontend
- **Core:** React ^18.2.0
- **Bridging:** Inertia.js ^2.0
- **Language:** TypeScript ^5.0.2
- **Build Tool:** Vite ^7.0.7 (with aggressive manual chunking)
- **Styling:** Tailwind CSS v4 (`@tailwindcss/vite`), `lightningcss`, `clsx`, `tailwind-merge`
- **UI Architecture:** Shadcn UI (`base-nova` style) with Radix UI primitives
- **Icons:** Lucide React
- **State Management:** Zustand ^5.0.14 (client-side), Inertia.js (server-driven state), React Context
- **Animation & Motion:** GSAP ^3.15.0, Framer Motion ^12.40.0
- **Data Visualization:** Recharts ^3.8.1, React Flow ^11.11.4
- **Utility:** `date-fns`, `xlsx`, `dompurify`
- **Specialized Inputs:** Monaco Editor, React Dropzone

### Testing & Quality
- **Code Quality:** Larastan ^3.10 (PHP), ESLint ^8.57.0 (JS/TS), Prettier ^3.3.0
- **Backend Testing:** Pest ^3.8 with Laravel Plugin, Mockery, PHPUnit ^11.5.3
- **Frontend / Unit Testing:** Vitest ^4.1.6, React Testing Library
- **E2E Testing:** Playwright ^1.60.0

## 4. Database Schema
The database uses standard Laravel Migrations and Eloquent models. It is heavily decoupled:
- **Core Schema:** Roots in `app/Models` and `database/migrations` handling User Management, Financials/Wallets, Task Management, CRM/Support, and Automations.
- **Modular Schema:** Domain-specific tables and models reside within `Modules/{ModuleName}/Models` and `Modules/{ModuleName}/Database/Migrations`.
- **Patterns:** Heavy use of traits, Spatie packages, accessors/mutators, and polymorphic relationships. Soft deletes are used across critical tables.

## 5. Services & Business Logic
The `app/Services/` directory centralizes core business rules, keeping controllers thin. 
- **Financial & Billing Services:** Handling wallets, subscriptions, multi-currency conversions, and dynamic pricing.
- **Operations & Tenant Services:** Workspace data isolation, activity logging, and system configurations.
- **Marketplace & Product Services:** Order fulfillment, serial keys generation.

## 6. Main User Workflows
1. **Onboarding & Identity:** Registration, KYC verification, tenant setup, role assignment.
2. **Financial & Billing:** Wallet top-ups, SaaS subscriptions, recurring invoices, dual-currency processing.
3. **Project & Task Execution:** Creating projects, task breakdowns (Kanban), time tracking.
4. **Sales & CRM:** Lead management, marketing campaigns, ticketing, client communications.
5. **Marketplace & Licensing:** Purchasing software, serial key generation/binding, license management.


### New Feature Request
Audit full ERP module and complete full user story how to use it and then build missing gaps , missing features, etc

### New Feature Request
# MASTER ERP EVOLUTION PROMPT

You are operating as a complete ERP Engineering Organization.

Your roles simultaneously include:

* ERP Product Manager
* ERP Business Analyst
* ERP Solution Architect
* Enterprise Software Architect
* Laravel 12 Architect
* Filament 4 Architect
* Senior Backend Engineer
* Senior Database Architect
* QA Lead
* Technical Writer
* SaaS Architect

Your mission is to transform the existing ERP codebase into a complete enterprise-grade ERP platform comparable to:

* Odoo Enterprise
* ERPNext
* Microsoft Dynamics 365 Business Central
* Oracle NetSuite
* SAP Business One

You are not building a demo.

You are building a production-ready ERP SaaS platform.

---

# PROJECT CONTEXT

Current ERP already contains:

## CRM

* Clients
* Client Notes
* Client Files
* Client Activities

## Sales

* Contracts
* Invoices
* POS

## Inventory

* Products
* Categories
* Stock Logs
* Product Variants
* Branch Transfers

## HR

* Employees
* Attendance
* Leave Requests
* Payroll Contracts
* Payslips

## Operations

* Projects
* Tasks
* Todo Lists
* Timers
* Support Tickets

## Finance

* Expenses
* Wallets
* Transactions
* Debts
* Recurring Billing

## SaaS Infrastructure

* Tenants
* Subscriptions
* Feature Flags
* Addons
* User Roles
* Permissions

---

# AUDIT FINDINGS

The current ERP is incomplete.

The following critical domains are missing or partially implemented:

## Procurement

* Suppliers
* Supplier Contacts
* Purchase Requests
* Purchase Orders
* Purchase Order Items
* Goods Receipt Notes
* Vendor Bills
* Accounts Payable

## Accounting

* Chart Of Accounts
* General Ledger
* Journal Entries
* Journal Lines
* Accounting Periods
* Fiscal Years
* Trial Balance
* Balance Sheet
* Profit & Loss
* Bank Accounts
* Bank Reconciliation

## Warehouse

* Warehouses
* Warehouse Zones
* Warehouse Bins
* Stock Transfers
* Stock Reservations
* Stock Adjustments
* Inventory Counts

## Tax Engine

* Tax Rates
* Tax Groups
* Tax Rules
* Tax Calculators
* Tax Reporting

## Asset Management

* Asset Categories
* Fixed Assets
* Depreciation Schedules
* Asset Transfers
* Asset Disposal

## Approval Engine

* Workflow Definitions
* Workflow Steps
* Approval Requests
* Approval Actions
* Escalations

## Calendar

* Events
* Meetings
* Reminders
* Scheduling

## OCR

* OCR Documents
* OCR Jobs
* OCR Invoice Extraction
* OCR Purchase Bill Recognition

## Sales Enhancements

* Quotations
* Estimates
* Sales Orders

## Manufacturing (Future Ready)

* Bill Of Materials
* Production Orders
* Work Centers
* Routing

---

# CORE ERP PRINCIPLES

Every ERP operation must be traceable.

Every ERP operation must be auditable.

Every ERP operation must be tenant isolated.

Every ERP operation must be event driven.

Every ERP operation must support enterprise scale.

No direct business logic inside controllers.

No duplicated logic.

No hidden calculations.

No orphan records.

No manual synchronization.

No hardcoded permissions.

No hardcoded workflows.

---

# ARCHITECTURE REQUIREMENTS

Use:

* Laravel 12
* PHP 8.4
* Filament 4
* MySQL
* Redis
* Queues
* Events
* Notifications
* Policies
* UUID Primary Keys
* Soft Deletes
* Audit Logs

Architecture Patterns:

* Domain Driven Design
* Service Layer
* Repository Pattern
* Action Classes
* DTOs
* Event Driven Architecture
* CQRS where appropriate

Folder Structure:

Domain/
Application/
Infrastructure/
Presentation/

Keep domains isolated.

---

# MULTI TENANT REQUIREMENTS

Every business record must belong to a tenant.

Examples:

tenant_id required on:

* suppliers
* purchase_orders
* warehouses
* assets
* journal_entries
* tax_rules
* approvals

Prevent tenant data leakage.

Implement tenant scopes.

Implement tenant policies.

Implement tenant-aware caching.

---

# ACCOUNTING RULES

Accounting is the financial source of truth.

Every financial operation must generate journal entries.

Examples:

Invoice Created
→ Journal Entry

Invoice Paid
→ Journal Entry

Expense Recorded
→ Journal Entry

Vendor Bill Approved
→ Journal Entry

Asset Purchased
→ Journal Entry

Asset Disposal
→ Journal Entry

Tax Liability
→ Journal Entry

No transaction may bypass accounting.

---

# INVENTORY RULES

Inventory is quantity based and valuation based.

Every inventory change creates:

* Stock Movement
* Audit Entry

Examples:

Purchase Receipt
→ Increase Stock

Sales Shipment
→ Decrease Stock

Transfer
→ Source Decrease
→ Destination Increase

Adjustment
→ Adjustment Record

Inventory must support:

* FIFO
* Average Cost

Design valuation engine accordingly.

---

# PROCUREMENT RULES

Workflow:

Purchase Request
→ Approval
→ Purchase Order
→ Approval
→ Goods Receipt
→ Vendor Bill
→ Payment
→ Closed

Support:

* Partial Receipts
* Partial Billing
* Multiple Approvals
* Supplier Performance Metrics

---

# SALES RULES

Workflow:

Quotation
→ Approved
→ Sales Order
→ Invoice
→ Payment

Support:

* Partial Invoicing
* Multiple Tax Rules
* Discounts
* Multi Currency

---

# ASSET MANAGEMENT RULES

Workflow:

Purchase Asset
→ Capitalize
→ Depreciate
→ Transfer
→ Dispose

Support:

* Straight Line Depreciation
* Declining Balance
* Manual Adjustment

Every depreciation run must generate journal entries.

---

# APPROVAL ENGINE RULES

Approval engine must be generic.

Must work with:

* Purchase Orders
* Vendor Bills
* Expenses
* Contracts
* Assets
* Leave Requests
* Custom Modules

Support:

* Single Step Approval
* Multi Step Approval
* Parallel Approval
* Conditional Approval
* Escalation Rules

No hardcoded approval logic.

All workflows configurable from admin panel.

---

# OCR RULES

OCR engine should support:

* Purchase Invoices
* Vendor Bills
* Receipts
* Expense Documents

Extract:

* Vendor
* Invoice Number
* Date
* Tax Amount
* Total Amount
* Line Items

Allow human review before posting.

---

# CALENDAR RULES

Calendar integrates with:

* Tasks
* Projects
* Meetings
* Leave Requests
* Approvals

Support:

* Daily
* Weekly
* Monthly
* Agenda Views

---

# REQUIRED IMPLEMENTATION OUTPUT

For EVERY module generate:

## Business Analysis

* Purpose
* Scope
* User Roles
* Permissions
* User Stories
* Acceptance Criteria
* Business Scenarios

---

## Database Design

Generate:

* Tables
* Columns
* Indexes
* Foreign Keys
* Constraints
* Enums

Generate complete ERD.

---

## Laravel Implementation

Generate:

* Migrations
* Models
* DTOs
* Repositories
* Services
* Actions
* Policies
* Events
* Listeners
* Notifications
* Jobs
* Seeders
* Factories

Production ready.

No pseudocode.

---

## API Layer

Generate:

* Controllers
* Form Requests
* API Resources
* Routes
* OpenAPI Documentation

---

## Filament Implementation

Generate:

* Resources
* Pages
* Widgets
* Relation Managers
* Tables
* Forms
* Filters
* Bulk Actions

---

## Testing

Generate:

* Unit Tests
* Feature Tests
* Integration Tests
* Pest Tests

Minimum enterprise-grade coverage.

---

## Reporting

Generate reports for each module.

Examples:

* Supplier Performance
* Purchase Analysis
* Aging Payables
* Inventory Valuation
* Asset Register
* Depreciation Report
* Trial Balance
* P&L
* Balance Sheet

---

## Dashboards

Generate KPI widgets.

---

# QUALITY GATES

A module is NOT complete unless all are implemented:

✓ Database

✓ Models

✓ Services

✓ Repositories

✓ Policies

✓ Events

✓ Listeners

✓ Notifications

✓ APIs

✓ Filament Resources

✓ Tests

✓ Reports

✓ Dashboard Widgets

✓ Audit Logs

✓ Tenant Isolation

✓ Documentation

---

# EXECUTION MODE

Before generating code:

1. Analyze existing codebase.
2. Detect existing modules.
3. Detect duplicates.
4. Detect conflicts.
5. Detect missing dependencies.
6. Detect missing migrations.
7. Detect missing permissions.
8. Detect missing events.
9. Detect missing policies.

Then create an implementation plan.

Then execute module by module.

Never simplify.

Never skip architecture.

Never generate placeholder code.

Never generate TODO comments.

Never stop at CRUD.

Build complete ERP business workflows.

Continue until the ERP reaches enterprise-grade completeness.