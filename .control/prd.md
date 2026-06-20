# Product Requirements Document (PRD)

## 1. Project Overview
Musoftwares is an enterprise-grade ecosystem combining an ERP, CRM, Client Portal, Admin Panel, Tools Marketplace, and various domain-specific modules (Freelance, Booking, AffiliatePos, GoldSavers, SMS Gateway, etc.). Built on a robust Laravel 12 + React 18 (Inertia.js) stack, the platform strictly adheres to a modular, thin-architecture design, enforcing mobile parity, premium UX using Shadcn UI, and a strict multi-currency financial engine.

## 2. Core Architectural & UI/UX Principles
1. **Premium UI/UX (Shadcn UI)**: Adherence to strict layout enforcement. Native browser prompts/alerts are forbidden. Extreme UX simplicity and mobile parity are required.
2. **ERP Form Requirements**: All Add/Edit forms in the ERP module MUST be full-width, dedicated pages. Modals or sliding sheets are explicitly forbidden for these forms to ensure uncompromised data entry experiences.
3. **i18n Localization**: Zero hardcoded strings. All UI text must be translatable via modular PHP arrays (no global JSON).
4. **Multi-Currency System**: No hardcoded currencies anywhere. The UI must dynamically format and respect the business's or client's active currency.
5. **Tri-Path Validation**: Every interactive route must account for:
   - *Happy Path*: Successful execution.
   - *Edge Cases*: Invalid data, partial data, network drops, unauthorized tenant access.
   - *Security Limits*: Rate limiting, brute force protection, module subscription enforcement.
6. **Module Isolation**: Complete logical separation between domains (e.g., ERP vs. CRM).

---

## 3. Comprehensive Routes & Pages Requirements

### 3.1. Public & Marketing Pages (`/resources/js/Pages/Public/`)
- **Core Info Routes**:
  - `/` -> `Home.jsx`
  - `/company` -> `Company.jsx`
  - `/platforms` -> `Platforms.jsx`
  - `/portfolio` -> `Portfolio.jsx`
  - `/portfolio/{id}` -> `PortfolioShow.jsx`
  - `/pricing` -> `Pricing.jsx`
  - `/solutions` -> `Solutions.jsx`
  - `/services/{id}` -> `WebsiteServiceShow.jsx`
- **Company Sub-pages**: `Company/About.jsx`, `Company/Careers.jsx`, `Company/Contact.jsx`
- **Legal Pages**: `Legal/Cookies.jsx`, `Legal/Privacy.jsx`, `Legal/Terms.jsx`
- **Platform Deep Dives**: `Platforms/Cloud.jsx`, `Platforms/Crm.jsx`, `Platforms/Erp.jsx`
- **Solutions Scopes**: `Solutions/Ecommerce.jsx`, `Solutions/Education.jsx`, `Solutions/Finance.jsx`, `Solutions/Healthcare.jsx`, `Solutions/RealEstate.jsx`
- **Blog**: `Blog/Index.tsx`, `Blog/Show.tsx`
- **UI/UX Edge Cases**: Fast loading (SEO critical), fully responsive layouts. Graceful degradation if assets fail to load.

### 3.2. Authentication (`/resources/js/Pages/Auth/`)
- **Routes**:
  - `/login` -> `Login.tsx`
  - `/register` -> `Register.tsx`
  - `/forgot-password` -> `ForgotPassword.tsx`
  - `/reset-password` -> `ResetPassword.tsx`
  - `/confirm-password` -> `ConfirmPassword.tsx`
  - `/verify-email` -> `VerifyEmail.tsx`
  - `/onboarding` -> `OnboardingWizard.tsx`
- **UI/UX Edge Cases**: Form validation state retention, clear rate-limiting feedback, device-size adaptability for complex onboarding steps. Strict checking for tenant/account initialization during onboarding.

### 3.3. Admin Panel (`/resources/js/Pages/Admin/`)
- **Dashboard & Core**: `Dashboard.tsx`, `HoursCalendar.tsx`
- **Content Management**: `BlogArticles/Create.tsx`, `Edit.tsx`, `Index.tsx`
- **Business Financials**: `Business/BalanceReport.tsx`, `Costs.tsx`, `Income.tsx`, `Reports.tsx`, `RecurringCosts/*`, `RecurringIncome/*`, `RecurringSalaries/*`
- **ERP & CRM Overseer**: `ERP/Index.tsx`, `ERP/Show.tsx`
- **Freelance Management**: `Freelance/Contracts/*`, `Jobs/*`, `Profiles/*`, `Proposals/*`, `Skills/*`
- **Marketplace Management**: `Marketplace/All.tsx`, `Categories.tsx`, `Edit.tsx`, `Pending.tsx`, `Orders/*`, `ServiceLandingPages/*`
- **Users & Clients**: `Users/Index.jsx`, `Show.jsx`, `Create.jsx`, `Edit.jsx`, `Impersonate.jsx`, `FileEditor.jsx`, `Files.jsx`, `Referrals.jsx`, `Reports.jsx`, `CoWork.jsx`
- **Transactions & Wallet**: `Transactions/Index.tsx`, `Cost.tsx`, `Income.tsx`, `Revenue.tsx`, `Transfer.tsx`, `WithdrawRequests/*`
- **System Settings & Configuration**: `Settings/Index.tsx`, `PaymentGateway/*`, `PaymentMethods/*`, `Plans/*`, `PointPackages/*`, `Points/*`, `Resellers/*`, `SerialDevices/*`
- **Support & Comm.**: `Tickets/*`, `Notifications/*`, `Tasks/*`
- **UI/UX Edge Cases**: Extremely complex data tables require virtualization or strict pagination. Bulk action confirmations are mandatory. Impersonation functionality must have a persistent floating exit button.

### 3.4. ERP Module (`/resources/js/Pages/ERP/`)
- **Dashboard & Configuration**: `Dashboard.tsx`, `Onboarding.jsx`, `UpgradePreview.tsx`, `Settings/Smtp.jsx`
- **Client Management**: `Clients/Index.tsx`, `Create.tsx`, `Edit.tsx`, `Show.tsx`, `Files.tsx`, `Notes.tsx`, `Transactions.tsx`
- **Invoices & Billing**: `Invoices/Index.jsx`, `Create.jsx`, `Edit.jsx`, `Show.jsx`, `ClientInvoices/Index.tsx`, `Pay.tsx`
- **Financial Control**: `Expenses/Create.tsx`, `Edit.tsx`, `Debts/*`, `Recurring/*`, `Transactions/Show.tsx`, `Wallet/*`, `Withdrawals/*`
- **Inventory & POS**: `Inventory/Index.tsx`, `Inventory/Products/*`, `Pos/Index.tsx`
- **HR & Team**: `Payroll/Index.tsx`, `Team/Login.tsx`, `Members.tsx`
- **Project Management**: `Projects/Index.tsx`, `Create.tsx`, `Edit.tsx`, `Show.tsx`, `Tasks/*`, `Tickets/*`
- **Multi-Branching**: `MultiBranch/Dashboard.tsx`, `Management.tsx`, `TransferCenter.tsx`
- **UI/UX Edge Cases (CRITICAL)**: **Full-width pages ONLY for creation and editing forms**. Forms handling thousands of products or nested line items must not lock the UI thread. Subscription gating must instantly render `UpgradePreview.tsx` if access is denied.

### 3.5. CRM Module (`/resources/js/Pages/CRM/`)
- **Dashboard & Workspaces**: `Dashboard.tsx`, `Workspaces/Index.tsx`, `CollectorDashboard.tsx`, `ManagerDashboard.tsx`, `MarketingDashboard.tsx`, `SupportDashboard.tsx`, `TelesalesDashboard.tsx`
- **Lead & Customer Management**: `Leads/Index.tsx`, `Customers/Index.tsx`, `Pipelines/Index.tsx`
- **Campaigns & Sequences**: `Campaigns/*`, `Sequences/*`
- **Configuration & Integrations**: `Settings/Index.tsx`, `Tags/Index.tsx`, `Widgets/*`, `Embeds/*`
- **UI/UX Edge Cases**: Kanban boards (`PipelineBoard.tsx`) must support smooth drag-and-drop. Form widgets (`LeadCaptureForm.jsx`) must be extremely lightweight and resistant to CSS bleed when embedded externally.

### 3.6. Client Portal (`/resources/js/Pages/Client/`)
- **Dashboard & Activity**: `Dashboard.tsx`, `Activity/Index.tsx`, `Notifications/Index.tsx`
- **Financials**: `Billing/Invoices.tsx`, `InvoicePay.tsx`, `Financial/AddBalance.jsx`, `PayoutMethods.jsx`, `Transactions.jsx`, `Withdrawals.jsx`, `WalletTransfer/*`
- **Communication & Support**: `Chat/Index.tsx`, `Messages/Index.jsx`, `Support/Tickets/Index.jsx`
- **Settings & Profile**: `Profile/Edit.tsx`, `Settings/Backup.jsx`, `Subscriptions/Manage.tsx`, `Plans.tsx`, `Kyc/Index.jsx`
- **UI/UX Edge Cases**: Provide frictionless payment flows. WebSockets/SSE for real-time chat updates must degrade gracefully to polling if socket connection fails.

### 3.7. Freelance & iSaaS (`/resources/js/Pages/Freelance/`, `/resources/js/Pages/iSaaS/`)
- **Freelance Core**: `Dashboard.tsx`, `Landing.tsx`, `AboutUs.tsx`, `HowItWorks.tsx`
- **Marketplace Browsing**: `Freelancers/Browse.tsx`, `Jobs/Browse.tsx`
- **Job Management**: `Jobs/Index.tsx`, `Show.tsx`, `Create.jsx`, `Edit.jsx`, `MyJobs.jsx`
- **Contracts & Proposals**: `Contracts/*`, `Proposals/*`, `iSaaS/Contracts/*`, `iSaaS/Proposals/*`
- **UI/UX Edge Cases**: Complex multi-step proposal submission. Escrow UI elements must clearly indicate hold states to prevent trust issues.

### 3.8. Tools & WebTools (`/resources/js/Pages/Tools/`, `/resources/js/Pages/WebTools/`)
- **Tool Runners (Examples)**: `B2BProspectorRunner.tsx`, `FacebookExtractorRunner.tsx`, `SnapDownloaderRunner.tsx`, `WhatsAppSenderRunner.tsx`, `WaAiAgentRunner.tsx`, etc.
- **Tools Portal**: `Explore.tsx`, `Downloads.tsx`, `MyLicenses.tsx`, `Billing.tsx`, `Runner.tsx`, `RuntimeConnect.tsx`
- **WebTools (Financial & Utility)**: `Calculator.tsx`, `GoldSaver.tsx`, `WithdrawInstapay.tsx`, `JsObfuscator.tsx`, `CoordinatesConverter.tsx`
- **UI/UX Edge Cases**: WebTools and Runners must provide highly responsive progress indicators (Spinners/Progress bars) as many tasks are long-running processes or rely on external API delays. Strict validation against timeouts and clear visibility into Background Task states.

### 3.9. External Modules & Addons
- **Marketplace**: `Marketplace/Dashboard.tsx`, `Browse.tsx`, `Orders/*`, `Seller/*`
- **Booking System**: `Booking/Dashboard.tsx`, `Appointments.tsx`, `Create.tsx`, `Providers.tsx`, `Checkout.tsx`, `Success.tsx`
- **Booking Rules & SmartSlots**: `BookingRules/*`, `BookingSmartSlots/*`
- **Affiliate POS**: `AffiliatePos/Admin/*`, `AffiliatePos/Affiliate/*`, `AffiliatePos/POS/*`
- **SMS Gateway**: `SmsPaymentGateway/Dashboard`, `ApiKeys.tsx`, `CheckoutSessions.tsx`, `Devices.tsx`, `Webhooks.tsx`, `Transactions.tsx`
- **GoldSavers**: `GoldSavers/Dashboard.tsx`, `Analytics/*`, `Market/*`, `Wallets/*`
- **Root Level**: `Error.tsx` (Global Fallback), `Welcome.tsx`, `TextPaymentGateway.tsx`, `PWA/InstallGuide.tsx`, `Runtime/Download.tsx`

---

## 4. Self-Grilling & Edge Case Resolutions

**Q1: What happens if a user accesses an ERP page without an active subscription?**
- **Resolution**: The system must intercept the request via middleware and seamlessly render `ERP/UpgradePreview.tsx` or a 403 Forbidden page natively integrated with Shadcn UI. Partial content loads must be strictly blocked to prevent data leaks.

**Q2: How are large datasets handled in CRM Kanban and ERP tables?**
- **Resolution**: CRM Pipelines (`PipelineBoard.tsx`) must paginate columns or implement virtual scrolling. ERP Inventory tables must utilize backend-driven pagination and search to prevent DOM lag on mobile devices.

**Q3: Are there modals in the ERP?**
- **Resolution**: *No modals or sliding sheets for Add/Edit forms.* All creations (e.g., `ERP/Clients/Create.tsx`, `ERP/Expenses/Edit.tsx`) are dedicated full-page routes to adhere to `context_skills.md` strict form requirements. 

**Q4: How does multi-currency reflect on the frontend?**
- **Resolution**: Currencies are never hardcoded as `$`. The system must pass the user's/tenant's active currency ISO/Symbol via Inertia's shared props. Every monetary value in components like `Admin/Transactions/TransactionUserCard.tsx` and `Client/Financial/Transactions.jsx` must pipe through a global currency formatter utility.

**Q5: What is the fail-safe for Websocket/Runtime disconnects in the Tools runner pages?**
- **Resolution**: Runners like `WhatsAppSenderRunner.tsx` must implement a robust reconnect backoff strategy. If disconnected, UI must display a clear non-intrusive banner indicating "Reconnecting to Runtime..." rather than failing silently.

**Q6: How are destructive actions handled in the Admin and Client portals?**
- **Resolution**: Deletions (e.g., `Client/Profile/Partials/DeleteUserForm.tsx` or Admin user deletes) require explicit confirmation dialogs (typing the name of the resource or a strict Yes/No Shadcn alert dialog) to prevent accidental data loss.

---
*End of PRD. This document serves as the master blueprint for all active frontend implementations and routes.*
