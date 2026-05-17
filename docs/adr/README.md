# Architecture Decision Records (ADR)

## What is an Architecture Decision Record?

An Architecture Decision Record (ADR) is a historical document that captures an important architectural decision made along with its context and consequences.

When developing complex multi-module enterprise systems, engineering teams frequently wonder "Why was this database schema designed this way?" or "Why did we choose Inertia over REST APIs?". ADRs preserve the exact reasoning behind major structural engineering decisions to ensure long-term clarity and prevent circular debates during future refactoring.

## Index of Decisions

- [ADR 001 — Column-Based Multi-Tenancy](./001-column-based-tenancy.md)
- [ADR 002 — Immutable Transaction Ledgers](./002-immutable-transactions.md)
- [ADR 003 — Polymorphic Real-Time Chat](./003-polymorphic-chat.md)
- [ADR 004 — Inertia.js Over Standalone REST API](./004-inertia-over-api.md)
- [ADR 005 — Modular Monolith over Microservices](./005-modular-monolith.md)
