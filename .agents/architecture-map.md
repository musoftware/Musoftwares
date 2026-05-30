# Architecture Map

## System Topology
1. **Core Laravel Framework**: Handles global routing, auth (Sanctum), and base services.
2. **Modules Layer (`Modules/`)**: Independent business domains (ERP, CRM, Booking, etc.) encapsulating their own Models, Controllers, Views, and routes.
3. **Frontend Layer (`resources/js/Pages`)**: Inertia.js React pages mirroring the backend module structure (e.g., `Pages/ERP/`, `Pages/CRM/`).
4. **Shared Components (`resources/js/Components`)**: Shadcn UI components (`ui/`), generic handlers (`FlashHandler.tsx`, `GlobalErrorHandler.tsx`), and module-specific UI fragments (`ERP/`, `CRM/`).

## Event & Background Processing
- Uses Laravel Queues (`redis` or `database`) for background jobs.
- WebSocket streaming (via Reverb or Pusher) for real-time frontend updates (e.g., `laravel-echo`).
