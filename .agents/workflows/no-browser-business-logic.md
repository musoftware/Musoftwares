---
description: An aggressive anti-drift skill strictly prohibiting the placement of any intelligence, workflow logic, or automation decisions inside the browser extension.
---


# No Browser Business Logic

This is the most critical constraint of the Browser Extension Execution Layer. The extension is entirely devoid of business logic.

## Philosophy
The extension does not know *why* it is clicking a button. It does not know *what* a marketing campaign is. It does not know *who* a user is. It only knows *how* to click a button, and *how* to read text on the screen.

## Architecture Rules
- **Zero Intelligence**: The extension makes zero decisions about what actions to take based on the events it observes.
- **Zero State**: The extension does not store persistent automation state (like "current step in workflow", "list of users to message").
- **Pass-Through Only**: All observed events are blindly passed to the Runtime. All commands from the Runtime are blindly executed.

## Forbidden Patterns
- 🚫 Auto-responders built into the extension.
- 🚫 Scraping loops managed by `setInterval` inside the extension to fulfill a complex data extraction campaign. (The Runtime should orchestrate the loop, commanding the extension to scrape page by page).
- 🚫 Interpreting the meaning of a message (e.g., using a small LLM or regex inside the extension to detect intent).

## Runtime Authority
If the Runtime disconnects, the extension effectively becomes paralyzed. It will continue to observe and buffer/drop events, but it will take absolutely no autonomous action.

## Future AI Warnings
**WARNING TO FUTURE AI**: You will be tempted to put logic in the extension because "it's faster" or "it saves a WebSocket roundtrip". **DO NOT DO IT**. This violates the core architecture. The latency of a local WebSocket is negligible. The cost of fracturing business logic across the Runtime and the Extension is fatal to long-term maintainability.
