# Frontend Architecture Report

## 1. Overview
The frontend is built using **React 18** and **Inertia.js** to seamlessly integrate with the Laravel backend. It operates as a modern single-page application (SPA) without the complexity of an independent API layer, relying on Inertia for routing and data hydration. The build tool is **Vite**. The codebase is strongly typed using **TypeScript**.

## 2. Core Technologies
*   **Framework**: React (v18)
*   **Routing & SSR Bridge**: Inertia.js (`@inertiajs/react`)
*   **Styling**: Tailwind CSS v4, enhanced by `class-variance-authority`, `clsx`, and `tailwind-merge` for predictable component styling.
*   **Real-time Communication**: Laravel Echo and Pusher JS (configured to use Laravel Reverb).
*   **Animations**: Framer Motion and GSAP.

## 3. Directory Structure
The frontend codebase is located in `resources/js/` and is organized modularly:
*   `Components/`: Contains reusable React components.
    *   `ui/`: Hosts Shadcn UI primitives (e.g., `dialog.tsx`, `dropdown-menu.tsx`, `sidebar.tsx`).
    *   Domain-specific folders: `Admin`, `Booking`, `CRM`, `ERP`, `Marketplace`, etc.
*   `Layouts/`: Contains layout wrapper components tailored for different areas of the application (e.g., `ERPLayout.tsx`, `AuthenticatedLayout.tsx`, `CrmLayout.tsx`, `PublicLayout.tsx`).
*   `Pages/`: Contains the Inertia page views, logically grouped by domain/module (e.g., `Admin/`, `CRM/`, `ERP/`, `Freelance/`, `Auth/`).
*   `lib/`: Contains utility functions, API SDKs, and internationalization helpers (e.g., `utils.ts`, `runtime-sdk.ts`, `i18n.ts`).
*   `hooks/`: Custom React hooks (e.g., `useInertiaNotifications.ts`, `useRuntimeStatus.ts`).
*   `types/`: TypeScript type declarations.
*   `Modules/`: Front-end specific domain logic or grouped assets.

## 4. State Management
State management is handled through a hybrid approach:
1.  **Inertia.js (Server-Driven State)**: The primary source of truth for page data, routing state, and form handling. Data is passed directly from Laravel controllers to React page components via props.
2.  **Zustand**: Used for complex, module-specific client-side state that spans multiple components without needing server round-trips. For example, `useCRMStore` and `usePipelineStore` manage CRM pipeline interactions.
3.  **React Context**: Used for global UI modes and toggles. E.g., `FreelanceModeProvider` and `MarketplaceModeProvider` (injected at the root `app.tsx` level).
4.  **Local State**: Standard React `useState` and `useReducer` for isolated component behavior (e.g., mobile menu toggles in layouts).

## 5. UI Components & Design System
*   **Shadcn UI & Radix**: The application relies heavily on **Radix UI** primitives for accessible, unstyled interactive components (Dropdowns, Dialogs, Tabs) wrapped with Tailwind CSS to create a cohesive design system (Shadcn pattern).
*   **Base UI**: Included for additional headless UI components.
*   **Icons**: **Lucide React** is the standard icon set.
*   **Forms**: Managed using standard React state or Inertia's `useForm` hook, styled via `@tailwindcss/forms`. Rich text or code editing is supported via `@monaco-editor/react`.

## 6. Layouts and Views
*   **Layouts (`resources/js/Layouts/`)**: Implements specific layout shells that include sidebars, navigation headers, and responsive drawers. For instance, `ERPLayout.tsx` defines a mobile-friendly drawer menu and a persistent sidebar for larger screens, isolating navigation logic from page content.
*   **Views (`resources/js/Pages/`)**: Each file inside the `Pages/` directory represents an Inertia route endpoint. The directory structure mirrors the domain model to keep the application scalable. 

## 7. Additional Libraries
*   **Date Handling**: `date-fns`
*   **Data Visualization**: `recharts` for charting and graphs.
*   **Markdown Parsing**: `marked` and `dompurify` for safe HTML rendering.
*   **Testing**: Playwright for End-to-End (E2E) testing (`playwright-report`, `tests/e2e/`), and Vitest with Testing Library for unit testing components.
