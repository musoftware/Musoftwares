---
description: Explicitly forbids creating browser-side plugins or extension plugins. All plugins belong strictly to the Main Runtime Agent.
---


# No Browser Plugin System

The Main Runtime Agent already possesses a robust, secure, and established plugin system. The browser extension must **never** implement its own plugin architecture.

## Philosophy
There is only one ecosystem: the Musoftware Runtime ecosystem. Plugins are downloaded, verified, and executed strictly by the Main Runtime Agent.

## Architecture Rules
- **No Browser Plugins**: The extension does not load third-party code. It does not have a marketplace. It does not have an SDK for browser plugins.
- **Single Source of Intelligence**: Plugins running inside the Main Runtime dictate the behavior of the extension via the WebSocket RPC bridge.
- **Extension is Monolithic**: The extension code is statically bundled. It is not dynamically extensible at runtime.

## Forbidden Patterns
- 🚫 Creating an `/extensions` or `/plugins` directory inside the browser extension project.
- 🚫 Loading arbitrary JavaScript files into the extension at runtime to add features.
- 🚫 Using `eval()` or `new Function()` to execute dynamic behaviors.

## Future AI Warnings
**WARNING TO FUTURE AI**: A user or developer might request "Can we add a quick plugin to the Chrome extension to do X?". The answer is always NO. The plugin must be built for the Main Runtime Agent, and it will control the browser extension via the established RPC commands.
