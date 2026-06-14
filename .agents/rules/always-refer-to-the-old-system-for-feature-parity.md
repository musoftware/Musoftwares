# Rule: Always Refer to the Old System for Feature Parity

## Problem Statement
When developing or migrating features into the new system (e.g., React/Inertia), there is a high risk of missing small but critical features, buttons, calculations, or behaviors that existed in the legacy system (e.g., old Blade templates, controllers). Furthermore, if the AI agent relies only on analyzing a single file (like a controller) without tracing the data flow to the UI (React Props) or searching across the whole repository, UI bugs and missing features will occur.

## Rules & Guidelines

### 1. Mandatory Deep Legacy Search (Global Grep)
- **NEVER** assume a feature's full logic is contained within a single `Controller` or `Livewire` component.
- **Deep Search:** You MUST use global `grep_search` across the legacy system's directories (`app`, `resources/views`, etc.) for keywords related to the feature to catch generic controllers, partial views, or traits that might contain missing features like `external_pay`, `exchange`, etc.

### 2. Strict UI-to-Backend Data Tracing
- You cannot just verify that a Controller method is "working". You **MUST** trace the data flow all the way to the frontend React component.
- **Props and Types:** Ensure the data passed from the controller matches what the React component expects (e.g., if the UI expects `currency.code`, ensure the controller isn't just passing `currency_id`).
- **Route Parameters:** Check all UI Action Links and Form submissions (e.g., `<Link href={...}>` or `router.post()`) and verify the parameters match the expected Request parameters in the backend (e.g., `?client_id=` vs `?user=`).

### 3. Feature Parity is the Goal
- The technical stack may change, but the **Final Result and User Experience must have 100% feature parity**.
- **Do not miss the details:** If the old system had a specific button, specific discount logic, or external links, the new React component MUST include them.

### 4. Execution Steps for Any Review/Migration
1. **Analyze User Request:** What is the feature?
2. **Deep Search (grep):** Search globally for the feature in the old codebase.
3. **Map Features:** Note every button, input, action, and calculation present in the legacy views and logic.
4. **Trace Frontend:** In the new system, verify the React component correctly receives, maps, and submits data back to the Controller without mismatches.
5. **Implement:** Build the equivalent containing ALL the mapped features.

### 5. Summary Checklist
- [ ] Did I use global `grep` to find all parts of the old feature instead of relying on one file?
- [ ] Did I verify the frontend React component data types and parameters against the Backend Controller?
- [ ] Does the new implementation include every action button that was present in the old system?



---
