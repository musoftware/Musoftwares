---
name: Operational UX Philosophy
description: The core UX rules enforcing Apple-Level Simplicity with Enterprise-Level Power across the platform.
---

# Operational UX Philosophy

This skill dictates the foundational user experience (UX) mindset for building the Musoftware ecosystem. The core mantra is: **Apple-Level Simplicity with Enterprise-Level Power.**

## Activation Conditions
This skill automatically applies when you are:
- Designing new interfaces, dashboards, or plugin workflows.
- Writing user-facing text, buttons, and notifications.
- Designing forms or configuration settings.
- Making architectural UX decisions.

## 1. Core Product Philosophy
The platform MUST feel simple, calm, guided, obvious, clean, operational, and approachable. Even when the underlying engine is an extremely advanced, distributed, real-time automation system, users should feel: **“This is easy.”**

They must NOT feel overwhelmed, confused, or intimidated.

## 2. The UX Golden Rule
**NEVER expose engine complexity in the UI.**
- The complexity belongs inside the runtime, NOT the UI.
- Do not expose runtime internals, technical tuning, low-level configs, developer terminology, or engineering language to the user.

## 3. Functionality & Progressive Disclosure
Simple UI does NOT mean removing functionality or hiding system power. We achieve this through **Progressive Disclosure**:
- The UI must be simple by default.
- Advanced power is hidden intelligently.
- **Example**: Instead of exposing "WPM", "typing curves", and "retry engine", expose options like "Safe", "Balanced", "Fast". The runtime handles the complex mapping internally.

## 4. Text & Copywriting Rules
ALL user-facing text must be human, short, obvious, friendly, operational, and non-technical.
- **BAD**: “Keystroke Randomization Curve” | **GOOD**: “Typing Style”
- **BAD**: “Runtime Worker Crash Recovery” | **GOOD**: “Automatic Recovery”
- **BAD**: “Distributed Multi-Session Orchestration” | **GOOD**: “Multi-Account Sending”

## 5. Button & Action Rules
Buttons must feel obvious, calm, and direct.
- **GOOD**: Start Campaign, Connect Account, Import Contacts, Continue, Save Changes.
- **BAD**: Execute Runtime Task, Initialize Session, Trigger Worker.

## 6. Form Design Rules
- Avoid giant forms, massive settings pages, textarea-heavy interfaces, and config overload.
- Prefer step-by-step wizards, cards, previews, guided builders, and smart defaults.

## 7. Defaults Philosophy
The system should **intelligently choose defaults**.
- Users should rarely configure delays, retries, concurrency, safety tuning, or queue settings.
- **The runtime should decide automatically.** Advanced settings should exist but remain hidden behind expandable sections or secondary menus, never shown first.

## Summary Checklist
- [ ] Is the copywriting human and non-technical?
- [ ] Are we using progressive disclosure to hide complexity?
- [ ] Are we using smart defaults instead of asking the user to configure technical parameters?
- [ ] Does the UI feel like "Apple designed enterprise automation software"?


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
