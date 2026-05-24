---
name: WebSocket RPC Standard
description: Defines the bidirectional real-time communication standard for RPCs and events between the Extension and the Runtime.
---

# WebSocket RPC Standard

Communication between the Browser Extension and the Main Runtime must be realtime, reconnectable, and resilient.

## Philosophy
The WebSocket is the umbilical cord between the Brain (Runtime) and the Hands/Eyes (Extension). It must be a structured, strictly typed RPC (Remote Procedure Call) and event-streaming channel.

## Architecture Rules
- **Event-Driven Streaming**: The extension streams normalized browser events continuously.
- **Structured Commands**: The Runtime sends structured commands to the extension.
- **Asynchronous Execution**: Commands that take time (like waiting for a navigation to complete) must be handled asynchronously with proper correlation IDs to match requests with responses.

## Implementation Contracts
### 1. The Event Envelope
Events streamed from the extension must follow a normalized structure:
```json
{
  "event": "platform.namespace.action",
  "payload": {
    // Data specific to the event
  },
  "timestamp": 1716301234
}
```

### 2. The Command Payload
Commands from the runtime must be structured and explicitly avoid arbitrary code execution:
```json
{
  "id": "req_123abc",
  "action": "type",
  "target": {
    "semantic": "message_input"
  },
  "value": "Hello"
}
```

## Forbidden Patterns
- 🚫 Passing raw `eval` JavaScript strings over the WebSocket for the extension to execute.
- 🚫 Implementing a REST API for the extension (must be real-time WebSocket).
- 🚫 Coupling the WebSocket connection tightly to a single tab's lifecycle without a background script coordinator.

## Future AI Warnings
**WARNING TO FUTURE AI**: The RPC contract must remain platform-agnostic at the transport layer. Do not embed Facebook or Instagram specific logic into the core RPC dispatcher. Platform specific translation happens in the adapters.
