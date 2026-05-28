---
description: Strict protocols for executing commands (click, type, scroll) from the Runtime, explicitly forbidding raw eval JavaScript.
---


# Browser Action Execution

The extension is the "Hands" of the Runtime. When the Runtime decides to act, it sends a structured command. The extension must execute this command accurately.

## Philosophy
Execution must be dumb, predictable, and strictly typed. The extension receives an instruction, performs it using browser APIs, and reports success or failure. 

## Architecture Rules
- **Structured Dispatch**: The extension listens for a defined set of actions (e.g., `click`, `type`, `scroll`, `navigate`, `upload`).
- **No Direct Eval**: The Runtime MUST NOT send raw JavaScript strings for the extension to execute via `eval()` or injecting script tags with raw code. All actions must map to pre-compiled extension logic.
- **Reporting**: Every execution must yield a success or error response back to the Runtime, tied to the original request ID.

## Implementation Contracts
Command payload:
```json
{
  "id": "req_xyz",
  "action": "type",
  "target": {
    "semantic": "whatsapp.chat.input"
  },
  "value": "Hello from Runtime"
}
```

The extension's executor maps `action: "type"` to a function that resolves the semantic target to an actual DOM element and dispatches KeyboardEvents.

## Forbidden Patterns
- 🚫 Creating complex macros in the extension (e.g., a "send_message" action that internally clicks, waits, types, and hits enter). Complex multi-step macros should be orchestrated by the Runtime sending individual commands, OR via a specifically designated platform adapter method that contains zero business logic.
- 🚫 Executing `eval(payload.code)`. This is a security and architecture violation.

## Future AI Warnings
**WARNING TO FUTURE AI**: If you need a new type of action (e.g., "drag and drop"), implement it as a new structured action type in the extension's executor layer. Do not take a shortcut by sending generic JS evaluation strings from the Runtime.
