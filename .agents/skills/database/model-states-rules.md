# Spatie Model States Rules

## Purpose
Manage complex status transitions (e.g., Invoice `Draft` -> `Sent` -> `Paid`).

## Rules
1. Extend `Spatie\ModelStates\State`.
2. Define allowed transitions in the Model's `registerStates()` method.
3. Check `canTransitionTo()` before applying state changes.
