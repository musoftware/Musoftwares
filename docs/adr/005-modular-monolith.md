# ADR 005 — Modular Monolith over Microservices

## Status: Accepted

## Context

The ERP System encompasses four diverse business domains: core platform administration, multi-tenant ERP invoicing, a skill-based freelance marketplace, and a productized service marketplace.

Options considered:
1. **Standard Monolith:** Maintain all models, controllers, and database migrations in standard root Laravel folders (`app/Models`, `app/Http/Controllers`).
2. **Distributed Microservices:** Split each module into an independent physical microservice (e.g., an ERP service, a Freelance service, a Marketplace service) communicating over gRPC, RabbitMQ, or HTTP REST.
3. **Modular Monolith:** Group domain logic by feature inside distinct `/Modules` folders while running everything inside a single PHP runtime and shared database.

## Decision

Use **Modular Monolith**.

## Rationale

- **Avoiding Microservice Overhead:** Distributed microservices introduce severe operational complexity: distributed transactions (Saga patterns), network latency, complex API contract versions, and difficult debugging across isolated log streams.
- **Clean Architectural Boundaries:** By using `nwidart/laravel-modules`, we achieve the exact same logical separation and domain encapsulation as microservices without the network tax. Each module maintains its own dedicated models, controllers, and test suites.
- **Seamless Future Extraction:** If a single module (e.g., the Marketplace module) experiences hyper-growth in the future, its clean internal architecture allows us to effortlessly extract it into an independent microservice with minimal code refactoring.

## Consequences

- **Shared Database Schema:** All modules share a single MySQL database instance. To prevent database spaghetti, modules must avoid directly writing raw SQL joins against another module's internal tables.
- **Service Dependency Rules:** When modules interact, they must invoke encapsulated domain services via Laravel's Service Container rather than directly mutating Eloquent models owned by another module.

## See Also

- `Modules/` directory structure
- `docs/MODULES.md`
