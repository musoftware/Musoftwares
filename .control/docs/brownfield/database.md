# Database Layer & Schema Architecture

## 1. Overview
The application utilizes a **Laravel 12** backend with a modular monolith architecture (via `nwidart/laravel-modules`). 
The data layer is built on top of Laravel's **Eloquent ORM** and relies heavily on Laravel's standard migration system. The database is highly decoupled, where core functionalities live in the root `app/Models` and `database/migrations` folders, while domain-specific functionalities are segregated into over 15 distinct `Modules`.

## 2. Database Engines & Configuration
According to `config/database.php`, the platform is database-agnostic but primarily configured to handle relational databases:
- **Supported Engines**: SQLite (default fallback), MySQL, MariaDB, PostgreSQL, and SQL Server.
- **Legacy Connections**: There is a specific `old_mysql` connection configured (pointing to `oldmusoftware`), implying an ongoing or historical migration from a legacy system.
- **Caching & Queues**: Redis is configured for caching, sessions, and queue management using the `phpredis` client.

## 3. Core Schema (Root Application)
The core schema encompasses over 130 models (`app/Models`) and around 300 migrations (`database/migrations`), covering the essential operational data:

### **User Management, Auth & Security**
- **Models**: `User`, `Role`, `Permission`, `AdminSettings`, `BlockedIp`, `DeviceToken`, `UserCredential`.
- **Patterns**: Integrates with `Spatie\Permission` for Role-Based Access Control (RBAC). The `User` model is a massive God object handling KYC verification, workspace settings, referrals, onboarding tracking, and balances.

### **Financials, Billing & Wallets**
- **Models**: `Invoice`, `InvoiceItem`, `Transaction`, `CostTransaction`, `Currency`, `CurrenciesExchange`, `Coupon`, `PaymentOrder`, `Voucher`, `Wallet`, `Earning`, `RecurringCost`, `RecurringIncome`, `RecurringSalary`.
- **Patterns**: High-fidelity financial tracking. It calculates client and business balances dynamically and supports scheduled payments, cost accruals, and multi-currency conversions using `CurrenciesExchange`.

### **Project & Task Management**
- **Models**: `Project`, `Task`, `Todo`, `TodoAudio`, `TodoChecklistItem`, `TodoImage`, `KanbanTask`.
- **Patterns**: Standard task delegation, incorporating audio and image attachments for Todos.

### **CRM, Communication & Support**
- **Models**: `Ticket`, `TicketCannedResponse`, `Conversation`, `Message`, `Review`, `NotificationCampaign`, `CoworkerMessage`.
- **Patterns**: Chat system using `Conversation` and polymorphic `Message` (handling text, voice, images, files).

### **Background Tasks & Automations**
- **Models**: `BackgroundTask`, `AutomationRule`, `IncomingWebhook`.
- **Patterns**: Models to store and trigger background job states and incoming webhook payloads.

## 4. Modular Schema (`Modules/` Directory)
The business logic is partitioned into standalone modules. Most of these modules have their own `Models/` and `Database/Migrations/` directories.

**Key Modules & Data Domains**:
- **AffiliatePos**: Affiliate and Point of Sale data.
- **Booking**: Extensive scheduling and booking system. Contains nested feature-level models (e.g., `BookingPriority`, `BookingSmartSlots`, `GcalSync`, `GroupSessions`, `WhiteLabel`).
- **CRM**: Advanced Customer Relationship Management entities.
- **ERP**: Enterprise Resource Planning entities (e.g., Attendance Logs, Leave Requests, Payroll). Includes a `Tenant` model, suggesting multi-tenancy capabilities within the ERP scope.
- **Fbmb**: Facebook/Meta Business integrations.
- **Freelance**: `FreelanceProfile`, `Skill`, `UserSkill`.
- **GoldSavers**: Features like `LivePrices` and tracking.
- **Marketplace**: Service/Product listings, and orders.
- **PaymentGateway / SmsPaymentGateway**: Payment routing and tracking integrations.
- **PasswordSync**, **Tools**, **WebTools**, **WrittenCoursesEngine**.

## 5. ORM Usage & Patterns
The codebase extensively leverages standard **Eloquent ORM** paradigms with modern Laravel 11/12 practices:

- **Inheritance & Traits**: Models extend `Illuminate\Database\Eloquent\Model` (or `Authenticatable` for `User`). Heavy use of traits like `HasApiTokens`, `HasFactory`, `Notifiable`, `Searchable` (Laravel Scout with Meilisearch), and custom traits like `IsPlatformClient`.
- **Spatie Packages**: Usage of `spatie/laravel-model-states` for finite state machines on models, and `spatie/laravel-permission` for roles.
- **Accessors & Mutators**: Uses Laravel's modern `casts(): array` method. Employs `protected $appends` to expose calculated attributes (e.g., `avatar_url`) in JSON payloads.
- **Relationships**: Rich use of `HasMany`, `BelongsTo`, and `BelongsToMany`. The `User` model acts as a central hub, containing over 20+ relationship definitions pointing to Core and Modular models.
- **Fat Models**: Some models, specifically `User`, contain substantial business logic (e.g., `locked_balance()`, `available_balance()`, `try_pay_unpaid_invoices()`), acting as domain aggregates.

## 6. Migrations
- Standard Laravel Schema builder.
- Iterative database changes are heavily tracked, indicating a long-lived project.
- A significant number of migrations from mid-2026 indicate a major architectural shift, adding tables for `ecosystem_core_tables`, `erp_payroll_and_time_tables`, `automation_rules`, and upgrading to `spatie_permissions`.
- Soft Deletes are used across critical tables (Invoices, Users, Cost Transactions) to maintain audit trails.
