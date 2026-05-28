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
