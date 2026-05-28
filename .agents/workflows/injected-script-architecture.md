---
description: Rules for using injected scripts solely for network interception and internal page instrumentation, avoiding fragile DOM automation.
---


# Injected Script Architecture

Content scripts run in an isolated world. They cannot access the JavaScript context of the host page (like React's internal state or the raw `fetch` function). To achieve high-fidelity observation, we must inject scripts directly into the page.

## Philosophy
Injected scripts are powerful but dangerous. They must be as small as possible, do exactly one thing (usually interception), and communicate their findings back to the Content Script via `window.postMessage`.

## Architecture Rules
- **Minimal Injection**: Only inject scripts when absolutely necessary (e.g., to override `window.fetch` or `XMLHttpRequest`). Do not inject massive libraries.
- **Isolate from Page Logic**: The injected script must not interfere with the host page's execution. It should observe, clone data if necessary, and let the original request proceed unmodified.
- **Message Passing**: The injected script sends data to the Content Script. The Content Script normalizes it and sends it to the Background Worker, which streams it to the Runtime.

## Implementation Contracts
Example of an injected fetch interceptor:
```javascript
const originalFetch = window.fetch;
window.fetch = async function(...args) {
  const response = await originalFetch.apply(this, args);
  // Clone the response to read it without consuming the stream
  const clone = response.clone();
  clone.json().then(data => {
    window.postMessage({ type: 'EXTENSION_FETCH_INTERCEPT', data }, '*');
  }).catch(() => {});
  return response;
};
```

## Forbidden Patterns
- 🚫 Injecting business logic or decision-making algorithms into the page context.
- 🚫 Using injected scripts to bypass CORS for arbitrary requests (the background script should handle cross-origin API calls if requested by the Runtime).
- 🚫 Mutating the DOM from an injected script. DOM manipulation belongs in the Content Script or Executors.

## Future AI Warnings
**WARNING TO FUTURE AI**: Keep injected scripts absolutely minimal. If an injected script causes a JavaScript error in the host page's context, it can break the entire web application (e.g., crashing Facebook). Test interceptors rigorously.
