---
name: Event-Driven Browser Observers
description: Guidelines for observing DOM, network, and page events and normalizing them into standard JSON payloads.
---

# Event-Driven Browser Observers

The extension operates as the "Eyes" of the Runtime. It must continuously observe platform activity and stream normalized events back to the brain.

## Philosophy
Observation is passive. Observers watch for changes, format the data into a standard schema, and broadcast it over the WebSocket. Observers do not trigger actions, handle state, or make decisions based on the data they observe.

## Architecture Rules
- **Normalization**: Raw browser events (MutationObserver records, Intercepted Fetch JSON, WebSocket frames) MUST be normalized into a standard event schema before being sent to the Runtime.
- **Continuous Streaming**: The extension streams events as they happen. The Runtime decides what is important and what to ignore.
- **Observer Types**:
  - `DOM Observers`: Watching for specific UI elements to appear/change.
  - `Network Observers`: Intercepting XHR/Fetch/GraphQL for high-fidelity data.
  - `Platform Observers`: Native extension events (tab changes, navigation).

## Implementation Contracts
Example of an observer translating a raw DOM change into a normalized event:
```json
{
  "event": "facebook.message.received",
  "payload": {
    "sender_id": "12345",
    "text": "Hello there"
  }
}
```

## Forbidden Patterns
- 🚫 Writing observers that say "If I see a message, then click reply." (This is a workflow/automation decision and belongs in the Runtime).
- 🚫 Sending raw, unparsed, platform-specific garbage (like a massive, un-filtered HTML string) to the Runtime unless explicitly requested.

## Scaling Rules
As more platforms are added, observers should be contained within their respective `/adapters/[platform]` directories to prevent the core `/observers` namespace from becoming a monolith.

## Future AI Warnings
**WARNING TO FUTURE AI**: Do not attempt to throttle, debounce, or filter events in the extension just to "save bandwidth" unless it's a generic, configured rule from the Runtime. The Runtime needs full visibility to make accurate decisions.
