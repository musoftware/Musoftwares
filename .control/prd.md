# Musoftwares Product Requirements Document (PRD)

## 1. Executive Summary
Musoftwares is a modular, multi-tenant Business Management Platform designed to serve as a comprehensive SaaS, ERP, CRM, and Marketplace. It consolidates core business operations, financial transactions, project management, and specialized domain workflows into a single deployable repository using a **Modular Monolith** architecture. 

The frontend is a React 18 SPA integrated with Laravel 12 via Inertia.js, providing a seamless, fast, and modern user experience built on Shadcn UI and Tailwind CSS v4.

---

## 2. Architecture & Tech Stack (Context Recap)
- **Backend:** Laravel ^12.0, PHP ^8.2, MySQL/PostgreSQL/SQLite, Redis, Laravel Scout (Meilisearch), Laravel Reverb (WebSockets).
- **Frontend:** React ^18.2.0, TypeScript ^5.0.2, Inertia.js ^2.0, Vite ^7.0.7, Tailwind CSS v4, Shadcn UI (`base-nova`), Zustand for state, GSAP/Framer Motion for animations.
- **Modularity:** `nwidart/laravel-modules` handles bounded contexts (Core, ERP, CRM, Billing, Booking, Marketplace, Fbmb, Freelance).
- **Services:** Core business logic is strictly kept in the Service layer (`app/Services/` or `Modules/{ModuleName}/Services/`), keeping controllers thin.

---

## 3. User Journeys & State Machines
### Onboarding Journey
`Guest` -> `Registered` -> `KYC_Pending` -> `KYC_Approved` (or `Rejected`) -> `Tenant_Setup` -> `Role_Assigned` -> `Active_User`

### Financial Workflow
`Select Action` (Top-up/Sub/Invoice) -> `Dual-Currency Processing` -> `Process Transaction` -> `Success/Failure` -> `Update Wallet/Activate License`

---

## 4. Explicit Route & Page Checklist (Strict UI/UX Requirements)

This section acts as a strict checklist for every expected route in the application. All pages must use Shadcn UI components with `base-nova` style and Tailwind v4.

### 4.1. Identity, Auth & Onboarding
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/login` | User authentication | Minimalist card. Email/Password inputs, Social Auth buttons, 'Forgot password' link. GSAP entrance animation. |
| `/register` | User account creation | Name, Email, Password, Confirm Password, Terms checkbox. Password strength indicator. |
| `/forgot-password` | Initiate password reset | Email input field, prominent submit button, link back to login. |
| `/reset-password/{token}` | Complete password reset | New password and confirm password inputs. Validation feedback in real-time. |
| `/verify-email` | Prompt for email verification | Illustration, "Check your inbox" message, "Resend verification email" action button. |
| `/verify-email/{id}/{hash}` | Verification processing | Invisible processing route. Redirects to `/kyc` or dashboard with success toast. |
| `/kyc` | Know Your Customer document upload | Multi-step wizard layout. React Dropzone for ID/document uploads. Visual progress tracker. Status indicator (Pending, Approved, Rejected). |
| `/onboarding/tenant-setup` | Workspace creation | Form for Workspace Name, Subdomain, and Logo upload. Real-time slug validation. |
| `/onboarding/role-assignment` | Initial user roles | Data table/list to invite team members and assign Spatie roles via dropdowns. |

### 4.2. Core Application & User Settings
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/dashboard` | Main landing overview | Bento-grid layout. Overview stat cards (revenue, active projects, leads). Recharts for charts. Quick action floating action button (FAB). |
| `/profile` | User personal settings | Avatar upload, personal info form, change password section, 2FA toggle (requires Auth confirmation). |
| `/settings/workspace` | Tenant global config | Tabs for General, Billing Address, Preferences, and Danger Zone (Delete workspace). |
| `/settings/members` | Team management | Data table of users. Roles/Permissions badges. Modal for "Invite Member" with role selection. |

### 4.3. Financial & Billing Module
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/billing` | Subscriptions & overview | Summary cards for Active Plan, Wallet Balance, and Next Billing Date. |
| `/billing/wallet` | Wallet management | Large balance display. Transaction history table with sorting. "Top-Up" CTA. |
| `/billing/wallet/top-up` | Add funds | Preset amount buttons + custom input. Dual-currency selector (Base vs Display currency). Payment method selection. |
| `/billing/subscriptions` | SaaS Plan management | Pricing table/cards (Shadcn styling). Upgrade/Downgrade confirm modals. Current plan highlighted. |
| `/billing/invoices` | Invoice history | Paginated table of invoices. Status badges (Paid, Pending, Overdue). Action menu (Download PDF). |
| `/billing/invoices/{id}` | Detailed invoice view | A4-styled digital layout. Print/Download actions. Breakdown of line items. |

### 4.4. ERP & CRM (Sales, CRM, Ticketing)
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/crm/leads` | Lead management | Toggle between Data Table and Kanban board (drag & drop). Advanced filters (status, source). |
| `/crm/leads/{id}` | Lead details | Split pane: Left (Contact info, status), Right (Activity timeline, notes input). |
| `/crm/clients` | Client directory | Searchable grid/table of clients. Status indicators. |
| `/crm/clients/{id}` | Client portal view | Tabs: Overview, Projects, Invoices, Tickets. |
| `/crm/campaigns` | Marketing campaigns | List of campaigns with mini sparkline charts (Recharts) for open/click rates. |
| `/crm/campaigns/create` | Campaign builder | Stepper UI. Audience selector (checkbox list), Rich Text/Monaco editor for email body. |
| `/support/tickets` | Support desk | List of tickets. Priority badges (Red/Yellow/Green). Assigned agent avatars. |
| `/support/tickets/create` | Open new ticket | Subject, Category dropdown, rich text description, React Dropzone for attachments. |
| `/support/tickets/{id}` | Ticket thread | Chat-bubble style layout. Internal vs Public note toggles. Sticky reply box at bottom. |

### 4.5. Project & Task Execution
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/projects` | Project portfolio | Card grid showing project name, client, progress bar, and team avatars. |
| `/projects/create` | New project | Form with date range picker, client autocomplete, budget input. |
| `/projects/{id}` | Project dashboard | High-level metrics. Tabs for Tasks, Files, Timesheets, Settings. |
| `/projects/{id}/tasks` | Task management | React Flow / custom Kanban board. Drag-and-drop columns. Filter by assignee. |
| `/projects/{id}/tasks/{taskId}`| Task detail | (Can be a modal or page). Title, description editor, subtask checklist, comments. "Start Timer" button. |
| `/projects/{id}/time-tracking` | Timesheets | Calendar or list view. Manual time entry form. Running timer widget. |

### 4.6. Marketplace & Licensing
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/marketplace` | Software store | Grid of product cards (image, title, price, tags). Sidebar for category filters. |
| `/marketplace/products/{id}` | Product details | Large hero image/carousel. Description tabs, pricing tiers. "Add to Cart" / "Buy Now" button. |
| `/marketplace/checkout` | Purchase flow | Cart summary. Dual-currency display. Payment gateway integration UI. |
| `/marketplace/checkout/success`| Confirmation | Celebration animation (Framer Motion). Order summary. "View License Keys" CTA. |
| `/licenses` | Purchased software | Table of owned licenses. Status (Active, Expired). Expander to view keys. |
| `/licenses/{id}` | License management | License details. List of Serial Keys. "Bind to IP/Domain" action. "Revoke" danger action. |

### 4.7. Additional Modules (Booking, Freelance, Fbmb)
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/booking` | Appointment calendar | Full-page calendar view (Month/Week/Day). Click to add appointment. |
| `/booking/create` | New appointment | Service selection, Date/Time picker (checking availability), Client info. |
| `/freelance/jobs` | Job board | List of open contracts. Filter by skill/budget. |
| `/freelance/jobs/{id}` | Job detail | Full description, budget, employer info. "Submit Proposal" CTA. |
| `/freelance/proposals` | Proposal tracking | List of submitted proposals with status (Pending, Accepted, Rejected). |
| `/fbmb/dashboard` | Fbmb Module home | Specific dashboard metrics related to the Fbmb context. |

### 4.8. Error Pages (Must be branded and styled)
| Route | Purpose | Specific UI/UX Requirements |
|-------|---------|-----------------------------|
| `/403` | Forbidden access | "Access Denied" illustration. Explanation text. "Return to Dashboard" button. |
| `/404` | Page not found | Creative 404 graphic. Search bar to find content. "Go Home" button. |
| `/500` | Server error | Apologetic text. "Try Again" button. "Contact Support" link. |
| `/503` | Maintenance mode | "We'll be right back" messaging. Estimated downtime display. |

### 4.9. Missing Menus & Role Isolations (Identified Gaps)
Based on the defined user roles (`accountant`, `support_agent`, `seller`), the following menu items and isolations are logically required but currently missing from the frontend implementation:

1. **Accountant Role Isolation (Admin Panel)**
   - **User Story:** As an Accountant, I want to access the Admin Panel and see ONLY the "Invoices", "Finance & Business", and "Seller & Payout" menus, so that I am not distracted by or given unauthorized access to System Settings, User Management, or Marketplace configurations.
   - **Acceptance Criteria:** Update `AppSidebar.tsx` to check if the user is an `accountant` (and not an admin) and filter the `items` array to only show the relevant financial groups.

2. **Support Agent Role Isolation (Admin Panel)**
   - **User Story:** As a Support Agent, I want to access the Admin Panel and see ONLY the "Operations -> Tickets" and "Operations -> Guest Tickets" menus, so that I can focus solely on resolving user issues without accessing other operational or financial data.
   - **Acceptance Criteria:** Update `AppSidebar.tsx` to check if the user is a `support_agent` and filter the `items` array accordingly.

3. **Seller Dashboard (Frontend/Marketplace)**
   - **User Story:** As a Seller on the Marketplace, I need a dedicated "Seller Portal" accessible from the main navigation, where I can manage my product listings, view sales, and request payouts.
   - **Acceptance Criteria:** Create routes like `/seller/dashboard`, `/seller/products`, and `/seller/payouts`. Add a "Seller Portal" link in the Services Mega Menu or User Dropdown in `AuthenticatedLayout.tsx` that is visible if the user has the `seller` role.

---

## 5. Database & Service Logic Execution Rules
1. **Thin Controllers:** Controllers only handle HTTP requests, `Inertia::render()`, and redirects.
2. **Service Layer:** All business logic (e.g., Key generation, dual-currency processing, subscription state changes) must reside in `app/Services/` or module-specific `Services/`.
3. **Data Integrity:** Soft deletes are mandatory on all core and modular tables.
4. **Real-Time Data:** Use Laravel Reverb to broadcast events to the frontend (e.g., ticket replies, task column changes, notification alerts) triggering Zustand state updates.

## 6. Non-Functional Requirements
- **Performance:** Vite must utilize aggressive manual chunking to ensure rapid SPA load times.
- **Security:** Strict authorization via `spatie/laravel-permission` must be applied at both the Route/Middleware level and within the UI components (hiding unauthorized elements).
- **UX Motion:** GSAP and Framer Motion must be used purposefully (e.g., page transitions, modal entrances, success states) without overwhelming the user interface. Keep animations smooth and under 300ms.
- **Accessibility:** Radix UI primitives inside Shadcn ensure ARIA compliance, keyboard navigation, and screen reader support.
