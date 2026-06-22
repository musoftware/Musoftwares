# Technical Constraints and Stack

This document outlines the strict technological requirements and versions specified for Musoftwares.

## Backend
- **Framework:** Laravel ^12.0
- **Language:** PHP ^8.2
- **Authentication & Security:** 
  - Laravel Sanctum ^4.3
  - Laravel Breeze ^2.4
  - Laravel Socialite ^5.27
- **Authorization:** `spatie/laravel-permission` ^6.10 || ^7.4
- **State Machines:** `spatie/laravel-model-states` ^2.12 || ^3.8
- **Real-Time Communication:** Laravel Reverb (via Laravel Echo & Pusher JS)
- **Search Engine:** Laravel Scout ^11.2 (using Meilisearch)
- **Database ORM:** Eloquent ORM (MySQL, PostgreSQL, SQLite, etc.)
- **Caching & Queues:** Redis (phpredis client)
- **Key Packages:** 
  - `barryvdh/laravel-dompdf` (PDF Generation)
  - `laravel-notification-channels/fcm`
  - `league/flysystem-aws-s3-v3`

## Frontend
- **Core:** React ^18.2.0
- **Bridging:** Inertia.js ^2.0 (server-driven state)
- **Language:** TypeScript ^5.0.2
- **Build Tool:** Vite ^7.0.7 (must use aggressive manual chunking)
- **Styling:** Tailwind CSS v4 (`@tailwindcss/vite`), `lightningcss`, `clsx`, `tailwind-merge`
- **UI Architecture:** Shadcn UI (`base-nova` style) incorporating Radix UI primitives
- **Icons:** Lucide React
- **State Management:** 
  - Zustand ^5.0.14 (client-side)
  - Inertia.js (server-driven state)
  - React Context
- **Animation & Motion:** 
  - GSAP ^3.15.0
  - Framer Motion ^12.40.0
- **Data Visualization:** 
  - Recharts ^3.8.1
  - React Flow ^11.11.4
- **Utility Libraries:** `date-fns`, `xlsx`, `dompurify`
- **Specialized Inputs:** Monaco Editor, React Dropzone

## Testing & Quality
- **Code Quality:** 
  - Larastan ^3.10 (PHP)
  - ESLint ^8.57.0 (JS/TS)
  - Prettier ^3.3.0
- **Backend Testing:** Pest ^3.8 with Laravel Plugin, Mockery, PHPUnit ^11.5.3
- **Frontend / Unit Testing:** Vitest ^4.1.6, React Testing Library
- **E2E Testing:** Playwright ^1.60.0
