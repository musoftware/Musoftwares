# Rule: Do Not Use Global JSON Translation Files (Use PHP Arrays)

## Problem Statement
Relying on a giant flat JSON file (e.g., `lang/en.json` or `lang/ar.json`) for translations creates massive, unmanageable files over time. It causes merge conflicts, makes it difficult to track which module or feature owns which string, and slows down developers. Furthermore, using raw English sentences as keys (e.g., `__('Manage and track your leads pipeline')`) makes the code brittle and visually cluttered.

## Rules & Guidelines

### 1. Prohibition of Global JSON Translations
- **Never** add translations to a global `en.json` or `ar.json` file.
- **Never** use raw English sentences as translation keys in the codebase.
- **Example of Failure**:
  ```php
  // ❌ INCORRECT (Using flat English strings relying on JSON files)
  return redirect()->back()->with('success', __('Campaign created successfully.'));
  ```
  ```tsx
  // ❌ INCORRECT
  <Button>{__('Save Settings')}</Button>
  ```

### 2. Mandatory Modular PHP Arrays
- All translations **must** be stored in modular PHP arrays inside the `lang/{locale}/` directory (e.g., `lang/en/crm.php`, `lang/en/erp.php`, `lang/en/messages.php`).
- Keys must use a short, descriptive snake_case identifier rather than the full English string.
- **Example of Correct Usage**:
  ```php
  // ✅ CORRECT (Using structured PHP array keys)
  return redirect()->back()->with('success', __('crm.campaign_created'));
  ```
  ```tsx
  // ✅ CORRECT
  <Button>{__('erp.save_settings')}</Button>
  ```

### 3. Transitioning & Refactoring
- When encountering existing raw English translation strings, they should be refactored into structured PHP array keys.
- If a string belongs to a specific domain (like CRM, ERP, Admin, Freelance), it should be placed in the corresponding `domain.php` translation file.

### 4. Summary Checklist
- [ ] Are translation keys short and descriptive (e.g., `module.action_name`) instead of full sentences?
- [ ] Are translations placed in `lang/en/module.php` and `lang/ar/module.php` instead of `en.json`?
- [ ] Did you avoid using spaces or special characters in the translation key itself?



---
