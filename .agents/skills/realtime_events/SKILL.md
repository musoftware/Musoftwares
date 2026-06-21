---
name: realtime_events
description: Manages the integration of Laravel Reverb WebSockets for real-time notifications, UI updates, and synchronization with frontend state management (Zustand).
---

# Real-Time Events & WebSockets (Laravel Reverb + Zustand)

This skill dictates how real-time features should be built in Musoftwares using Laravel Reverb on the backend and Zustand/React on the frontend.

## 1. Backend Implementation (Laravel Reverb)

- **Event Broadcasting**: Use Laravel's broadcasting capabilities to emit events over Reverb.
- **Channels**: 
  - Use `PrivateChannel` for user-specific data (e.g., notifications, private messages).
  - Use `PresenceChannel` for collaborative features (e.g., viewing a ticket, active users in a project).
  - Use `Channel` (public) sparingly, only for global events.
- **Event Classes**: Ensure event classes implement `ShouldBroadcast` (or `ShouldBroadcastNow` for critical real-time updates).
- **Payloads**: Keep event payloads minimal. Broadcast only necessary identifiers and delta changes to avoid sending bloated JSON strings over WebSockets. Let the frontend fetch full details via Inertia/XHR if needed, or send exactly what Zustand needs to update its state.
- **Service Layer**: Keep business logic out of the Event classes. The Service Layer should process business rules and explicitly dispatch the broadcast events.

## 2. Frontend Implementation (React + Zustand + Laravel Echo)

- **Laravel Echo**: Use Laravel Echo with the `pusher-js` client configured to connect to your local/production Reverb server.
- **Zustand Synchronization**:
  - Bind Echo listeners inside a `useEffect` at the layout or specific component level (or within a centralized custom hook).
  - When an event is received, call the relevant Zustand store action to update the client-side state.
  - Example: For a new ticket reply, the Echo listener catches the event and calls a Zustand action `addReply(payload)`, instantly updating the UI without a full page reload.
- **Inertia.js Interoperability**: 
  - Inertia handles server-driven state on navigation. Real-time events should patch the current client-side state. 
  - For complex state updates that are hard to patch manually, you can trigger an `router.reload({ only: ['dataKey'] })` via Inertia to refetch the freshest data seamlessly from the server.

## 3. Strict Git Workflow for Future AI Agents

**CRITICAL RULE FOR AI AGENTS**: When implementing real-time features or making *any* code changes related to this skill, you MUST follow this strict git workflow:
1. **Branching**: Every new feature, fix, or real-time event implementation MUST be done in its own dedicated branch (e.g., `feature/reverb-ticket-replies`).
2. **Atomic Commits**: You must commit after EVERY logical modification. Do not wait until the entire feature is done to commit. Make small, incremental commits with clear, descriptive messages.
3. **No Direct Main Commits**: Never commit directly to the `main` or `master` branch during feature development.

## 4. Common Real-Time Workflows

- **Notifications**: Broadcast to a `PrivateChannel('App.Models.User.{id}')`. On the frontend, the layout component listens to this channel and updates the notification bell counter/list via a Zustand store.
- **Task Kanban Boards**: When a task is moved, broadcast a `TaskMoved` event to a `PresenceChannel('project.{id}')`. Other users viewing the board receive the event and their local Zustand store updates the columns.
- **Ticket Threads**: Broadcast `TicketReplyCreated` to `PrivateChannel('ticket.{id}')` (or presence channel) to instantly append chat-bubble replies to the UI.
