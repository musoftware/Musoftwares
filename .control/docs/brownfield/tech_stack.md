# Musoftwares Codebase Tech Stack & Architecture

## High-Level Architecture
The application employs a **Modular Monolith** architecture powered by `nwidart/laravel-modules`. This allows the system to remain cohesive as a single deployable repository while physically grouping domain logic into distinct bounded contexts.

### Discovered Modules
- **Core / Platform Modules**: `Core`, `Shared`, `Tools`, `WebTools`
- **Business Domains**: `ERP`, `CRM`, `Billing` (implied by Vite config), `Booking`, `Freelance`, `Marketplace`
- **Specialized Domains**: `AffiliatePos`, `GoldSavers`, `PasswordSync`, `WrittenCoursesEngine`
- **Integrations / Gateways**: `PaymentGateway`, `SmsPaymentGateway`, `Fbmb`

## Backend Framework
- **Core**: Laravel `^12.0`
- **Language**: PHP `^8.2`
- **Authentication & Security**: Laravel Sanctum `^4.3`, Laravel Breeze `^2.4`, Laravel Socialite `^5.27`
- **Real-Time Communication**: Laravel Reverb
- **Search Engine**: Laravel Scout `^11.2` (via Meilisearch)
- **Authorization & State Management**: `spatie/laravel-permission` `^6.10 || ^7.4`, `spatie/laravel-model-states` `^2.12 || ^3.8`
- **Other Key Packages**: 
  - `barryvdh/laravel-dompdf` (PDF Generation)
  - `laravel-notification-channels/fcm` (Firebase Notifications)
  - `league/flysystem-aws-s3-v3` (S3 Storage)

## Frontend Framework
- **Core**: React `^18.2.0`
- **Bridging**: Inertia.js `^2.0`
- **Language**: TypeScript `^5.0.2`
- **Build Tool**: Vite `^7.0.7`
- **Styling**: Tailwind CSS `v4` (`@tailwindcss/vite`), processed by `lightningcss`
- **UI Architecture**: Shadcn UI (using `base-nova` style with Radix UI primitives)
- **Icons**: Lucide React
- **State Management**: Zustand `^5.0.14`

### Key Frontend Libraries
- **Animation & Motion**: GSAP `^3.15.0`, Framer Motion `^12.40.0`
- **Data Visualization**: Recharts `^3.8.1`, React Flow `^11.11.4` (Node-based UI workflows)
- **Utility & Data Handling**: `date-fns`, `xlsx` (Excel exports), `dompurify`
- **Specialized Inputs**: Monaco Editor (`@monaco-editor/react`), React Dropzone, Firebase SDK

## Development & Build Tooling
- **Code Quality (PHP)**: Larastan `^3.10` (PHPStan wrapper)
- **Code Quality (JS/TS)**: ESLint `^8.57.0`, Prettier `^3.3.0` (with auto-import sorting and Tailwind plugins)
- **Vite Chunking**: The Vite config applies aggressive manual chunking to avoid monolithic bundle sizes. Specialized chunks include `lib-icons`, `lib-motion`, `lib-charts`, `lib-editor`, `app-pages-core`, `app-pages-erp`, and `app-pages-freelance`.

## Testing Stack
- **Backend Testing**: Pest `^3.8` with Laravel Plugin, Mockery, PHPUnit `^11.5.3`
- **Frontend / Unit Testing**: Vitest `^4.1.6`, React Testing Library
- **End-to-End (E2E) Testing**: Playwright `^1.60.0`
