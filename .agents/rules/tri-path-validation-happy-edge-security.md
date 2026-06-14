# Rule: Tri-Path Validation (Happy, Edge, Security)

## Problem Statement
When debugging issues or ensuring a feature "works", focusing solely on the "Happy Path" (the optimal, expected scenario) leads to missing hidden bugs, data inconsistencies, and security vulnerabilities. Features may appear functional on the surface but fail drastically when backend data is stale or malicious payloads are sent.

## Rules & Guidelines

Whenever requested to "ensure something works", "test a feature", or "debug a problem", you MUST explicitly evaluate and document your checks against the following **Three Paths**:

### 1. The Happy Path (Normal Execution)
- **Objective:** Ensure the primary intended behavior functions seamlessly.
- **Checks:**
  - Do all UI components render without breaking?
  - Do frontend endpoints point to the correct Backend routes (e.g., checking Ziggy aliases like `admin.transactions.create` vs `transactions.create`)?
  - Does the core transaction/submission save correctly to the database?

### 2. The Unhappy Path (Edge Cases & Data States)
- **Objective:** Anticipate failure states, empty states, and corrupted or stale environment data.
- **Checks:**
  - What happens if the database query returns an empty array `[]` or `null`? (e.g., the cronjob hasn't run, missing fallback exchange rates).
  - How does the UI handle `undefined` or missing relations?
  - Are strict type comparisons causing issues? (e.g., parsing a JSON boolean `true` against a string `'true'`).
  - Does the code break if a user has no projects, zero balance, or a missing currency setting?

### 3. The Security Path (Anti-Hacking & Exploits)
- **Objective:** Prevent malicious actors from bypassing logic or manipulating data.
- **Checks:**
  - **Authorization:** Does the endpoint verify that the user has the right permissions/subscriptions to perform the action?
  - **Payload Integrity:** Are we blindly trusting the frontend? (e.g., updating a balance using a frontend-supplied `amount` instead of recalculating it securely in the backend).
  - **Data Leakage:** Does an exception or error message expose database schema details to the user? (Refer to the `never-expose-laravel-footprint` rule).
  - **Validation:** Are all inputs strictly validated using Laravel Form Requests?

### Enforcement Checklist
Whenever you conclude a debugging session or verify a feature, explicitly confirm:
- [ ] Happy Path verified.
- [ ] Edge Cases and empty states checked.
- [ ] Security and payload integrity enforced.



---
