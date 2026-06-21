---
name: Component Reusability
description: Enforces the DRY principle for UI components and business logic.
---

# Component Reusability

To maintain a clean and scalable codebase, components must be designed for reuse.

## Core Rules

1. **Single Responsibility Principle**: A component should ideally do one thing well. If a component is growing too large, break it down into smaller, focused sub-components.
2. **DRY (Don't Repeat Yourself)**: Avoid duplicating identical UI elements (like Buttons, Inputs, Cards) across different pages. Extract them into a shared `components/` directory.
3. **Props/Slots**: Use props, slots, or children effectively to make components flexible enough to handle variations (e.g., primary vs. secondary button styles) without duplicating the underlying markup.
4. **Separation of Concerns**: Keep complex business logic out of presentational components. Use hooks, services, or container components to handle data fetching and state logic.
