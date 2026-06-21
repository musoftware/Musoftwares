---
name: State Management
description: Guidelines for managing frontend application state efficiently.
---

# State Management Best Practices

Handling state efficiently prevents bugs and unnecessary re-renders.

## Core Rules

1. **Local vs Global State**: Keep state as local as possible. Only elevate state to a global store (Context, Redux, Zustand, Pinia) if it needs to be accessed by multiple unconnected components (e.g., user authentication status, shopping cart).
2. **Avoid Prop Drilling**: If you find yourself passing props down more than 2-3 levels of components that don't need the data, consider using a Context API or global store.
3. **Derived State**: Do not store state that can be computed from other state variables. Compute it on the fly during the render cycle to prevent synchronization bugs.
4. **Immutable Updates**: Always treat state as immutable. When updating objects or arrays, create a new copy rather than mutating the original reference directly.
