---
name: Automation Engine
description: The core principles for building workflows, background tasks, and scheduled automation rules.
---

# Automation Engine

This skill outlines how automated rules and scheduled tasks function within Musoftware. The Automation Engine allows users to define triggers ("When this happens...") and actions ("...do this").

## Activation Conditions
This skill automatically applies when you are:
- Building "If This Then That" (IFTTT) style rules for the CRM or ERP.
- Modifying Laravel Task Scheduling (`Console/Kernel.php`).
- Writing robust Queue jobs.

## 1. Trigger & Action Architecture
The engine relies heavily on the **Event-Driven Philosophy**.
- **Triggers**: Bound to Laravel Events. When `InvoiceOverdue` is fired, the Automation Engine listens, checks user-defined rules, and dispatches Actions.
- **Actions**: Discrete, self-contained Job classes pushed to the Queue. (e.g., `SendReminderEmailJob`).

## 2. Rule Evaluation
- Rule evaluations must be lightweight. Do not perform heavy database queries synchronously while evaluating an event. If evaluation is complex, dispatch an `EvaluateRuleJob`.

## 3. The Queue
- **No Synchronous Automation**: Automations MUST run on the queue. Never block the web request cycle to execute an automation rule.
- **Failures & Retries**: Ensure automation jobs implement `ShouldQueue` and handle failures gracefully. Use `$tries` and `$backoff` to prevent spamming APIs on failure.

## 4. Scheduled Tasks (Cron)
- System-wide periodic checks (e.g., "Check all deals for staleness every midnight") are registered in Laravel's scheduler.
- These scheduled commands should chunk database queries to avoid memory exhaustion on large datasets.

## Summary Checklist
- [ ] Are automation triggers tied to domain events?
- [ ] Are the resulting actions dispatched to a background queue?
- [ ] Do scheduled tasks use chunking for large datasets?


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
