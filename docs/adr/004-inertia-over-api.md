# ADR 004 — Inertia.js Over Standalone REST / GraphQL API

## Status: Accepted

## Context

When designing the frontend architecture of our modern React SaaS platform, we evaluated how data should flow between the Laravel backend and React UI components.

Options considered:
1. **Traditional Decoupled SPA:** Construct a standalone React Single Page Application (SPA) communicating with a completely separate Laravel REST or GraphQL API.
2. **Inertia.js Monolithic Bridge:** Use `@inertiajs/react` to bridge Laravel controllers directly with React page components without building API endpoints solely for page navigation.

## Decision

Use **Inertia.js Monolithic Bridge**.

## Rationale

- **Massive Productivity Boost:** Eliminates the exhaustive boilerplate of building REST API controllers, OAuth token managers, CORS policies, client-side data fetching hooks (e.g., React Query or RTK Query), and global state stores.
- **Server-Driven Routing:** Preserves the simplicity of traditional server-side MVC web development. Laravel controllers govern routing, middleware role checks, and database validation, while React handles component reactivity and modern UI aesthetics.
- **Simplified Deployment:** The entire application compiles into a single unified deployment artifact. There is no need to configure and maintain separate Nginx server blocks or CI/CD pipelines for frontend and backend repositories.

## Consequences

- **Tightly Coupled Architecture:** The frontend cannot be hosted on a separate CDN or edge network (like Vercel or Netlify) independently of the Laravel backend server.
- **Mobile App Integration:** Because Inertia responses return HTML or JSON page props rather than raw data resources, building a native iOS/Android mobile app in the future requires creating dedicated API endpoints alongside our Inertia web routes. We have accounted for this by maintaining isolated REST endpoints in `/api/` route files for third-party consumers.

## See Also

- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/app.tsx`
