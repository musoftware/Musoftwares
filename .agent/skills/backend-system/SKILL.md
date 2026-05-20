---
name: Backend System Architecture
description: The Laravel backend architecture, emphasizing workflow-first design, service layers, and event-driven patterns, including marketplace integration.
---

# Backend System Architecture

This skill defines the backend architecture for the Musoftware web application. It mandates the use of service layers, workflow-first design, and event-driven principles over simple CRUD operations.

## Activation Conditions
This skill automatically applies when you are:
- Creating or modifying Laravel Controllers, Models, or Services.
- Designing new backend APIs or web routes.
- Implementing business logic for ERP, CRM, or billing.
- Handling database migrations or Eloquent relationships.

## 1. Core Technology Stack
- **Framework**: Laravel (PHP).
- **Database**: PostgreSQL / MySQL (managed via Eloquent ORM).
- **Architecture Pattern**: Service Repository / Action-based pattern.

## 2. Event-Driven & Realtime Principles
The backend must be reactive and asynchronous to support Realtime Operational UIs.
- **Queues**: Any task taking longer than a few hundred milliseconds MUST be dispatched to a Laravel Queue.
- **Events & Listeners**: Use Laravel's Event system to decouple side-effects.
- **Runtime Commands**: The backend sends commands to the Local Runtime orchestrator asynchronously. It listens to WebSocket events from the runtime to update the database state and push updates to the UI.

## 3. Workflow-First Design (No Fake CRUD)
Musoftware requires operational continuity. Standard RESTful CRUD controllers are insufficient for business workflows.
- **Actions/Services**: Business logic must live in `Services` or `Actions` classes, not inside Controllers. Controllers should only parse HTTP requests and format responses.
- **Lifecycle States**: Entities (e.g., Invoices, Plugin Executions) must have defined lifecycle states. Use State Machines or explicitly defined status enums.

## 4. Marketplace & Plugin Integration
The backend serves as the source of truth for the **Tools Marketplace**.
- **Billing & Subscriptions**: The backend manages all plugin subscriptions, licensing logic, and usage credits.
- **Runtime API**: Exposes secure API routes for the Local Runtime to check licenses, download plugin payloads, and report telemetrics.

## 5. Operational Continuity
- Never leave a user or a system process in an ambiguous state.
- Ensure all API endpoints that communicate with the Local Runtime have appropriate timeout, retry, and error-handling mechanisms.

## Summary Checklist
- [ ] Is the business logic extracted from the Controller into a Service or Action?
- [ ] Are long-running tasks dispatched to the Queue?
- [ ] Are runtime interactions managed via asynchronous events and WebSocket broadcasts?
- [ ] Does the implementation support a workflow rather than just raw database row manipulation?

**Laravel Backend Strict Rule**: 
For any Tool/Plugin, its ONLY connection to the Laravel backend is checking if the user is subscribed to the service or not.
EVERYTHING ELSE related to the tools (data, configurations, campaigns, logs, operational entities, processing) MUST be handled by the Local Runtime Agent and stored locally in the client's local SQLite database. The Laravel backend must NEVER be used to store or process tool-specific data.
