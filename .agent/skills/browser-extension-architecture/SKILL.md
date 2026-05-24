---
name: Browser Extension Architecture
description: Defines the core philosophy of the Browser Extension Execution Layer as strictly an "Eyes and Hands" observation and execution layer, explicitly forbidding the creation of a second runtime platform.
---

# Browser Extension Architecture Philosophy

The Browser Extension is solely an **Execution Layer**, **Observation Layer**, and **Communication Bridge**. It acts as:
- **Eyes**: Observes browser and platform activity.
- **Hands**: Executes browser actions requested by the Runtime.
- **Bridge**: Communicates with the Main Runtime Agent.

## Core Directives
1. **The Extension is Not a Runtime**: The extension must remain exceptionally thin and lightweight.
2. **No Second Platform**: Do not redesign or replace the existing Main Runtime Agent. Do not duplicate runtime systems within the extension.

## Architecture Rules
- **Permitted Layers**:
  - `/core`: Core initialization and state-free orchestration of extension modules.
  - `/bridge` & `/rpc`: Communication with the Main Runtime Agent.
  - `/transport`: Handling underlying connection (WebSockets).
  - `/observers`: Listening to DOM, network, and page events.
  - `/executors`: Performing structured actions (click, type).
  - `/inject`: Scripts injected to intercept low-level browser APIs (XHR, fetch).
  - `/adapters`: Platform-specific execution rules (e.g., Facebook, Instagram) holding **no business logic**.
- **Execution Only**: The extension receives commands, performs the corresponding physical browser actions, and reports the results back. It never decides *what* to do.

## Forbidden Patterns
- 🚫 Creating a mini-runtime or orchestrator inside the extension.
- 🚫 Implementing a browser-side plugin system.
- 🚫 Managing automation workflows, task scheduling, or AI decisions within the browser.
- 🚫 Using the browser extension as a storage system for persistent automation state.

## Runtime Expectations
The Main Runtime Agent is the "Brain". It expects the extension to behave as a remote, dumb terminal that accurately streams raw, normalized events and blindly executes highly specific, structured commands without argument or hesitation.

## Future AI Warnings
**WARNING TO FUTURE AI**: Do not attempt to optimize the system by moving decision-making closer to the browser. The architecture explicitly isolates all intelligence to the Main Runtime Agent. Adding "just a little bit of logic" to the extension violates this separation of concerns and leads to architecture drift. The extension must remain "dumb".
