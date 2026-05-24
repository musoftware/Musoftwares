---
name: Reconnect & Recovery Patterns
description: Stability requirements for silently reconnecting to the WebSocket, handling tab closures, and maintaining resilience.
---

# Reconnect & Recovery Patterns

Browsers are chaotic environments. Tabs close, extensions reload, laptops go to sleep, and the Main Runtime Agent might restart. The extension must gracefully handle all of this.

## Philosophy
The connection to the Brain is ephemeral, but the intent to connect is permanent. The extension must always strive to maintain an active WebSocket connection to the Runtime.

## Architecture Rules
- **Silent Reconnection**: If the WebSocket drops, the extension must immediately begin a backoff-retry loop to reconnect. It must not spam the user with error modals.
- **Stateless Recovery**: When the extension reconnects, it does not send a backlog of missed events. It simply resumes streaming current events. The Runtime is responsible for reconciling any missed state.
- **Tab Safety**: The extension must handle multiple tabs open to the same platform. Coordination must occur in the extension's Background Worker to prevent duplicating events.

## Forbidden Patterns
- 🚫 Crashing the extension if the Runtime is unreachable.
- 🚫 Storing large queues of events in the extension's memory during a disconnect.
- 🚫 Requiring user intervention to reconnect the extension to the Runtime.

## Scaling Rules
Use a robust ReconnectingWebSocket implementation in the `/transport` layer. Implement exponential backoff to prevent flooding the local network if the Runtime is down.

## Future AI Warnings
**WARNING TO FUTURE AI**: Ensure that the background script coordinates WebSocket connections. Content scripts in individual tabs should pass events to the background script, which then streams them to the Runtime. Do not open a WebSocket connection from every single content script.
