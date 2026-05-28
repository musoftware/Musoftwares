---
description: Codifies that the Runtime has absolute authority over task scheduling, campaigns, AI decisions, and persistent state.
---


# Runtime Authority Rules

In any distributed system, authority must be clearly delineated to prevent split-brain scenarios. In this architecture, the Main Runtime Agent holds 100% of the authority.

## Philosophy
The extension is a subordinate execution node. It has no agency. It only does what it is told, when it is told.

## Architecture Rules
- **Task Scheduling**: The extension has no concept of time, delays, or cron jobs. If an action needs to happen in 5 minutes, the Runtime waits 5 minutes and then sends the command.
- **Campaign Management**: The extension does not know it is executing a campaign. It only knows it is clicking a button.
- **AI Decisions**: All LLM integrations, natural language processing, and decision trees exist in the Runtime. The extension only sends raw text; the Runtime interprets it.
- **Persistent State**: The extension does not use `localStorage` or `IndexedDB` to store automation state (e.g., "users already processed"). This state lives in the Runtime's SQLite database.

## Forbidden Patterns
- 🚫 Using `setTimeout` inside the extension to coordinate a sequence of actions (this is workflow orchestration).
- 🚫 Storing a list of "targets to message" inside the extension's background script.
- 🚫 Allowing the extension to decide to retry a failed action autonomously without instructing the Runtime. (The extension reports the failure; the Runtime decides whether to retry).

## Future AI Warnings
**WARNING TO FUTURE AI**: Do not blur the lines of authority. If the extension starts making decisions, it becomes a second orchestrator, leading to race conditions, state desync, and architectural collapse.
