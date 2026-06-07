---
name: frontend-development
description: Frontend development standards including React form handling, Inertia pages, and reusable UI components.
---

# Inertia Page Rules

## Rules
1. Export pages as default: `export default function PageName() { ... }`
2. Wrap pages in `AuthenticatedLayout` or `ERPLayout`.
3. Define TypeScript interfaces for `props`.
4. Flash messages are handled globally; do not render raw flash alerts inside the page body.



---


# React Form Standards

## Rules
1. Always use `@inertiajs/react` `useForm`.
2. Handle loading states by disabling the submit button (`processing` flag).
3. Display validation errors inline below fields using `<InputError message={errors.field} />`.



---


# Reusable Dialog Patterns

## Rules
1. Use Shadcn `Dialog` or `Sheet` components.
2. Manage dialog open/close state via local component state or URL query parameters (for deep linking).



---


# UI Table Patterns

## Rules
1. **Single Action Menu**: NEVER place multiple action buttons inline. Always use a single `...` (ellipsis) DropdownMenu for row actions to preserve horizontal space.
2. Support mobile responsive stacking.
3. Avoid loading >100 rows; use Laravel pagination (`paginate(15)`).



---


