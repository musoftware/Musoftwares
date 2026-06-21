# Core Features & Overview

## System Purpose
Musoftwares is a comprehensive, modular, multi-tenant Business Management Platform designed to function seamlessly as a SaaS, ERP, CRM, and Marketplace. It is designed to handle core business operations, financial transactions, project management, and specialized domain workflows in a single deployable repository.

## Architecture
The platform is built using a **Modular Monolith** architecture. Physical grouping of domain logic is handled via the `nwidart/laravel-modules` package, enabling distinct bounded contexts.
Modules include:
- Core
- ERP
- CRM
- Billing
- Booking
- Marketplace
- Fbmb
- Freelance

## Frontend Architecture
- **Single-Page Application (SPA):** Built with React ^18.2.0.
- **Bridging:** Inertia.js ^2.0 removes the necessity for a standalone REST API for the core web app, directly wiring the Laravel backend to React components.

## Core Workflows
1. **Onboarding & Identity:** Registration, KYC verification, Tenant workspace setup, and Spatie role assignment.
2. **Financial & Billing:** Multi-currency system (dual-currency processing), Wallet top-ups, SaaS subscriptions, and recurring invoices.
3. **Project & Task Execution:** Project creation, task breakdowns (Kanban style), and time tracking.
4. **Sales & CRM:** Lead tracking, marketing campaigns, client communication, and ticketing.
5. **Marketplace & Licensing:** Software purchases, serial key generation/binding, and license lifecycle management.
