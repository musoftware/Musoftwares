---
name: Runtime-First Browser Bridge
description: Establishes the relationship between the Main Runtime and the Extension. The Main Runtime is unchanged, and the extension must adapt to its architecture.
---

# Runtime-First Browser Bridge

The Main Runtime Agent already exists, is stable, and is production-approved. This project **only** adds a browser-side execution bridge alongside it.

## Philosophy
**Runtime = Brain**
**Extension = Eyes + Hands**

The Main Runtime remains COMPLETELY unchanged. The Browser Extension must adapt to the existing runtime architecture — never the opposite.

## Architecture Rules
- **One-Way Authority**: The Main Runtime dictates all actions. The extension never acts autonomously.
- **Strict Separation of Concerns**: The existing Runtime already owns the plugin system, orchestration, AI, workflows, automation logic, SQLite, state management, sessions, queues, realtime systems, and business logic. Do not recreate any of these inside the browser extension.

## Implementation Contracts
- The Extension connects to the Runtime (usually via WebSocket).
- The Runtime defines the JSON schema for commands and expected events.
- The Extension translates browser events into the Runtime's expected schema (normalization).

## Allowed Patterns
- Normalizing a WhatsApp incoming message DOM event into a standard `{ "event": "whatsapp.message.received" }` JSON payload before sending it over the bridge.
- Receiving a structured action payload and mapping it to a local executor.

## Forbidden Patterns
- 🚫 Refactoring the Runtime to better accommodate the extension.
- 🚫 Creating browser-side workflows.
- 🚫 Splitting business logic between the Runtime and the browser.

## Future AI Warnings
**WARNING TO FUTURE AI**: If you find yourself modifying the Main Runtime to fix a browser extension issue, STOP. The Extension must be the one to adapt. The Runtime is the source of truth and the universal orchestrator. Do not pollute the Runtime with browser-specific quirks.
