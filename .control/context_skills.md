# Context & Skills

This document defines the core technologies, libraries, and custom architectural rules governing the Musoftwares project. All code generation and modification must adhere strictly to these constraints.

## Tech Stack

### Backend
- **PHP**: 8.2+
- **Framework**: Laravel 12.0
- **Database**: MySQL/MariaDB

### Frontend
- **UI Framework**: React 18.2
- **Integration**: Inertia.js 2.0 (`@inertiajs/react` and `inertiajs/inertia-laravel`)
- **Styling**: Tailwind CSS 4.3
- **Build Tool**: Vite 7.0
- **Language**: TypeScript 5.0

## Core Libraries

### Backend Packages
- `nwidart/laravel-modules` (Modular Architecture)
- `spatie/laravel-model-states` (State Management)
- `spatie/laravel-permission` (Role/Permission Management)
- `laravel/sanctum` (Authentication)
- `laravel/scout` + `meilisearch/meilisearch-php` (Search)
- `barryvdh/laravel-dompdf` (PDF Generation)
- `laravel-notification-channels/fcm` (Notifications)

### Frontend Packages
- **UI Components**: Radix UI Primitives (`@radix-ui/react-*`), Shadcn UI
- **Icons**: `lucide-react`
- **Animations**: `framer-motion`
- **Charts**: `recharts`
- **State Management**: `zustand`
- **Utilities**: `date-fns`, `clsx`, `tailwind-merge`
- **E2E Testing**: `@playwright/test`

## Custom Rules & Architectural Compliance

1. **Modular Architecture**: 
   - Strict boundaries between domains (`ERP`, `CRM`, etc.) via `nwidart/laravel-modules`.
   - Never mix logic between strictly separated modules (e.g., ERP vs CRM).
2. **Thin Architecture**: 
   - Enforce thin controllers and thin services. Avoid fat architecture and logic bloat in routing or controller files.
3. **Frontend Implementation**:
   - **Forms**: ERP forms MUST be full width (no modals or sliding sheets for Add/Edit forms).
   - **i18n**: Always use translatable text. NEVER hardcode English strings in the UI. Do NOT use global JSON translation files; use modular PHP arrays instead.
   - **UI/UX**: Strict layout enforcement using Shadcn UI. No native browser prompts or alerts. Maintain mobile parity and extreme UX simplicity.
4. **Data, Currency & Business Logic**:
   - Never use hardcoded currencies in invoices, expenses, or transactions. Enforce multi-currency system correctly.
   - Always enforce module/addon subscription checks.
   - Implement tri-path validation: Happy path, Edge cases, and Security limits.
5. **Security & Stability**:
   - Never expose Laravel footprint or default errors.
   - Always write unit/feature tests.
   - Follow the Comprehensive Edits Rule: trace data flow, verify related references across frontend and backend, and perform bug/security sweeps instead of isolated edits.
