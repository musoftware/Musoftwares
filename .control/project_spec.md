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
