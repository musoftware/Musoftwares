# Musoftwares - Comprehensive Product Requirements Document (PRD)

## 1. Introduction & Objectives
Musoftwares is a modular, multi-tenant Business Management Platform functioning as a comprehensive SaaS, ERP, CRM, and Marketplace. The objective is to unify core business operations, financial transactions, project management, and specialized domain workflows into a single deployable repository using a Modular Monolith architecture.

**ERP Evolution Objective:** 
Transform the existing ERP module into a complete, enterprise-grade ERP SaaS platform comparable to Odoo Enterprise, ERPNext, Microsoft Dynamics 365 Business Central, Oracle NetSuite, and SAP Business One. We are building a production-ready ERP SaaS platform, not a demo.

## 2. Architecture & Tech Stack Summary
- **Backend:** Laravel ^12.0, PHP ^8.4, Filament 4, `nwidart/laravel-modules` (Modular Monolith), Laravel Sanctum, Laravel Breeze, Laravel Socialite, Spatie Permissions/Model States, Laravel Reverb (WebSockets).
- **Frontend:** React ^18.2.0, Inertia.js ^2.0 (SPA bridging), TypeScript ^5.0.2, Vite ^7.0.7, Tailwind CSS v4, Shadcn UI (`base-nova`), Zustand ^5.0.14, Recharts, React Flow, GSAP, Framer Motion.
- **Data Layer:** Eloquent ORM (MySQL/PostgreSQL), Redis (Cache/Queue), Meilisearch (via Scout).
- **Architecture Patterns:** Domain Driven Design (DDD), Service Layer, Repository Pattern, Action Classes, DTOs, Event Driven Architecture, and CQRS where appropriate. Folder Structure: `Domain/`, `Application/`, `Infrastructure/`, `Presentation/`. Domains must be kept isolated.
- **Data Primitives:** UUID Primary Keys, Soft Deletes, Audit Logs.

## 3. Core ERP Principles & Rules

Every ERP operation must be traceable, auditable, tenant-isolated, event-driven, and support enterprise scale. No direct business logic inside controllers, no duplicated logic, no hidden calculations, no orphan records, no manual synchronization, no hardcoded permissions, and no hardcoded workflows.

### 3.1. Multi-Tenant Requirements
Every business record must belong to a tenant. `tenant_id` is required on suppliers, purchase_orders, warehouses, assets, journal_entries, tax_rules, approvals, etc. Prevent tenant data leakage by implementing tenant scopes, tenant policies, and tenant-aware caching.

### 3.2. Accounting Rules
Accounting is the financial source of truth. No transaction may bypass accounting. Every financial operation (Invoice Created, Invoice Paid, Expense Recorded, Vendor Bill Approved, Asset Purchased, Asset Disposal, Tax Liability) must generate journal entries.

### 3.3. Inventory Rules
Inventory is quantity-based and valuation-based. Every inventory change (Purchase Receipt, Sales Shipment, Transfer, Adjustment) creates a Stock Movement and an Audit Entry. Inventory must support FIFO and Average Cost valuation methods.

### 3.4. Procurement & Sales Rules
- **Procurement Workflow:** Purchase Request -> Approval -> Purchase Order -> Approval -> Goods Receipt -> Vendor Bill -> Payment -> Closed. Must support partial receipts, partial billing, multiple approvals, and supplier performance metrics.
- **Sales Workflow:** Quotation -> Approved -> Sales Order -> Invoice -> Payment. Must support partial invoicing, multiple tax rules, discounts, and multi-currency.

### 3.5. Asset Management Rules
- **Workflow:** Purchase Asset -> Capitalize -> Depreciate -> Transfer -> Dispose.
- Must support Straight Line Depreciation, Declining Balance, and Manual Adjustment. Every depreciation run must generate journal entries.

### 3.6. Approval Engine Rules
The approval engine must be generic and work with Purchase Orders, Vendor Bills, Expenses, Contracts, Assets, Leave Requests, and Custom Modules. Must support Single Step, Multi Step, Parallel, and Conditional Approvals, plus Escalation Rules. No hardcoded approval logic; all workflows must be configurable from the admin panel.

### 3.7. OCR & Calendar Rules
- **OCR Engine:** Support Purchase Invoices, Vendor Bills, Receipts, Expense Documents. Extract Vendor, Invoice Number, Date, Tax Amount, Total Amount, and Line Items. Allow human review before posting.
- **Calendar:** Integrates with Tasks, Projects, Meetings, Leave Requests, and Approvals. Support Daily, Weekly, Monthly, and Agenda views.

## 4. Comprehensive Route & Page Checklist

This section acts as a strict checklist for every expected route/page in the application, ensuring no gaps in user flow.

### 4.1. Authentication & Onboarding (Core)
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/login` | Email/password login, Socialite logins (Google, etc.). Clean card layout, centered. GSAP entrance animation. |
| `/register` | Initial account creation. Form validation. |
| `/forgot-password` | Request password reset link. |
| `/reset-password/{token}` | Input new password. |
| `/verify-email` | Prompting user to verify email before proceeding. |
| `/two-factor-challenge` | 2FA code input for enhanced security. |
| `/onboarding/kyc` | Multi-step form for identity validation. Progress bar, file dropzone for documents. |
| `/onboarding/workspace` | Tenant setup, naming the workspace, inviting initial team members. |

### 4.2. Core Dashboard & Settings
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/dashboard` | Main operational overview. Real-time metrics via Reverb. Summary charts (Recharts). |
| `/settings/profile` | User profile management, avatar upload. |
| `/settings/security` | Change password, enable 2FA, view active sessions. |
| `/settings/workspace` | Tenant settings, Spatie role assignment, user invitations. |
| `/notifications` | Centralized notification hub (FCM/DB). Real-time incoming alerts. |

### 4.3. Billing & Financials (Billing Module)
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/billing/wallet` | Wallet balance, transaction history. Dual-Currency UI showing base vs. converted currency. Top-up modal. |
| `/billing/subscriptions` | SaaS subscription plans, current active plan, upgrade/downgrade flows. |
| `/billing/invoices` | List of recurring and past invoices. View/Download PDF (`laravel-dompdf`). |

### 4.4. ERP Module (Operations, HR, Supply Chain, Accounting)
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/erp/dashboard` | ERP-specific real-time operational efficiency metrics. |
| `/erp/departments` | Manage organizational structure. Tree view or list. |
| `/erp/employees` | Employee directory and profiles, including Attendance, Leave Requests, Payroll Contracts, and Payslips. |
| `/erp/cost-centers` | Define and track cost centers. |
| `/erp/assets` | Register physical/digital assets. Asset Categories, Transfers, and Disposal. Grid/List view. Assign to employees/departments. |
| `/erp/assets/{id}/depreciation` | Depreciation schedules and maintenance logs. |
| `/erp/procurement/suppliers` | Suppliers and Supplier Contacts. Vendor management with order history. |
| `/erp/procurement/requests` | Purchase Requests and Purchase Orders (with Order Items). |
| `/erp/procurement/receipts` | Goods Receipt Notes. |
| `/erp/procurement/bills` | Vendor Bills and Accounts Payable. |
| `/erp/warehouse` | Warehouses, Zones, and Bins. |
| `/erp/warehouse/transfers` | Stock Transfers, Stock Reservations, Stock Adjustments, Inventory Counts. |
| `/erp/inventory` | Multi-warehouse inventory tracking. Products, Categories, Stock Logs, Product Variants. Real-time low-stock alerts. |
| `/erp/accounting/chart-of-accounts` | Chart Of Accounts. |
| `/erp/accounting/journals` | Journal Entries and Journal Lines. |
| `/erp/accounting/periods` | Accounting Periods and Fiscal Years. |
| `/erp/accounting/reports` | Trial Balance, Balance Sheet, Profit & Loss. |
| `/erp/accounting/banks` | Bank Accounts and Bank Reconciliation. |
| `/erp/tax` | Tax Rates, Tax Groups, Tax Rules, Tax Calculators, Tax Reporting. |
| `/erp/approvals` | Workflow Definitions, Steps, Approval Requests, Actions, Escalations. |
| `/erp/calendar` | Events, Meetings, Reminders, Scheduling. |
| `/erp/ocr` | OCR Documents, OCR Jobs, Invoice Extraction, Purchase Bill Recognition. |
| `/erp/manufacturing` | Bill Of Materials, Production Orders, Work Centers, Routing (Future Ready). |

### 4.5. Sales & CRM
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/crm/dashboard` | Sales pipeline overview, conversion rates. |
| `/crm/clients` | Clients, Client Notes, Client Files, Client Activities. |
| `/crm/leads` | Lead tracking board (Kanban style via React Flow or Drag-and-Drop). |
| `/crm/campaigns` | Marketing outreach campaigns. Performance metrics. |
| `/crm/tickets` | Customer support ticketing system. Chat-like interface for responses. |
| `/crm/communications` | Centralized messaging center for client interactions. |
| `/sales/quotations` | Quotations and Estimates. |
| `/sales/orders` | Sales Orders. |
| `/sales/contracts` | Contracts. |
| `/sales/invoices` | Invoices. |
| `/sales/pos` | Point of Sale (POS). |

### 4.6. Project & Task Execution
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/projects` | List/Grid of active project workspaces. |
| `/projects/{id}/board` | Kanban task breakdown. Tasks, Todo Lists. Framer Motion for smooth drag-and-drop. |
| `/projects/{id}/time` | Time tracking logs, timer widget. |

### 4.7. Marketplace & Licensing
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/marketplace` | Product catalog. High-end visual cards, filtering. |
| `/marketplace/products/{id}` | Product detail page. Rich text, image galleries. |
| `/marketplace/cart` | Shopping cart drawer or page. |
| `/marketplace/checkout` | Payment processing using Wallet credits or external gateway. |
| `/marketplace/licenses` | Manage purchased software. Serial key generation and binding to hardware/users. |

### 4.8. Booking Module
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `/booking` | Booking dashboard, upcoming appointments. |
| `/booking/calendar` | Interactive calendar for scheduling resources or services. |

### 4.9. Error Pages
| Route / Page | Description & UI/UX Requirements |
|---|---|
| `403 Unauthorized` | High-quality branded error page. Clear action to return home. |
| `404 Not Found` | Friendly "page not found" graphic. Search bar or links to common areas. |
| `500 Server Error` | Apologetic messaging. Auto-retry countdown or support link. |
| `503 Maintenance` | Scheduled maintenance page with estimated return time. |

## 5. UI/UX Design Requirements & Guidelines
- **Premium Aesthetics:** Use curated, harmonious color palettes (base-nova) and modern typography (e.g., Inter, Outfit). Avoid generic colors.
- **Micro-Animations & Motion:** Use Framer Motion and GSAP for route transitions, hover effects, and drag-and-drop interactions to make the app feel alive and responsive.
- **Component System:** Exclusively use Shadcn UI with Radix primitives. Avoid ad-hoc styling. Ensure all interactive elements have focus states and accessibility attributes.
- **Data Heavy Screens:** Use DataTables with sticky headers, pagination, and inline filtering for lists (e.g., ERP Inventory, Employees).
- **Responsive Design:** Ensure the SPA is fully functional on mobile devices using Tailwind's responsive utilities. Hide complex sidebar navigations behind a hamburger menu on small screens.

## 6. Implementation Methodology, Output & Quality Gates

### 6.1. Required Implementation Output
For EVERY module generate the following:
- **Business Analysis:** Purpose, Scope, User Roles, Permissions, User Stories, Acceptance Criteria, Business Scenarios.
- **Database Design:** Tables, Columns, Indexes, Foreign Keys, Constraints, Enums. Complete ERD.
- **Laravel Implementation (Production Ready, No Pseudocode):** Migrations, Models, DTOs, Repositories, Services, Actions, Policies, Events, Listeners, Notifications, Jobs, Seeders, Factories.
- **API Layer:** Controllers, Form Requests, API Resources, Routes, OpenAPI Documentation.
- **Filament Implementation:** Resources, Pages, Widgets, Relation Managers, Tables, Forms, Filters, Bulk Actions.
- **Testing (Minimum Enterprise-Grade Coverage):** Unit Tests, Feature Tests, Integration Tests, Pest Tests.
- **Reporting & Dashboards:** Reports for each module (e.g., Supplier Performance, Trial Balance, P&L) and KPI widgets.

### 6.2. Quality Gates
A module is NOT complete unless all are implemented:
- [ ] Database
- [ ] Models
- [ ] Services
- [ ] Repositories
- [ ] Policies
- [ ] Events
- [ ] Listeners
- [ ] Notifications
- [ ] APIs
- [ ] Filament Resources
- [ ] Tests
- [ ] Reports
- [ ] Dashboard Widgets
- [ ] Audit Logs
- [ ] Tenant Isolation
- [ ] Documentation

### 6.3. Execution Mode
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
Then create an implementation plan and execute module by module. Never simplify, never skip architecture, never generate placeholder code or TODO comments, and never stop at CRUD. Build complete ERP business workflows and continue until the ERP reaches enterprise-grade completeness.

## 7. Missing Gaps & Edge Cases to Address
- **ERP Integration:** Ensure ERP financial transactions perfectly sync with the Core wallet system without race conditions.
- **Multi-Currency:** Dual-currency processing must be explicitly visible on all pricing and invoicing screens. Exchange rates must be cached but frequently updated.
- **Tenant Data Isolation:** Global scopes must be strictly applied across all modules so tenant data never leaks.
- **Soft Deletes Context:** Ensure related models (e.g., deleted employee) still render correctly in historical records (e.g., past payslips) without throwing 404s or null pointer exceptions.
- **Inertia Payload Size:** With a modular monolith, Inertia responses must be heavily optimized using partial reloads and lazy evaluation to avoid sending massive, unnecessary JSON payloads.
