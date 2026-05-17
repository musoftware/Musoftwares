# ADR 003 — Polymorphic Real-Time Chat Architecture

## Status: Accepted

## Context

The SaaS platform requires real-time messaging across three separate domains:
1. Freelancer and client collaboration on active job contracts (`Modules/Freelance`).
2. Buyer and seller communication on service marketplace escrow orders (`Modules/Marketplace`).
3. Client helpdesk and customer support ticketing (`Modules/Core`).

Options considered:
1. **Separate Chat Systems per Module:** Create dedicated messaging tables for each module (e.g., `contract_messages`, `order_messages`, `ticket_messages`).
2. **Unified Polymorphic Conversation Model:** Create a single central messaging engine in the Core module that dynamically attaches to any domain entity via polymorphic relationships (`conversable_type` and `conversable_id`).

## Decision

Use **Unified Polymorphic Conversation Model**.

## Rationale

- **DRY Architecture:** Prevents duplicating WebSocket broadcasting logic, attachment uploader controllers, read-receipt trackers, and UI chat bubble components across three separate modules.
- **Unified User Experience:** Allows us to build a single central "Messages" inbox where users can view and respond to all active conversations across contracts, orders, and support tickets in one place.
- **Ease of Extension:** If we develop a new feature module in the future (e.g., an internal team collaboration module), we can instantly enable real-time chat for that entity simply by adding our `MorphMany` conversation trait to the new model.

## Consequences

- **Database Polymorphic Indexes:** Polymorphic string columns (`conversable_type`) can degrade query performance if unindexed. We mitigate this by enforcing composite indexes on `(conversable_type, conversable_id)` across all database migrations.
- **Strict Role Permissions:** Because all messages live in a central table, backend controllers and WebSocket channel authorizers must strictly verify participant IDs before broadcasting private payloads.

## See Also

- `Modules/Core/Models/Conversation.php`
- `Modules/Core/Models/Message.php`
