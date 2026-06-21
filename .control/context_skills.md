# Musoftwares Context & Skills

This document outlines the strict architectural guidelines, tech stack, and core libraries used in the Musoftwares project. All development must comply with these rules.

## Tech Stack

### Backend
- **Framework:** Laravel ^12.0
- **Language:** PHP ^8.2
- **Database:** MySQL / PostgreSQL / SQLite (via Eloquent ORM)
- **Cache & Queues:** Redis (phpredis client)
- **Search Engine:** Laravel Scout ^11.2 (Meilisearch)
- **Real-Time Communication:** Laravel Reverb (via Laravel Echo & Pusher JS)

### Frontend
- **Core Library:** React ^18.2.0
- **Language:** TypeScript ^5.0.2
- **Bridging:** Inertia.js ^2.0 (Direct Laravel to React integration)
- **Build Tool:** Vite ^7.0.7 (with aggressive manual chunking)
- **Styling:** Tailwind CSS v4 (`@tailwindcss/vite`), `lightningcss`, `clsx`, `tailwind-merge`

---

## Core Libraries

### Backend Packages
- **Architecture:** `nwidart/laravel-modules` (Modular Monolith)
- **Auth & Security:** Laravel Sanctum ^4.3, Laravel Breeze ^2.4, Laravel Socialite ^5.27
- **Authorization:** `spatie/laravel-permission` ^6.10 || ^7.4
- **State Machines:** `spatie/laravel-model-states` ^2.12 || ^3.8
- **Utilities:** `barryvdh/laravel-dompdf` (PDF Generation), `laravel-notification-channels/fcm`, `league/flysystem-aws-s3-v3`
- **Testing:** Pest ^3.8 (with Laravel Plugin), PHPUnit ^11.5.3, Larastan ^3.10, Mockery

### Frontend Packages
- **UI Architecture:** Shadcn UI (`base-nova` style) with Radix UI primitives
- **Icons:** Lucide React
- **State Management:** Zustand ^5.0.14 (client-side), React Context
- **Animation & Motion:** GSAP ^3.15.0, Framer Motion ^12.40.0
- **Data Visualization:** Recharts ^3.8.1, React Flow ^11.11.4
- **Utilities & Specialized Inputs:** `date-fns`, `xlsx`, `dompurify`, Monaco Editor, React Dropzone
- **Testing & Quality:** ESLint ^8.57.0, Prettier ^3.3.0, Vitest ^4.1.6, React Testing Library, Playwright ^1.60.0

---

## Custom Rules & Architectural Compliance

### 1. Modular Monolith Architecture
- The system must adhere to a **Modular Monolith** design using `nwidart/laravel-modules`.
- Domain logic must be physically grouped into distinct bounded contexts (e.g., Core, ERP, CRM, Billing, Booking, Marketplace, Fbmb, Freelance).
- Avoid tight coupling between modules where possible.

### 2. Thin Controllers & Service Layer
- **Thin Controllers:** Controllers are strictly responsible for HTTP request handling, validation mapping, and returning Inertia responses or redirects.
- **Service Layer:** All core business rules and logic must be centralized within the `app/Services/` directory for core features, or `Modules/{ModuleName}/Services/` for specific domains. Do not place business logic in controllers.

### 3. Inertia.js over REST APIs
- The application utilizes Inertia.js to seamlessly integrate the backend and frontend. 
- Do not build a standalone JSON REST API layer for the core web application workflows unless required for third-party integrations.

### 4. Database Schema & Modeling
- **Schema Isolation:**
  - Core schema (User Management, Financials, Task Management) roots in `app/Models/` and `database/migrations/`.
  - Module-specific schema strictly resides in `Modules/{ModuleName}/Models/` and `Modules/{ModuleName}/Database/Migrations/`.
- **Model Design:** Heavily utilize Laravel traits, Spatie packages (e.g., permissions, states), accessors, mutators, and polymorphic relationships to maintain flexibility.
- **Soft Deletes:** Enforce soft deletes across all critical tables to maintain historical integrity and prevent accidental data loss.

### 5. Frontend Guidelines
- **State Management:** Rely on Inertia.js for server-driven state. Use Zustand or React Context for complex client-side state.
- **UI Components:** Strictly use Shadcn UI components configured with the `base-nova` style and Tailwind CSS v4.

### 6. Workflow Implementation Requirements
When implementing or modifying workflows, adhere strictly to the established processes:
- **Onboarding:** Must handle KYC verification, Tenant workspace setup, and Spatie role assignment.
- **Financials:** Must account for Dual-Currency Processing before finalizing wallet top-ups, SaaS subscriptions, or recurring invoices.
- **Marketplace:** Must ensure the full lifecycle from purchase, to serial key generation, to binding and license management.
