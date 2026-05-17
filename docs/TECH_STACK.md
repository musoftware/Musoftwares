# Technology Stack & Dependencies

## Overview

The ERP System is built on a highly modern, production-ready stack combining **Laravel 12** on the backend and **React 18** (fully typed with TypeScript) on the frontend, bridged seamlessly via **Inertia.js 2.0**.

## Core Backend Stack

### Framework
- **PHP:** 8.2 or 8.3
- **Laravel:** `^12.0` (Modular Monolith architecture)

### Database & Search
- **MySQL:** 8.0+ (Required for column-based tenancy, JSON columns, window functions, and strict relational integrity)
- **Meilisearch:** Full-text search engine (`laravel/scout` + `meilisearch/meilisearch-php`)

### Key Composer Dependencies (`composer.json`)
- **`nwidart/laravel-modules` (`^11.1 || ^13.0`):** Provides the modular directory structure isolating Core, ERP, Freelance, and Marketplace domains.
- **`inertiajs/inertia-laravel` (`^2.0`):** Monolithic bridge connecting Laravel controllers to React components.
- **`tightenco/ziggy` (`^2.4`):** Generates JavaScript route helper functions matching Laravel named routes in React.
- **`spatie/laravel-permission` (`^6.10 || ^7.4`):** Fine-grained role and permission management.
- **`spatie/laravel-model-states` (`^2.12 || ^3.8`):** Robust state machine management for invoices and orders.
- **`barryvdh/laravel-dompdf` (`^3.1`):** HTML to PDF generation for professional client invoices and receipts.
- **`phpunit/phpunit` (`^11.5.3`):** Robust automated unit and feature testing framework.

## Core Frontend Stack

### Framework
- **React:** `^18.2.0`
- **TypeScript:** `^5.0.2` (Strictly typed `.tsx` component architecture)
- **Inertia.js React Client:** `@inertiajs/react` (`^2.3.23`)
- **Vite:** Build tool and HMR development server (`^7.0.7`)

### Styling & UI Components
- **Tailwind CSS v4:** `@tailwindcss/vite` & `tailwindcss` (`^4.3.0`)
- **shadcn/ui Primitives:** Accessible headless UI components via Radix UI (`@radix-ui/react-dialog`, `@radix-ui/react-dropdown-menu`, `@radix-ui/react-toast`, etc.)
- **Framer Motion (`^12.38.0`):** Advanced micro-interactions, modal transitions, and spring animations.
- **Lucide React (`^1.16.0`):** Modern SVG icon library.
- **CMDK (`^1.1.1`):** Command palette component (`CommandPalette.tsx`).

### Data Visualization & Utilities
- **Recharts (`^3.8.1`):** Responsive financial charts and dashboard metrics.
- **Canvas Confetti (`^1.9.4`):** Micro-interaction celebratory animations upon successful milestone or order completion.
- **DOMPurify (`^3.4.3`) & Marked (`^18.0.3`):** Secure markdown parsing and HTML sanitization for freelance job descriptions and chat messages.

## Real-Time Communication Stack

- **Laravel Echo (`^2.3.4`) & Pusher JS (`^8.5.0`):** Client-side WebSocket listeners for synchronizing polymorphic chat messages, real-time invoice timer sessions, and notification banners.

## Development & Testing Tools
- **ESLint & Prettier:** Automated code formatting and static analysis (`eslint-plugin-react`, `prettier-plugin-tailwindcss`).
- **Vitest (`^4.1.6`) & Testing Library (`@testing-library/react`):** Lightning-fast React component unit testing environment.
