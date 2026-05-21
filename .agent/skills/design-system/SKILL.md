---
name: Unified Design System
description: The single design language, Shadcn usage, typography, and premium Apple-inspired UI aesthetics.
---

# Design System

This skill dictates the visual identity and structural UI rules for Musoftware. We maintain ONE unified design language across all modules.

## Activation Conditions
This skill automatically applies when you are:
- Creating new UI components for the web app or plugin interfaces.
- Styling pages or tweaking layouts.
- Reviewing UX consistency.

## 1. The Shadcn Mandate (STRICT RULE)
- **Shadcn UI ONLY**: You MUST use Shadcn components (Card, Button, Input, Table, Badge, etc.) everywhere across the entire application and all tools. Do NOT build custom UI components with raw Tailwind classes (like `bg-white rounded-xl shadow-md` or custom form elements).
- **Refactor Raw Tailwind**: If a UI element needs to be built (e.g., a card, a button, an input, a switch), you MUST use the corresponding Shadcn component. Refactor any existing raw Tailwind components into Shadcn components.
- **No Ad-hoc Components**: Use the Shadcn dropdown, not a raw `div` with Tailwind.
- **Tokens over Hardcodes**: Rely on Tailwind's configuration (`bg-background`, `text-foreground`, `primary`, `muted`, `accent`). Ensure Dark Mode works flawlessly.

## 2. Component Philosophy
Components must be: **reusable, calm, compact, and obvious.**
- Use **whitespace intentionally** to let the interface breathe.
- Use **subtle borders** instead of heavy contrasting blocks.
- Maintain a **soft hierarchy** and **minimal color usage**.
- **Avoid**: Giant gradients, dashboard clutter, excessive charts, and noisy interfaces.

## 3. Typography & Visual Hierarchy
- **Font**: Use **Inter** (modern sans-serif).
- **Hierarchy Rules**: The UI must guide attention clearly.
  - **Primary**: Next action, current status, operational progress.
  - **Secondary**: Analytics, advanced settings, technical logs.
  - They must never compete visually.

## 4. The "Wow" Factor (Apple-Level Simplicity)
Enterprise software must feel premium. The ultimate goal is: **"Apple designed enterprise automation software."**
- **Operational SaaS Tools**: Plugins must feel premium. Never expose engineering panels or configuration consoles.
- **No Exposed Internals**: Never render raw JSON dumps to the user.
- **Micro-animations**: Use subtle transitions on interactable elements.
- **Depth**: Use subtle shadows (`shadow-sm`, `shadow-md`) to define elevation.

## 5. Form Design
- Avoid giant, massive settings pages or textarea-heavy interfaces.
- Prefer steps, cards, previews, and smart defaults.
- Always show inline validation errors using the Shadcn `<Form>` wrapper.

## Summary Checklist
- [ ] Does the UI feel calm, minimal, and premium (Linear/Apple inspired)?
- [ ] Are you using whitespace intentionally with minimal color usage?
- [ ] Does the visual hierarchy clearly prioritize the next operational action?


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

## 6. Unified Layout Width
- **Standard Application Pages**: Must use a boxed, centered layout (`max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`). This ensures a unified shape across all pages and prevents content from stretching uncomfortably on ultra-wide screens.
- **Data-Heavy Views**: Full-width (`w-full px-4`) is strictly reserved ONLY for giant data tables, complex kanban boards, or highly dense technical interfaces where horizontal space is critical.
- **Implementation Rule**: Always default to the boxed layout (`max-w-7xl`) for dashboards, forms, profile settings, and standard list views.
