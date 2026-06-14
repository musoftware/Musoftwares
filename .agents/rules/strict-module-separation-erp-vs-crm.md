# Strict Module Separation (ERP vs CRM)

## Problem Statement
Coupling the CRM module directly to the ERP module's internal files (such as placing CRM translations into `lang/en/erp.php` or `lang/ar/erp.php`) breaks the independence of modules. It prevents modules from being installed, uninstalled, or maintained individually, and causes spaghetti code where changing one module breaks another.

## Rules & Guidelines

### 1. Zero Direct Dependencies
- The **CRM Module** must NEVER write files to, extend, or directly rely on the **ERP Module's** internal files unless explicitly exposing an API or interface.
- If the CRM module needs to communicate with the ERP module (e.g. creating an invoice for a lead), this must be done via **Domain Events**. You must dispatch an event from CRM and create a Listener in ERP (or vice versa). No direct instantiation of cross-module Services or Controllers.

### 2. Isolated Translations
- **Never** add CRM-specific translation keys to `erp.php`.
- All translations for the CRM module must be stored strictly within `lang/en/crm.php` and `lang/ar/crm.php` (or within the CRM module's own `lang` directory if the project uses a fully isolated module architecture).
- Use `__('crm.key_name')` in the frontend when referencing CRM texts.

### 3. Isolated Views and React Pages
- CRM pages must live in `resources/js/Pages/CRM`.
- ERP pages must live in `resources/js/Pages/ERP`.
- They must not cross-reference each other's components unless the component is placed in a shared `resources/js/Components` directory intended for global use (e.g., standard Shadcn UI components).

### 4. Database Foreign Keys
- If CRM tables reference ERP tables (e.g., `tenant_id` referencing `erp_tenants`), ensure the relationship is handled gracefully so that if the ERP module were theoretically removed, the CRM module would fail gracefully or rely on an interface. (Since ERP is the base, CRM relies on ERP, but ERP must NEVER rely on CRM).

### 5. Summary Checklist
- [ ] Are CRM translations placed in `crm.php` instead of `erp.php`?
- [ ] Is cross-module communication happening via standard Laravel Events instead of direct class calls?
- [ ] Are React pages strictly isolated in their respective `Pages/CRM` and `Pages/ERP` directories?



---
