---
description: Specific strategies for intercepting and extracting data from platform GraphQL responses to feed high-fidelity data to the Runtime.
---


# GraphQL Network Hooks

Modern platforms (Facebook, Instagram) rely heavily on GraphQL. Scraping the DOM to read a message is fragile. Intercepting the GraphQL response containing the message is robust.

## Philosophy
Prefer network interception over DOM scraping whenever possible. The data in a network response is structured, machine-readable, and changes far less frequently than CSS classes.

## Architecture Rules
- **Adapter-Specific Interception**: Network hooks must be defined inside Platform Adapters. A Facebook adapter knows which GraphQL query name corresponds to "fetch_messages".
- **Silent Observation**: Hooks must only observe and copy data. They must never block or modify the GraphQL response going to the host application.
- **Normalization Pipeline**: 
  1. Injected Script intercepts `window.fetch`.
  2. Injected Script passes raw JSON to Content Script.
  3. Content Script passes to Adapter.
  4. Adapter extracts the relevant data (e.g., the message text and sender ID) and normalizes it.
  5. Content Script sends normalized event to Background Worker.
  6. Background Worker streams to Runtime.

## Implementation Contracts
Adapters should define maps of query names to handlers:
```javascript
const GraphQLHandlers = {
  "MessageFetchQuery": (payload) => {
    return {
      event: "facebook.message.received",
      payload: extractMessages(payload)
    };
  }
};
```

## Forbidden Patterns
- 🚫 Sending raw, multi-megabyte GraphQL responses directly to the Runtime over the WebSocket. The adapter *must* extract and normalize only the required data to save bandwidth and parsing overhead in the Runtime.
- 🚫 Executing custom GraphQL queries from the extension to the platform's API using intercepted tokens, unless explicitly commanded by the Runtime.

## Future AI Warnings
**WARNING TO FUTURE AI**: GraphQL interception is the secret weapon for anti-fragility. Prioritize building robust GraphQL hooks over complex DOM MutationObservers. The DOM is a side-effect of the network data; observe the source.
