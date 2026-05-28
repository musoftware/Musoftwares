---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Always ensure every translation key used in views, controllers, or frontend components actually exists in the translation language files.
---

# Rule: No Missing Translations (Raw Keys in UI)

## Problem Statement
When a translation helper such as `__('erp.invoices')` is used, but the key `invoices` is not defined in the corresponding translation file, the application will render the raw key literal (e.g., `erp.invoices`) on the frontend. This is commonly seen in navigation menus (such as rendering `erp.invoices` instead of `Invoices` or `الفواتير` on routes like `http://127.0.0.1:8000/erp/dashboard?section=invoices`). This breaks the UI polish, causes confusion, and looks highly unprofessional.

## Rules & Guidelines

### 1. Mandatory Key Definition
- Whenever you introduce a new translation key in a Blade view, React/TSX component, Controller, or Menu configuration, you **MUST** immediately add the key to the corresponding language files.
- **Do not** write the usage of a translation key and assume it exists or leave it for later.

### 2. Multi-Language Parity
- The application supports multiple languages (typically English and Arabic).
- When a key is added, it must be added to **all** active locale files simultaneously to ensure that users of any language do not see raw keys.
- **Example**: If you add `__('erp.invoices')`, ensure both the English and Arabic `erp.php` language files are updated.

### 3. Validating Dynamic Keys
- Be especially careful with dynamically generated keys (e.g., `__('erp.' . $section)`). Ensure that **every possible value** of `$section` (like `invoices`, `clients`, `projects`) has a corresponding entry in the translation files.
- If a section or type is added to the database or configuration, its translation key must be added concurrently.

### 4. Shared and Module-Specific Translations
- Follow the project's modular translation file structure. If the translation belongs to a specific module (like `Modules/ERP`), add it to that module's translation directory rather than the global translation files, to keep features encapsulated.

### 5. Summary Checklist
- [ ] Have all new translation keys been added to the English language file?
- [ ] Have all new translation keys been added to the Arabic language file?
- [ ] Are dynamic keys (e.g., `__('section.' . $name)`) fully covered for all possible dynamic values?
- [ ] Has the frontend been checked to ensure no raw keys like `erp.invoices` are visible to the user?
