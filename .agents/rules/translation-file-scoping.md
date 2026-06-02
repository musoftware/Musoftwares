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
