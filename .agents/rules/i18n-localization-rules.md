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



---


---
trigger: model_decision
description: "Guidelines and requirements for using modular PHP translation arrays instead of global JSON language files."
---

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


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Always ensure every translation key used in views, controllers, or frontend components actually exists in the translation language files.
---

# Rule: No Missing Translations (Raw Keys in UI)

## Problem Statement
When a translation helper such as `__('admin.invoices')` is used, but the key `invoices` is not defined in the corresponding translation file, the application will render the raw key literal (e.g., `admin.invoices`) on the frontend. This is commonly seen in navigation menus (such as rendering `admin.invoices` instead of `Invoices` or `الفواتير` on routes like `http://127.0.0.1:8000/admin/dashboard?section=invoices`). This breaks the UI polish, causes confusion, and looks highly unprofessional.

## Rules & Guidelines

### 1. Mandatory Key Definition
- Whenever you introduce a new translation key in a Blade view, React/TSX component, Controller, or Menu configuration, you **MUST** immediately add the key to the corresponding language files.
- **Do not** write the usage of a translation key and assume it exists or leave it for later.

### 2. Multi-Language Parity
- The application supports multiple languages (typically English and Arabic).
- When a key is added, it must be added to **all** active locale files simultaneously to ensure that users of any language do not see raw keys.
- **Example**: If you add `__('admin.invoices')`, ensure both the English and Arabic `admin.php` language files are updated.

### 3. Validating Dynamic Keys
- Be especially careful with dynamically generated keys (e.g., `__('admin.' . $section)`). Ensure that **every possible value** of `$section` (like `invoices`, `clients`, `projects`) has a corresponding entry in the translation files.
- If a section or type is added to the database or configuration, its translation key must be added concurrently.

### 4. Shared and Module-Specific Translations
- Follow the project's modular translation file structure. If the translation belongs to a specific module (like `Modules/ERP`), add it to that module's translation directory rather than the global translation files, to keep features encapsulated.

### 5. Summary Checklist
- [ ] Have all new translation keys been added to the English language file?
- [ ] Have all new translation keys been added to the Arabic language file?
- [ ] Are dynamic keys (e.g., `__('section.' . $name)`) fully covered for all possible dynamic values?
- [ ] Has the frontend been checked to ensure no raw keys like `admin.invoices` are visible to the user?



---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Never mix Arabic and English languages in user-facing labels, document exports, system logs, or transaction notes.
---

# Rule: Never Mix Arabic and English Languages

## Problem Statement
Mixing languages in the same UI view, PDF document, status label, or system-generated log causes visual clutter, looks unprofessional, and breaks layout alignment (especially LTR vs RTL alignment).

## Rules & Guidelines

### 1. Pure UI Rendering (Zero Mixing Policy)
- A view must be rendered completely in **one** target language based on the current locale.
- **NEVER** write mixed-language labels or tooltips, such as:
  - ❌ `Invoice No (رقم الفاتورة)`
  - ❌ `الحالة (Status)`
  - ❌ `Total (الإجمالي)`
- Keep all UI text purely in the target language (e.g., `"Invoice No"` or `"رقم الفاتورة"`, but never both in the same label).

### 2. Localization Helpers (`__` or `trans`)
- All user-facing strings must be localized via translation helpers:
  - **Laravel backend:** `__('messages.key')` or `trans('messages.key')`
  - **Inertia / React frontend:** Use localized props passed from the controller or a front-end translation helper.
- Never hardcode static strings directly in views or components unless they are technical symbols (e.g., standard currency symbols like `$` or code formats like `INV-2026-001`).

### 3. Status Labels and Badges
- Invoice and transaction statuses (e.g., `paid`, `unpaid`, `sent`, `overdue`, `cancelled`) must be displayed as localized labels, never raw database values or mixed translation strings:
  - **English Mode:** `Paid`, `Unpaid`, `Sent`, `Overdue`, `Cancelled`
  - **Arabic Mode:** `مدفوع`, `غير مدفوع`, `مرسل`, `متأخر`, `ملغي`
- Never format status labels as mixed text (e.g., `Paid (مدفوع)`).

### 4. Financial Records & PDFs (Invoices, Expenses, Transactions)
- Invoices generated for clients must be generated in a single language matching the client's preferred language. Do not mix English layout headers with Arabic item descriptions or vice-versa.
- PDF exports must respect strict font rendering for the selected language to prevent broken glyphs or question marks (`?`).

### 5. System Logs and Transaction Notes
- Auto-generated transaction reasons and notes logged in the database must remain in the primary language of the action context (e.g., `"Subscription via Kashier online payment (Trx: 1234)"` or its complete Arabic equivalent, never a hybrid).



---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Always place translation keys in the correctly scoped locale file according to the feature domain (e.g., admin.php, erp.php, general.php).
---

# Rule: Translation File Scoping

## Problem Statement
Adding unrelated translation keys into a domain-specific translation file (such as placing generic `admin` or `finance` translations into `erp.php`) pollutes the file, violates module boundaries, and makes the application harder to localize. For example, the `Payment Links` feature belongs to `Admin/Finance`, but translations were incorrectly added to the `erp.php` file, creating coupling between unrelated domains.

## Rules & Guidelines

### 1. Identify the Correct Domain Namespace
Before adding a translation key, determine the module or section the feature belongs to:
- **ERP Module** (e.g., Projects, Clients, Timers, Invoices, Debts): Use `erp.php`.
- **Admin Section** (e.g., Dashboard, Payment Links, Finance, Settings, Subscriptions): Use `admin.php`.
- **General Actions/Labels** (e.g., "Save", "Cancel", "Delete", "Amount", "Title"): Use `general.php` or `messages.php` as appropriate.
- **Frontend/Guest** (e.g., Landing Pages, Client Portal): Use `frontend.php` or a dedicated domain file.

### 2. Never Pollute `erp.php` with Generic Strings
- Do not place generic actions like `save`, `cancel`, or `copy_link` into `erp.php` simply because you are working on an ERP page. Check if they exist in `general.php` first (e.g., `__('general.save')`).
- If a feature explicitly belongs to the `Admin` directory (e.g., `Admin/Finance/PaymentLinks`), its specific translation keys (like `payment_link_title_placeholder`) MUST go into `admin.php`, NOT `erp.php`.

### 3. Usage in Views
Always prefix your translation keys with the correct domain:
```tsx
// ❌ INCORRECT (Using ERP namespace for an Admin feature)
<Label>{__('erp.payment_links')}</Label>

// ✅ CORRECT (Using Admin namespace for an Admin feature)
<Label>{__('admin.payment_links')}</Label>

// ✅ CORRECT (Using General namespace for a common action)
<Button>{__('general.save')}</Button>
```

### 4. Adding Keys to Multiple Languages
When you define the correct domain, you **MUST** update that specific file across all locales (e.g., `lang/en/admin.php` and `lang/ar/admin.php`).

### 5. Summary Checklist
- [ ] Have I identified the correct domain (Admin, ERP, General) for my new feature?
- [ ] Am I using `general.php` for common buttons/labels rather than duplicating them in domain files?
- [ ] Did I verify that I am NOT adding Admin/Finance feature translations into `erp.php`?



---


