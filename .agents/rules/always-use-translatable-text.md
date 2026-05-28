---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Always use localization helpers (__ or trans) and never use hardcoded English text in user-facing UI messages, emails, exception aborts, flash success/error messages, or dynamic logs.
---

# Rule: Always Use Translatable Text, Never Hardcode English

## Problem Statement
Hardcoding raw English text in views, controller redirect messages (flash success/error messages), validation errors, exceptions, or notification emails violates localization policies. It makes the application impossible to translate cleanly and breaks UI and user flow consistency when switching locales.

## Rules & Guidelines

### 1. Backend Controllers & Request Validation
- **Never** return hardcoded English string literals in redirect flash messages, validation messages, or JSON API responses.
- **Always** wrap user-facing status messages, errors, and toast text with the `__()` translation helper.
- **Example**:
  ```php
  // ❌ INCORRECT (Hardcoded English)
  return redirect()->back()->with('success', 'Client created successfully.');
  
  // ✅ CORRECT (Translatable/Localized)
  return redirect()->back()->with('success', __('erp.client_created_success'));
  ```

### 2. HTTP Exception Aborts & Exceptions
- When aborting a request or throwing a user-facing exception, the message must be translatable.
- **Example**:
  ```php
  // ❌ INCORRECT (Hardcoded Exception message)
  abort(403, 'Unauthorized access to client.');
  
  // ✅ CORRECT (Translatable/Localized)
  abort(403, __('errors.unauthorized_client'));
  ```

### 3. Frontend React / TSX Components
- **Never** write plain English strings directly inside JSX tags or component state initializers (like placeholders, tooltips, or labels).
- All static labels must be dynamically translated. Use standard frontend translation helpers or pass pre-translated strings from the controller as page props.
- **Example**:
  ```tsx
  // ❌ INCORRECT (Hardcoded static label)
  <button>Save Changes</button>
  
  // ✅ CORRECT (Using localization helper/prop)
  <button>{__('Save Changes')}</button> 
  // or
  <button>{translations.save_changes}</button>
  ```

### 4. System Logs & Activity Logs
- Activities logged to the database that are displayed to users (e.g., in a "Recent Activities" timeline) must be stored in a translatable key format or generated dynamically using translation keys at the rendering layer.
- **Example**:
  ```php
  // ❌ INCORRECT (Hardcoded narrative text)
  ActivityLogger::log('client_created', "Client '{$client->name}' was added.");
  
  // ✅ CORRECT (Log action key, format dynamically or log with trans key)
  ActivityLogger::log('client_created', __('logs.client_added', ['name' => $client->name]));
  ```

### 5. Email Templates & Notifications
- All mailables, notification contents, and text lines must use translation files.
- Avoid using `$this->line('Thank you for using our application!')` with hardcoded strings. Use `$this->line(__('mail.thank_you'))`.
