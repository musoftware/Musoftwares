---
description: Architecture for continuously streaming normalized events to the runtime without local interpretation.
---


# Realtime Event Streaming

The extension operates like a security camera, continuously streaming what it sees back to the command center (the Runtime).

## Philosophy
Event streaming must be continuous, unopinionated, and normalized. The extension does not filter events based on what it thinks is important; it sends everything defined by the platform adapters, and the Runtime decides what to act upon.

## Architecture Rules
- **Fire and Forget**: When an observer detects an event, it formats it and fires it over the WebSocket. It does not wait for a response.
- **Standard Envelope**: All streamed events must conform to the standard Event Envelope schema defined in the RPC standard.
- **High-Fidelity Context**: Events must contain as much context as safely possible (e.g., an element's text, HTML structure if necessary, or the exact JSON from an intercepted API call).

## Implementation Contracts
```typescript
function streamEvent(eventName: string, payload: any) {
  if (websocket.isConnected()) {
    websocket.send(JSON.stringify({
      event: eventName,
      payload: payload,
      timestamp: Date.now()
    }));
  }
}
```

## Forbidden Patterns
- 🚫 Debouncing critical events in the extension just to reduce traffic, unless it's a rapidly firing DOM mutation where a custom rate-limit is necessary to prevent crashing the Runtime.
- 🚫 The extension modifying the payload based on business rules (e.g., "redact swear words"). The extension sends raw data.

## Future AI Warnings
**WARNING TO FUTURE AI**: Do not implement an event queue inside the extension that attempts to replay missed events if the WebSocket disconnects. State recovery is the responsibility of the Runtime, which should query the platform for missed state upon reconnection.
