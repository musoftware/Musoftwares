# Technical Constraints & Tech Stack

## Backend Constraints
- **Framework:** Laravel ^12.0
- **Language:** PHP ^8.2
- **Auth:** Laravel Sanctum ^4.3, Laravel Breeze ^2.4, Laravel Socialite ^5.27
- **Authorization:** `spatie/laravel-permission` ^6.10 || ^7.4
- **State Management:** `spatie/laravel-model-states` ^2.12 || ^3.8
- **Real-Time:** Laravel Reverb (via Laravel Echo & Pusher JS)
- **Search:** Laravel Scout ^11.2 (using Meilisearch)
- **Database/Cache:** Eloquent ORM (MySQL, PostgreSQL, SQLite), Redis (phpredis client)
- **Key Packages:** `barryvdh/laravel-dompdf` (PDF Generation), `laravel-notification-channels/fcm`, `league/flysystem-aws-s3-v3`

## Frontend Constraints
- **Library:** React ^18.2.0 with TypeScript ^5.0.2
- **Bridge:** Inertia.js ^2.0
- **Build Tool:** Vite ^7.0.7 (requiring aggressive manual chunking)
- **Styling:** Tailwind CSS v4 (`@tailwindcss/vite`), `lightningcss`, `clsx`, `tailwind-merge`
- **UI Architecture:** Shadcn UI (`base-nova` style), Radix UI primitives, Lucide React icons
- **State Management:** Zustand ^5.0.14 for client-side, Inertia for server-driven state, React Context
- **Animation:** GSAP ^3.15.0, Framer Motion ^12.40.0
- **Data Viz:** Recharts ^3.8.1, React Flow ^11.11.4
- **Utilities/Inputs:** `date-fns`, `xlsx`, `dompurify`, Monaco Editor, React Dropzone

## Testing & Quality
- **PHP:** Larastan ^3.10, Pest ^3.8 (with Laravel Plugin), Mockery, PHPUnit ^11.5.3
- **JS/TS:** ESLint ^8.57.0, Prettier ^3.3.0, Vitest ^4.1.6, React Testing Library
- **E2E:** Playwright ^1.60.0
