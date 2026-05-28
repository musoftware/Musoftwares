---
description: Rules for targeting elements using semantic identifiers rather than fragile CSS/XPath selectors to ensure long-term stability.
---


# Semantic Selector System

Targeting UI elements in modern web apps (like Facebook or WhatsApp) using standard CSS classes (e.g., `.x1y123z`) is extremely fragile because these classes are auto-generated and change frequently.

## Philosophy
The Runtime should not know about fragile CSS classes. It should command the extension using **Semantic Identifiers**. The extension (via its Platform Adapters) translates these semantic IDs into the current, valid CSS/XPath selectors.

## Architecture Rules
- **Semantic Targets**: The Runtime requests actions on targets like `message_input`, `send_button`, or `profile_picture`.
- **Adapter Translation**: The platform-specific adapter inside the extension maintains the mapping of `message_input` to the actual, current DOM selector (`div[aria-label="Message"]`).
- **Resilience**: Adapters should use stable attributes whenever possible (`aria-label`, `data-testid`, `role`) instead of layout classes.

## Implementation Contracts
Runtime Command:
```json
{
  "action": "click",
  "target": {
    "semantic": "send_button"
  }
}
```

Adapter Translation (Internal to Extension):
```javascript
const Selectors = {
  "send_button": "button[aria-label='Send']"
};
const domNode = document.querySelector(Selectors[payload.target.semantic]);
```

## Forbidden Patterns
- 🚫 The Runtime sending raw, auto-generated CSS selectors like `.x1y123z` over the WebSocket.
- 🚫 Hardcoding layout-based XPath (e.g., `/html/body/div[1]/div[2]/span`) in the Runtime's workflows.

## Anti-Dom Fragility
By abstracting selectors into semantic names, if Facebook updates its UI, only the Extension's Facebook Adapter needs updating. The Runtime's automation workflows remain perfectly intact.

## Future AI Warnings
**WARNING TO FUTURE AI**: Do not leak raw CSS selectors into the Main Runtime Agent's plugins or workflows. The Runtime must only speak in semantic terms.
