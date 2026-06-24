---
name: realtime_events
description: Strict guidelines for real-time capabilities, explicitly banning WebSockets/polling for SaaS features and reserving WebSockets exclusively for Runtime Tools.
---

# Real-Time Events & WebSockets Architecture

This skill dictates how real-time features and WebSockets are handled in the Musoftwares ecosystem.

## 1. Main SaaS Application (ERP, CRM, Billing, etc.)

**CRITICAL RULE: NO WEBSOCKETS OR POLLING FOR SAAS FEATURES.**

Our main hosting infrastructure **DOES NOT SUPPORT** WebSockets (no Laravel Reverb, Pusher, or Soketi).

- **No WebSockets**: Do not attempt to implement WebSockets, Server-Sent Events (SSE), or any push-based real-time features for the main Laravel backend and React frontend.
- **No Polling**: Do not implement long-polling or normal interval polling (e.g., `setInterval` or Inertia `router.reload` on a timer). 
- **Manual Refresh Only**: If a user needs the latest data (e.g., new notifications, ticket replies, status changes), they must manually refresh the page or click a manual refresh button.
- **Remove Old Implementations**: If you encounter old Laravel Echo or Reverb listener code in SaaS components, you should remove them.

## 2. Runtime Tools (The Only Exception)

WebSockets are **EXCLUSIVELY** reserved for Runtime Tools. 

Because the cloud hosting does not support WebSockets, the architecture is inverted:
- **The Hub is Local**: The Local Runtime Agent (NodeJS daemon running on the user's PC) opens a local WebSocket server (e.g., `ws://127.0.0.1:XXXX`).
- **The Client is Cloud**: The React UI hosted in the cloud connects *to* this local WebSocket server to communicate with the Runtime Tools.
- **No Cloud Hub**: There is no central cloud hub like Reverb or Pusher acting as an intermediary. The Cloud UI connects directly to the user's local machine for tool telemetry, commands, and real-time execution logs.

## 3. Strict Git Workflow for Future AI Agents

**CRITICAL RULE FOR AI AGENTS**: When making *any* code changes related to this skill, you MUST follow this strict git workflow:
1. **Branching**: Every new feature or fix MUST be done in its own dedicated branch (e.g., `feature/remove-reverb`).
2. **Atomic Commits**: You must commit after EVERY logical modification. Make small, incremental commits with clear, descriptive messages.
3. **No Direct Main Commits**: Never commit directly to the `main` or `master` branch during feature development.
