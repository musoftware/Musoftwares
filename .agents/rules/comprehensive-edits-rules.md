---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx,json,vue,blade.php}"
description: Ensure that edits are comprehensive. Do not stop after modifying a single place; check for related code, side effects, and do a final analysis for bugs and security.
---

# Rule: Comprehensive Edits and System-Wide Validation

## Problem Statement
Making a targeted edit in only one file or function without considering the broader system often leads to regressions, broken links, missing logic, and security vulnerabilities. Stopping immediately after a single change leaves side effects unchecked and can silently break features that rely on the modified code.

## Rules & Guidelines

### 1. Do Not Stop at a Single Edit
- When applying a change, do not just modify one place and assume the job is done.
- Actively search the codebase for other instances, related references, or identical logic that might also need to be updated to maintain consistency.

### 2. Check for Related Code and Side Effects
- **Data Flow:** Trace the data flow from the backend to the frontend (and vice-versa). If you change a model or a controller response, ensure the frontend React/Inertia component is updated to handle the new structure.
- **Dependencies:** If a service or helper method is changed, find all components or controllers that consume it and verify they still function correctly.
- **Missing Logic:** Check if adding a new status, currency, or feature requires updates in dropdowns, validation rules, database migrations, or translation files.

### 3. Identify and Prevent Problems
- Anticipate edge cases introduced by your changes.
- Consider what happens if related data is missing, null, or in an unexpected state because of the new edits.

### 4. Final Analysis (Bugs & Security)
- **Bug Sweep:** After completing the code changes, review the logic one final time specifically looking for logical errors, typos, or unhandled exceptions.
- **Security Check:** Ensure that new endpoints are protected by appropriate middleware or policies. Verify that data being displayed is authorized for the current user and that payloads are strictly validated before being saved to the database.

### 5. Summary Checklist
- [ ] Have I searched the codebase for other areas affected by this change?
- [ ] Have both the frontend and backend been updated to reflect the new logic or data structures?
- [ ] Are all related translations, migrations, and validation rules updated?
- [ ] Has a final review been conducted for potential edge cases, bugs, and security vulnerabilities?
