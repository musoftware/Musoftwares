# Rule: Complete Placeholders Before Starting New Jobs

## Problem Statement
Jumping to new tasks while leaving behind "TODOs", placeholder text (e.g., "Lorem Ipsum", "Fake Name"), hardcoded mock data, or empty stub functions creates severe technical debt. It leads to broken UI elements, untested logic gaps, and an unprofessional end product.

## Rules & Guidelines

### 1. The "No Leftovers" Protocol
- **Before starting any new job, feature, or request**, you **MUST** ensure the current or previous task is 100% fully implemented.
- Do not leave work half-finished. If the user asks you to start something new, first explicitly confirm that you have completed all placeholders and fake data from the current task.

### 2. Strict Elimination of Placeholders
- **Never** leave placeholder strings (e.g., "TODO", "FIXME", "Title Here", "Lorem Ipsum") in Blade views, React/TSX components, or backend responses.
- All text in the UI must be final, meaningful, and fully localized using the `__()` translation helper.

### 3. Real Data Over Fake Data
- Mock data or hardcoded arrays used for initial prototyping must be fully replaced by dynamic data fetched from the database/backend.
- **Never** leave hardcoded user details, fake balances, or dummy lists in production-ready files.

### 4. Complete All Stubs and Empty Logic
- If a route, controller method, or UI button is created, its core logic must be implemented. Do not create "dead" buttons or empty endpoints just to make the UI look complete visually.
- Every interactive element must perform its intended action or gracefully handle the state.

### 5. Pre-Flight Check Before Completion
- Before declaring a task "done" and moving to the next prompt, actively review the files you modified.
- Sweep for words like "fake", "mock", "todo", or generic placeholders and replace them with actual implementation.
