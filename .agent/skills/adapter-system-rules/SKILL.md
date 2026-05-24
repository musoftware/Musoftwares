---
name: Adapter System Rules
description: Defines how platform-specific adapters (Facebook, Instagram, WhatsApp) should be structured without adding business logic.
---

# Adapter System Rules

The extension must interact with highly varied platforms. To keep the core extension clean, all platform-specific logic is isolated into **Adapters**.

## Philosophy
Adapters are translation layers. They translate platform-specific DOM and network activity into standard extension events, and they translate standard extension commands into platform-specific physical actions. They contain absolutely no business logic.

## Architecture Rules
- **Directory Structure**:
  - `/adapters/facebook`
  - `/adapters/whatsapp`
  - `/adapters/instagram`
- **Adapter Responsibilities**:
  1. Defining Semantic Selectors (mapping `send_button` to `#actual-btn`).
  2. Defining Observers (how to detect a new message on this specific platform).
  3. Defining Executors (handling platform-specific quirks, like needing to dispatch a custom React event to trigger an input).
  4. Defining Network Hooks (intercepting specific GraphQL queries).

## Forbidden Patterns
- 🚫 Putting business logic in an adapter (e.g., `if (message.text === "help") { sendHelpMenu(); }`).
- 🚫 An adapter initiating a workflow or campaign.
- 🚫 Adapters maintaining persistent state across page reloads (state belongs in the Runtime).

## Implementation Contracts
An adapter exposes a standard interface to the extension core:
```typescript
interface PlatformAdapter {
  name: string;
  initialize(core: ExtensionCore): void;
  resolveSemanticSelector(semanticName: string): string;
  // ...
}
```

## Future AI Warnings
**WARNING TO FUTURE AI**: Do not use Adapters as a backdoor to sneak orchestration logic into the extension. If an adapter needs to do a complex sequence of steps, it is likely that the Runtime should be orchestrating those steps individually.
