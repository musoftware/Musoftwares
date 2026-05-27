---
name: Full Page Forms Policy
description: Enforces the use of full, dedicated pages for "Add" and "Edit" forms across the ERP and general system, explicitly forbidding the use of modals or sliding sheets.
---

# Full Page Forms Policy

## Core Principle
Across the ERP system and the broader Musoftware platform, **all "Add" and "Edit" actions must be handled on full, dedicated pages**. The use of Modals, Dialogs, or Offcanvas/Sliding Sheets for standard form submissions is strictly prohibited for main entities.

## Why?
1. **Focus:** Data entry for complex entities requires the user's full attention. A dedicated page provides a distraction-free environment.
2. **Space:** Modals and sheets constrain horizontal and vertical space, leading to cramped forms, excessive scrolling within a small container, and poor mobile experiences.
3. **Deep Linking:** Full pages have dedicated URLs (e.g., `/erp/invoices/create` or `/erp/invoices/1/edit`), allowing users to bookmark, refresh, or share the link to a specific form.
4. **Validation Handling:** Complex validation errors are easier to display and manage on a full page compared to a modal, which might need to scroll or resize awkwardly.

## Implementation Guidelines

### 1. Routing
Always define explicit routes for creating and editing resources:
- `GET /resource/create` -> Renders the full page "Add" form.
- `POST /resource` -> Handles the submission.
- `GET /resource/{id}/edit` -> Renders the full page "Edit" form.
- `PUT/PATCH /resource/{id}` -> Handles the update.

### 2. UI/UX
- **Navigation:** The form page must include a clear "Back" or "Cancel" button to return to the previous index or detail view.
- **Header:** Use the standard page header component (e.g., `<x-client.section-header>`) with a clear title like "Create [Entity]" or "Edit [Entity]".
- **Layout:** Wrap the form in an appropriate card layout (e.g., `<x-client.form-card>`) as per the standard design system.

### 3. What is NOT Allowed
- `shadcn/ui` Dialogs or Modals for creating/editing main resources.
- `shadcn/ui` Sheets or Offcanvas menus for data entry.
- Inline table editing for complex models (unless explicitly designed for bulk quick-edits).

### 4. Exceptions
The *only* exception to this rule is for extremely simple, single-field, or highly contextual sub-actions where navigating away would disrupt a larger workflow (e.g., adding a quick tag, renaming a file, or a quick status change confirmation). For all standard CRUD operations on main models (e.g., Invoices, Clients, Products, Transactions, Appointments), full pages are mandatory.
