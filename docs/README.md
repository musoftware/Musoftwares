# ERP System — Documentation

Welcome to the ERP System documentation.
This is a comprehensive SaaS platform for multi-tenant invoicing, freelancing, and marketplace services built on a robust modular monolith architecture.

## Quick Links

- [Architecture Overview](./ARCHITECTURE.md)
- [Tech Stack](./TECH_STACK.md)
- [Database Schema](./DATABASE.md)
- [API Documentation](./API.md)
- [Frontend Guide](./FRONTEND.md)
- [Backend Setup](./BACKEND.md)
- [Deployment](./DEPLOYMENT.md)
- [Module Guide](./MODULES.md)
- [User Guide](./USER_GUIDE.md)

## What is This?

A modular Laravel 12 + React 18 (TypeScript) SaaS with 5 structured modules (4 currently active):

1. **ERP Module** — Multi-tenant invoicing, client wallets (`client_wallets`), recurring entries, and referrals.
2. **Freelance Module** — Skill-based job marketplace (`freelance_jobs`), point packages, proposals, and contracts.
3. **Marketplace Module** — Productized service platform (`marketplace_services`), multi-tier packages, orders, and escrow vaults (`marketplace_escrows`).
4. **Core Module** — Shared foundation including authentication, currencies, double-entry accounting ledgers (`ledgers`, `accounts`, `journal_entries`), global wallets (`wallets`, `wallet_transactions`), polymorphic chat, and audit/impersonation logging.
5. **Shared Module** — Additional architectural utilities (currently inactive in `modules_statuses.json`).

## Key Features

- ✅ **Multi-currency accounting** with historical exchange rates (date-locked) and double-entry journal ledgers.
- ✅ **Multi-tenancy** (each ERP subscriber is isolated via column-based scoping on `tenant_id`).
- ✅ **Real-time polymorphic chat** across marketplace orders, freelance contracts, and support tickets.
- ✅ **Professional invoicing** (simple, quantity, and live timer billing sessions with associated costs).
- ✅ **Smart freelance marketplace** with required skill matching (`freelance_job_skills`) and bidding point ledgers (`point_transactions`).
- ✅ **Wallet & Escrow payments** for marketplace orders (`marketplace_escrows`) and point packages.
- ✅ **Referral system** (2-level commission earnings tracked in `client_referral_earnings`).
- ✅ **Withdrawal requests** (`withdrawals`) with manual admin review and audit notes.
- ✅ **Secure Impersonation** (super-admins can temporarily log into any user account, tracked in `impersonation_logs`).
- ✅ **Premium React 18 SPA UI** built with TypeScript (`.tsx`), Inertia.js 2.0, Tailwind CSS v4, shadcn/ui, and Framer Motion.

## Quick Start

See [Backend Setup](./BACKEND.md) and [Frontend Guide](./FRONTEND.md).

## Project Structure

```text
erp-system/
├── app/             ← Core Framework bootstrap, exceptions, and base middleware
├── database/        ← Master framework migrations and seeders
├── Modules/
│   ├── Core/        ← Currencies, exchange rates, settings, chat, double-entry ledgers, audit logs
│   ├── ERP/         ← Tenants, tenant_clients, invoices, timer_sessions, client_wallets, withdrawals
│   ├── Freelance/   ← Freelance skills, jobs, proposals, contracts, point packages & transactions
│   ├── Marketplace/ ← Service categories, services, packages, orders, reviews, escrows
│   └── Shared/      ← Shared helper utilities
├── resources/js/
│   ├── Pages/       ← Inertia pages structured by feature (Admin, ERP, Freelance, Marketplace, Welcome)
│   ├── Components/  ← Reusable TypeScript components (shadcn/ui + StatCard, ContextualPanels, CommandPalette)
│   ├── Layouts/     ← AdminLayout, AuthenticatedLayout, ClientLayout, GuestLayout, MarketplaceLayout, PublicLayout
│   └── app.tsx      ← React 18 TypeScript entry point
├── docs/            ← You are here
└── public/
```

## Contributing

When adding features or modifying existing architecture:
1. Ensure all new React components and pages are written in strictly typed TypeScript (`.tsx`).
2. Update relevant API docs in `docs/API.md` and related module API documents.
3. Update database schema documentation in `docs/DATABASE.md` if migrations are added or altered.
4. Keep all code examples and component guides up-to-date.

## Support

See [Troubleshooting Guide](./TROUBLESHOOTING.md) and [FAQ](./FAQ.md) for common technical and operational questions.
