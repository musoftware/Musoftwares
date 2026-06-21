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

## 4. User Role: Admin

This section defines the operational capabilities, oversight functions, and system management controls executed by the "Admin" role to govern the Musoftwares ecosystem.

### 4.1. User Stories
- **US-ADM-01 (Tenant Oversight & Support)**: As an Admin, I must be able to view, impersonate, and manage all registered users and client tenants to provide support, troubleshoot issues, and oversee platform usage without requiring their credentials.
- **US-ADM-02 (Financial Auditing & Reporting)**: As an Admin, I must be able to view aggregated business financials (income, costs, recurring salaries, and profitability) across all modules to assess the overall health of the ecosystem.
- **US-ADM-03 (Marketplace Moderation)**: As an Admin, I must be able to review, approve, reject, or edit pending marketplace services and orders to maintain quality control and manage disputes between freelancers and clients.
- **US-ADM-04 (Content & Marketing Management)**: As an Admin, I must be able to create, edit, and publish blog articles, and manage service landing pages to drive SEO and marketing efforts.
- **US-ADM-05 (System Configuration & Billing)**: As an Admin, I must be able to configure global system settings, payment gateways, subscription plans, and points packages to adapt the platform's offering to changing business needs.
- **US-ADM-06 (Support & Operational Workflows)**: As an Admin, I must be able to manage support tickets, operational tasks, and global system notifications to ensure prompt customer service and internal efficiency.

### 4.2. Edge Cases
- **EC-ADM-01 (Impersonation State Management)**: If an Admin impersonates a user and navigates across multiple tabs, the impersonation state must be consistently maintained or clearly indicated (with a persistent exit button) to prevent accidental actions in the wrong context.
- **EC-ADM-02 (Concurrent Configuration Edits)**: If multiple Admins attempt to edit the same global setting simultaneously, the system must handle the race condition gracefully, either by implementing optimistic locking or alerting the second user.
- **EC-ADM-03 (Accidental System Lockout)**: If an Admin attempts to delete, disable, or demote their own account, the system must prevent this action if they are the last active super-admin to avoid total system lockout.
- **EC-ADM-04 (Large Data Export Timeouts)**: If an Admin requests a massive financial report export spanning years of data, the system must process this asynchronously via a background task and notify the Admin upon completion to prevent PHP/Nginx timeouts.
- **EC-ADM-05 (Destructive Action Safeguards)**: When an Admin initiates a bulk delete on critical resources (e.g., user accounts or transactions), they must be prompted with a strict confirmation dialog (e.g., typing "DELETE") to prevent catastrophic accidental data loss.

### 4.3. Testing Requirements
- **TR-ADM-01 (Impersonation Privilege Boundary)**: Automated tests must ensure that an impersonating Admin can perform user actions but is strictly blocked from irreversible core actions (like deleting the tenant account permanently) that require the actual user's re-authentication.
- **TR-ADM-02 (Audit Logging for Admins)**: Integration tests must verify that every sensitive action performed by an Admin (e.g., changing payment settings, impersonation sessions, refunding a transaction) is securely logged in an immutable audit trail.
- **TR-ADM-03 (Strict Role-Based Access Control)**: Tests must confirm that non-admin users (clients, freelancers, staff) cannot access any `/Admin` routes, and that unauthorized attempts result in a strict 403 Forbidden response.
- **TR-ADM-04 (Bulk Action Integrity & Reporting)**: E2E tests must validate that bulk actions in the Admin panel process all selected items correctly, handle partial failures gracefully (e.g., skipping locked records), and provide an accurate summary report.

---

## 5. User Role: Client

This section defines the autonomous capabilities, financial interactions, and communication workflows executed by the "Client" role within the Client Portal and broader ecosystem.

### 5.1. User Stories
- **US-CLI-01 (Financial Autonomy & Billing)**: As a Client, I must be able to view, securely pay, and download invoices, as well as manage my wallet balance and payout methods, so I can handle financial obligations smoothly.
- **US-CLI-02 (Communication & Support)**: As a Client, I must be able to communicate via real-time chat and submit support tickets to the business, ensuring my queries are resolved promptly.
- **US-CLI-03 (Service Discovery & Orders)**: As a Client, I must be able to browse the marketplace, review available services, and place or manage orders, enabling me to acquire new solutions easily.
- **US-CLI-04 (Freelance Contracts & Proposals)**: As a Client, I must be able to post jobs, review freelancer proposals, and manage active contracts and escrow releases securely.
- **US-CLI-05 (Profile & Subscription Management)**: As a Client, I must be able to manage my profile details, complete KYC verification, and independently upgrade, downgrade, or cancel my subscription plans.

### 5.2. Edge Cases
- **EC-CLI-01 (Real-Time Disconnection)**: If the WebSocket connection fails during an active chat or payment process, the system must gracefully degrade to polling or display an offline warning without losing draft messages.
- **EC-CLI-02 (Insufficient Wallet Balance)**: When attempting to pay an invoice or transfer funds, if the wallet balance is insufficient, the system must clearly prompt the client to add funds via an external gateway before proceeding.
- **EC-CLI-03 (Simultaneous Invoice Payment)**: If a client and an admin attempt to mark an invoice as paid simultaneously, or if double-clicking the pay button, the system must prevent double-charging using idempotency keys.
- **EC-CLI-04 (Subscription Downgrade Constraints)**: If a client attempts to downgrade a subscription while currently using features exceeding the lower plan's limits, they must be prompted to adjust their usage before the downgrade is accepted.
- **EC-CLI-05 (KYC Verification Delays)**: If a client's KYC verification is pending or rejected, certain financial actions (like large withdrawals) must be strictly locked, with clear feedback on why the action is disabled.

### 5.3. Testing Requirements
- **TR-CLI-01 (Idempotent Payment Handling)**: Automated tests must simulate rapid double-clicks on payment submission buttons to ensure only one transaction is processed and recorded.
- **TR-CLI-02 (Data Isolation Verification)**: Integration tests must guarantee that a Client cannot access, view, or modify invoices, tickets, or profile data belonging to another Client, enforcing strict tenant boundaries.
- **TR-CLI-03 (WebSocket Degradation)**: E2E tests must intentionally block WebSocket connections to verify that the chat UI correctly falls back to long-polling and still delivers messages.
- **TR-CLI-04 (Subscription Enforcement)**: Tests must mock date/time progression to ensure that an expired or downgraded subscription accurately limits access to premium tools and features in the client portal.

---

## 6. User Role: Musoftwares System

This section defines the automated, background, and programmatic capabilities executed by the "Musoftwares System" itself, acting autonomously to maintain platform health, financial accuracy, and data synchronization.

### 6.1. User Stories
- **US-SYS-01 (Automated Billing & Subscriptions)**: As the Musoftwares System, I must automatically process recurring subscription renewals, calculate prorations, and gracefully downgrade tenant access if a payment fails, so that revenue collection and access control are hands-free.
- **US-SYS-02 (Multi-Currency Synchronization)**: As the Musoftwares System, I must periodically fetch exchange rates and accurately compute financial transactions across the dual-currency architecture (Client vs. Business currencies) without rounding errors.
- **US-SYS-03 (Background Task Orchestration)**: As the Musoftwares System, I must orchestrate long-running asynchronous background tasks (e.g., web scrapers, mass SMS dispatch, data imports) via queues, and publish real-time WebSocket status updates to the end user.
- **US-SYS-04 (Event-Driven Automation)**: As the Musoftwares System, I must observe internal events (e.g., Lead stage changes) and execute predefined user-configured automation rules (e.g., sending an email, updating a tag) predictably and accurately.
- **US-SYS-05 (Webhook & External API Processing)**: As the Musoftwares System, I must securely receive, validate, and process incoming webhooks from external platforms (e.g., payment gateways, WhatsApp providers) and map them to internal state changes.
- **US-SYS-06 (Security & Rate Limiting Enforcement)**: As the Musoftwares System, I must actively monitor request velocities, enforce rate limits per module/tenant, and block malicious patterns (e.g., brute-force login, spam) to protect the ecosystem.

### 6.2. Edge Cases
- **EC-SYS-01 (Queue Worker Crashes)**: If a queue worker processing a heavy task crashes midway, the system must either safely retry the job (idempotency) or mark it as failed and alert the admin without corrupting the database state.
- **EC-SYS-02 (Simultaneous Automation Triggers)**: If a single entity update triggers multiple competing automation rules, the system must process them sequentially or resolve deadlocks to prevent data race conditions.
- **EC-SYS-03 (External API Rate Limiting)**: When communicating with external APIs (e.g., sending SMS), if the rate limit is hit, the system must pause and requeue the remaining payload using exponential backoff.
- **EC-SYS-04 (Currency Exchange Service Failure)**: If the primary exchange rate API is down, the system must fall back to the most recently cached rates or pause automated currency-conversion transactions to prevent financial discrepancies.
- **EC-SYS-05 (Webhook Replays)**: If a payment gateway replays a webhook due to a network timeout, the system must recognize the duplicate transaction ID and gracefully ignore it to prevent double-crediting wallets.

### 6.3. Testing Requirements
- **TR-SYS-01 (Idempotency Testing)**: All queued jobs and webhook handlers must have automated tests verifying that running the same payload twice does not result in duplicate records or double-billing.
- **TR-SYS-02 (Concurrency & Race Condition Simulation)**: Integration tests must simulate high concurrency (e.g., multiple rapid wallet deduction requests) to ensure database transactions and locks correctly prevent negative balances.
- **TR-SYS-03 (Job Failure & Retry Logic)**: Automated tests must mock external API failures to verify that the exponential backoff and dead-letter-queue (DLQ) mechanisms function as intended.
- **TR-SYS-04 (Time-Travel Testing for Billing)**: Subscription lifecycles must be tested using mock clocks (e.g., Carbon's `setTestNow()`) to verify auto-renewals, grace periods, and expiration hooks across month boundaries and leap years.
- **TR-SYS-05 (Security Payload Validation)**: Webhook endpoints must have tests injecting invalid signatures, missing headers, and malformed JSON to confirm they fail closed and log the incident without crashing.

---

## 7. User Role: Team Member

This section defines the operational tasks, collaboration workflows, and restricted capabilities executed by the "Team Member" role within an organization's workspace (ERP, CRM, etc.).

### 7.1. User Stories
- **US-TM-01 (Task & Project Execution)**: As a Team Member, I must be able to view, update, and complete tasks assigned to me within projects so that work progresses efficiently without bottlenecks.
- **US-TM-02 (CRM Lead & Customer Interaction)**: As a Team Member, I must be able to interact with assigned leads, update pipeline stages, and add notes to customer profiles based on my permissions, enabling seamless sales and support operations.
- **US-TM-03 (Time Tracking & Payroll Visibility)**: As a Team Member, I must be able to log my working hours, request leaves, and view my payroll slips, so that I have clear visibility into my employment and compensation records.
- **US-TM-04 (Internal Communication & Collaboration)**: As a Team Member, I must be able to communicate with other team members via internal chat or task comments to ensure clear operational alignment.
- **US-TM-05 (Restricted Access & Role Permissions)**: As a Team Member, I must only have access to the modules and data permitted by my role (e.g., HR vs Sales), protecting sensitive business financial data from unauthorized internal viewing.

### 7.2. Edge Cases
- **EC-TM-01 (Permission Revocation During Active Session)**: If an admin revokes a team member's permission to a module while they are actively using it, the system must immediately restrict access on the next API request or page load and redirect them with a clear message.
- **EC-TM-02 (Concurrent Task Updates)**: If two team members attempt to edit the same task, ticket, or lead simultaneously, the system must handle the conflict gracefully (e.g., via real-time WebSocket syncing or optimistic locking) to prevent silent data overwrites.
- **EC-TM-03 (Account Deactivation)**: If a team member is deactivated or terminated, they must be instantly logged out from all devices, and their historical data (logs, comments, performed actions) must be strictly preserved for auditing purposes.
- **EC-TM-04 (Cross-Branch Constraints)**: If a team member is restricted to a specific branch in a multi-branch setup, they must be prevented from viewing, creating, or manipulating data (inventory, clients, invoices) belonging to other branches.
- **EC-TM-05 (Task Reassignment on Deactivation)**: When a team member with pending critical tasks is deactivated, the system must either prompt the admin to reassign those tasks or move them to a generic pool to avoid abandoned workflows.

### 7.3. Testing Requirements
- **TR-TM-01 (Role-Based Access Control Verification)**: Automated tests must verify that team members with specific roles (e.g., "Sales Rep") receive a strict 403 Forbidden response when attempting to access unauthorized routes (e.g., "Payroll" or "Settings").
- **TR-TM-02 (Session Termination on Deactivation)**: Integration tests must simulate account deactivation and verify that all active session tokens and WebSocket connections for the team member are instantly invalidated.
- **TR-TM-03 (Branch Isolation)**: E2E tests must confirm that branch-restricted team members only see clients, inventory, and tasks explicitly associated with their assigned branch.
- **TR-TM-04 (Concurrent Edit Conflict Resolution)**: Tests must simulate concurrent edits to a single entity (e.g., updating a lead's status) and verify the system correctly processes the requests without corruption.

---

## 8. User Role: Manager

This section defines the oversight capabilities, team coordination, and approval workflows executed by the "Manager" role within specific modules (e.g., ERP, CRM).

### 8.1. User Stories
- **US-MGR-01 (Team Oversight & Performance)**: As a Manager, I must be able to view dashboards (e.g., `ManagerDashboard.tsx`) detailing my team's performance, lead conversion rates, and task completion, so I can effectively monitor productivity.
- **US-MGR-02 (Task & Lead Assignment)**: As a Manager, I must be able to assign, reassign, or unassign leads, tasks, and support tickets among my team members to ensure balanced workload and prompt resolution.
- **US-MGR-03 (Approval Workflows)**: As a Manager, I must be able to review and approve/reject team requests such as leave applications, expense reports, or discount requests on invoices to maintain financial and operational control.
- **US-MGR-04 (Project & Campaign Oversight)**: As a Manager, I must be able to oversee the progress of projects and marketing campaigns, adjusting timelines and resources as necessary to meet business objectives.
- **US-MGR-05 (Data Export & Reporting)**: As a Manager, I must be able to generate and export reports specific to my department's activities (e.g., sales reports, support resolution times) for strategic planning.

### 8.2. Edge Cases
- **EC-MGR-01 (Reassigning Active Tasks)**: If a Manager reassigns a lead or ticket while a team member is actively editing it, the system must handle the transition smoothly, warning the current user and transferring ownership without data loss.
- **EC-MGR-02 (Approval Deadlocks)**: If an expense requires multi-level approval and a Manager is unavailable, the system should allow a designated delegate or Admin to override or re-route the approval to prevent bottlenecks.
- **EC-MGR-03 (Cross-Department Visibility Limits)**: A Manager must be strictly restricted from viewing performance metrics or approving expenses for employees outside their assigned department or branch.
- **EC-MGR-04 (Self-Approval Prevention)**: A Manager attempting to approve their own expenses or leave requests must be blocked by the system, automatically escalating the request to an Admin or higher-level supervisor.
- **EC-MGR-05 (Mass Assignment Rollback)**: If a Manager performs a bulk assignment of hundreds of leads that fails midway, the system must rollback the changes or clearly flag which assignments succeeded and failed.

### 8.3. Testing Requirements
- **TR-MGR-01 (Scope of Authority)**: Automated tests must verify that a Manager receives a 403 Forbidden response when attempting to access, modify, or approve records belonging to a different branch or department.
- **TR-MGR-02 (Self-Approval Block)**: Integration tests must assert that a Manager cannot approve their own requests, ensuring the system enforces escalation rules correctly.
- **TR-MGR-03 (Bulk Reassignment Integrity)**: E2E tests must simulate bulk reassignment of tasks, validating that the old assignee loses access immediately and the new assignee is notified and granted access.
- **TR-MGR-04 (Dashboard Data Aggregation)**: Tests must confirm that the Manager Dashboard accurately aggregates data exclusively from the Manager's direct reports, ignoring data from other teams.

---

## 9. Self-Grilling & Edge Case Resolutions

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
