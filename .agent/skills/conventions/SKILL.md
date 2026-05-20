---
name: Musoftware Coding Conventions
description: The naming conventions, runtime versioning rules, and strict copywriting standards for the ecosystem.
---

# Coding Conventions

This skill outlines the strict coding standards and folder organizations for Musoftware across the backend, frontend, and plugins. It also enforces the "Apple-Level Simplicity" tone for all user-facing strings.

## Activation Conditions
This skill automatically applies when you are:
- Creating new files, classes, components, or plugins.
- Writing user-facing text, button labels, or notifications.
- Refactoring existing code.

## 1. Text & Copywriting Rules (CRITICAL)
While variable names and database schemas should remain technically accurate, **ALL user-facing text must be human, short, obvious, friendly, and non-technical.**

- Avoid engineering terminology, runtime jargon, or internal architecture language in the UI.
- **BAD**: “Keystroke Randomization Curve” | **GOOD**: “Typing Style”
- **BAD**: “Runtime Worker Crash Recovery” | **GOOD**: “Automatic Recovery”
- **BAD**: “Distributed Multi-Session Orchestration” | **GOOD**: “Multi-Account Sending”

### Button & Action Labels
Buttons must feel obvious, calm, and direct.
- **GOOD**: Start Campaign, Connect Account, Import Contacts, Continue, Save Changes.
- **BAD**: Execute Runtime Task, Initialize Session, Trigger Worker.

## 2. Naming Conventions (Code Level)
The underlying code remains highly organized and explicitly named.

### General
- **WebSocket Events**: Use `snake_case` or `dot.notation` (e.g., `plugin.progress`).
- **Database Tables**: Plural, `snake_case` (e.g., `client_invoices`).

### Backend (Laravel/PHP)
- **Classes/Models**: `PascalCase` (e.g., `InvoiceService`).
- **Methods**: `camelCase` (e.g., `calculateTotal()`).
- **Services**: Business logic must reside in `app/Services/` or `app/Actions/`.

### Frontend (React/JS)
- **Components**: `PascalCase` (e.g., `LeadActivityStream.jsx`).
- **Hooks**: `camelCase` starting with `use` (e.g., `useWebSocket()`).

## 3. Plugin Creation Conventions
Plugins adhere strictly to this folder structure:
```
plugins/plugin-id/
 ├── manifest.json
 ├── worker/
 ├── frontend/
 └── docs/
```

## Summary Checklist
- [ ] Are all user-facing strings human, friendly, and completely devoid of engineering jargon?
- [ ] Do button labels communicate a calm, direct action ("Start Campaign" instead of "Trigger Worker")?
- [ ] Are internal code structures adhering to proper casing and folder separation?


# Tool UI Architecture System

## Core Philosophy
Each major tool/plugin must feel like a **real standalone software product**, not a one-page dashboard, simple form, or shallow CRUD layout.
Think of tools like independent workspaces (e.g., Notion, Slack, Linear, VSCode) operating inside the Musoftware ecosystem. The user should feel: "I entered a real application."

## Tool Layout Architecture
Large tools MUST have dedicated application layouts.
Structure:
<ToolShell>
  <ToolSidebar />
  <ToolHeader />
  <WorkspaceTabs />
  <MainWorkspace />
  <RealtimePanel />
</ToolShell>

NEVER build a plugin as a single giant page or a giant form. 

## Workspace Routing & Navigation
Plugins must support internal workspace routing (e.g., `/tools/whatsapp/accounts`, `/tools/whatsapp/campaigns`).
Do not flatten workflows into one screen. Every major entity must have a dedicated workspace tab.

## Software-Grade UX
Tools must support persistent state, operational workflows, realtime feedback, workspace continuity, and internal routing. They are installable-grade operational applications, NOT admin templates or settings dumps.

## Operational Entities
Entities (e.g., Accounts, Campaigns, Monitoring Jobs) must have dedicated pages, operational states, history, activity feeds, and realtime updates. 

## Realtime Software Feeling
Tools must feel alive. Always support live updates, realtime logs, websocket events, queue progress, and live activity feeds.

## Advanced UI Simplicity
Even advanced systems must feel simple and guided through progressive disclosure, contextual actions, clean navigation, and focused workspaces. Hide runtime complexity and engine internals completely.

## Final AI Behavior
When generating plugins or tools, automatically build full multi-workspace layouts, separate operational domains, and create a complete software-grade UX without needing user reminders.


# Runtime-First Architecture & Execution

## Critical Engineering Philosophy
The Frontend UI is ONLY an **operational control surface**. The REAL system exists exclusively inside the **Runtime Agent + Workers**.
- Buttons MUST trigger real runtime commands.
- Uploads MUST stream directly to the runtime.
- Tasks execute locally via the runtime.
- Logs, statuses, and progress MUST stream from the runtime via WebSockets.
- The Runtime is the absolute source of truth.

## No More Fake UI
NEVER create fake dashboards, static metrics, fake progress bars, mock runtime states, disconnected tabs, dead buttons, or simulated success messages. 
Tables, cards, and widgets MUST display real runtime data, not hardcoded placeholders.

## Architecture Responsibility
**Frontend (UI)**: ONLY renders state, triggers actions via Runtime SDK, subscribes to events, and displays progress. It NEVER owns operational logic or truth.
**Runtime Agent**: Handles execution, queues, workers, automation, browser sessions, local storage, CSV parsing, retries, logs, and concurrency. IT OWNS the operational entities (Campaigns, Queues, Sessions).
**Laravel Backend**: Handles ONLY authentication, subscriptions, billing, marketplace, licensing, user accounts, and high-level metadata. It NEVER handles local operational execution or heavy processing (like CSV uploads).

## Realtime System Requirement
Operational software MUST feel alive. You must always implement:
- Live logs
- Live queue states
- Live runtime heartbeat
- Realtime progress updates
- WebSocket event subscriptions

## Final AI Behavior
When building tools, you must constantly ask: 
- "Where does this operation actually execute?"
- "Is this runtime-owned or frontend-owned?"
- "How does realtime synchronization work?"
Never stop at a beautiful UI. Build fully operational, runtime-connected infrastructure.
