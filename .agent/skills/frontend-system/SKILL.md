---
name: Frontend System Architecture
description: The UI architecture, Shadcn-only policy, plugin UI integration, and Apple-level UX philosophy.
---

# Frontend System

This skill defines the frontend UI architecture for the Musoftware web application. It enforces UI consistency, dictates component choices, and establishes the "Apple-Level Simplicity" philosophy.

## Activation Conditions
This skill automatically applies when you are:
- Building or modifying React components.
- Designing new pages, dashboards, or UI flows.
- Styling elements or adjusting layouts.
- Implementing UI components using Shadcn.
- Building the frontend integration for a new Plugin.

## 1. Core Technology Stack
- **Framework**: React (rendered via Laravel inertia or Vite).
- **Styling**: TailwindCSS.
- **Component Library**: **Shadcn UI ONLY**.
- **Typography**: Inter font.

> [!CAUTION]
> **No Custom CSS/Component Bloat.** Do not build custom UI components from scratch if a Shadcn component exists for that purpose. Always use the established Shadcn library components to guarantee visual consistency.

## 2. Website UI Philosophy (CRITICAL)
Inspired by Apple, Linear, Raycast, Notion, Stripe, and Arc Browser, the UI should feel: **minimal, intentional, obvious, soft, premium, and focused.**
- It must NOT feel like a dashboard-heavy admin template, engineering panel, or settings chaos.
- Plugin UI belongs **inside the website**, NOT inside standalone apps. Everything must feel like native, premium Musoftware.

## 3. Operational UX Standards
Plugins must feel like **operational SaaS tools**, prioritizing Apple-level simplicity with Enterprise-level power.
- **Workflow-First UX**: Design interfaces around the *Lifecycle* of a task. Use wizards and multi-step forms.
- **Progressive Disclosure**: Keep interfaces simple by default. Advanced power is hidden intelligently.
- **No Giant Forms**: Use steps, cards, previews, and guided builders rather than config-heavy interfaces.
- **Hide the Engine**: NEVER expose engine complexity, runtime internals, or developer terminology.

### No Fake Dashboards
- Avoid creating dashboards filled with "placeholder" charts, random cards, or disconnected metrics.
- Visual hierarchy must prioritize the *next action* and *current status* over noisy analytics and logs.

### Consistency Rules
- **No Layout Shifting**: Ensure loading states use proper skeletons that match the final content dimensions.
- **Unified Spacing & Typography**: Rely entirely on Tailwind utility classes.

## 4. UI Aesthetics & "Wow" Factor
The frontend must deliver an outcome that feels like **"Apple designed enterprise automation software."**
- **Clean and Modern**: Use ample whitespace intentionally, with minimal color usage.
- **Micro-interactions**: Incorporate subtle hover effects and active states.
- **Dark Mode**: Always ensure components support dark mode gracefully without jarring contrast issues.

## Summary Checklist
- [ ] Is the UI inspired by Linear/Stripe/Apple (minimal, soft, premium)?
- [ ] Is the engine complexity completely hidden from the frontend view?
- [ ] Are you using progressive disclosure instead of overwhelming the user?


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
