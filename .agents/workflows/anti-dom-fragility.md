---
description: Techniques to prevent the extension from breaking when target platforms update their frontends, emphasizing network-level observation over DOM scraping.
---


# Anti-DOM Fragility

Web platforms constantly update their UI, obfuscate classes, and change layouts (A/B testing). A Browser Extension relying purely on CSS selectors will break weekly.

## Philosophy
Assume the DOM will change tomorrow. Build systems that survive these changes.

## Architecture Rules
1. **Prefer Network Over DOM**: As defined in the GraphQL Network Hooks skill, reading data from API responses is 100x more stable than reading it from DOM nodes.
2. **Semantic Selectors**: When DOM interaction is required (e.g., clicking a button), always use Semantic Selectors resolved by the Adapter.
3. **Attribute Targeting**: Avoid classes. Target elements by `aria-label`, `role`, `data-testid`, or native attributes like `name` or `placeholder`.
4. **XPath Fallbacks**: Only use XPath as a last resort, and prefer text-based XPath (e.g., `//button[contains(text(), 'Send')]`) over structural XPath (e.g., `//div/div/span/button`).

## Implementation Contracts
When an Adapter fails to resolve a semantic selector or execute an action, it must stream a specific failure event to the Runtime:
```json
{
  "event": "extension.action.failed",
  "payload": {
    "action": "click",
    "target": "send_button",
    "reason": "Element not found. UI may have updated."
  }
}
```

## Forbidden Patterns
- 🚫 Relying on auto-generated classes like `.x1y123z`.
- 🚫 Hardcoding pixel offsets for clicks.
- 🚫 Using `setTimeout` to wait for an element to appear (use `MutationObserver` or `requestAnimationFrame` polling with a timeout instead, managed cleanly by the executor).

## Future AI Warnings
**WARNING TO FUTURE AI**: When fixing a broken selector, do not just patch in the new auto-generated class. Invest the time to find a stable attribute (`aria-label`) or switch the data extraction method to a network hook. Patching classes is a treadmill of technical debt.
