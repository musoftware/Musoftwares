---
name: Tools Marketplace Architecture
description: The premium ecosystem for plugins, emphasizing outcomes and workflows over technical specifications.
---

# Tools Marketplace

This skill defines how the Musoftware Marketplace operates. It bridges the Cloud UI where users purchase tools, and the Local Runtime where those tools are actually downloaded and executed.

## Activation Conditions
This skill automatically applies when you are:
- Building the Marketplace storefront in the Web UI.
- Modifying how the Local Runtime downloads and extracts plugins.
- Developing tool cards and changelogs.

## 1. Marketplace Philosophy (CRITICAL)
The Tools Marketplace should feel like a **premium software ecosystem**, much like the Apple App Store or Linear integrations directory.
- It must **NOT** feel like a hacker marketplace, a download scripts page, or a technical admin dashboard.

### Outcome-Driven Presentation
Tool cards and plugin detail pages must emphasize:
- **Outcomes**: What the user achieves (e.g., "Automate Client Onboarding").
- **Workflows**: How it fits into their day.
- **Value**: Why it matters.
- **Simplicity**: Showcasing clean screenshots and simple steps.

Do **NOT** emphasize:
- Technical specs (unless hidden in an advanced section).
- Runtime details (e.g., "Runs on Python 3.10 with Puppeteer").
- Low-level internal features.

## 2. Integration & Workspace Navigation
- **Workspace Integration**: Once installed, plugins appear naturally in the workspace navigation sidebar as premium operational tools, not hidden away in a secondary UI.
- **Invisible Infrastructure**: The user just clicks "Install" and the runtime handles downloading, extracting, and verifying the payload behind the scenes.

## 3. Update & Versioning System
- **Update Channels**: The Runtime Orchestrator periodically polls the Marketplace API for updates.
- **Changelogs**: The UI fetches and displays human-readable changelogs from the marketplace. Keep changelogs focused on user value, avoiding excessive developer jargon.

## Summary Checklist
- [ ] Does the marketplace presentation feel like a premium ecosystem rather than a hacker script store?
- [ ] Are plugin descriptions focused on outcomes and workflows rather than technical implementation details?
- [ ] Are plugin installations seamless, keeping the runtime complexity invisible?


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
