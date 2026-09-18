---
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
- **Full Width Constraint:** Every Create and Edit form container (the card wrapping the form fields) MUST span the **full width** of the main content area (e.g., using `w-full` or `max-w-7xl` or equivalent full width styling). Never use a narrow layout (e.g., centering the form card with large left/right empty margins, or setting fixed narrow max-widths like `max-w-md` or `max-w-2xl` for the card).

### 3. What is NOT Allowed
- `shadcn/ui` Dialogs or Modals for creating/editing main resources.
- `shadcn/ui` Sheets or Offcanvas menus for data entry.
- Inline table editing for complex models (unless explicitly designed for bulk quick-edits).

### 4. Exceptions
The *only* exception to this rule is for extremely simple, single-field, or highly contextual sub-actions where navigating away would disrupt a larger workflow (e.g., adding a quick tag, renaming a file, or a quick status change confirmation). For all standard CRUD operations on main models (e.g., Invoices, Clients, Products, Transactions, Appointments), full pages are mandatory.

---

## 5. Modal-to-Page Migration & Elimination Protocol

When deprecating an existing modal, sheet, or dialog in favor of a dedicated full-page form:

1. **System-Wide Trigger Sweep**:
   - Grep the entire repository for references to the modal component, its state variables (e.g., `isTicketModalOpen`, `setIsModalOpen`), and action button labels.
   - Update every trigger button across all pages, layouts, navigation bars, headers, and banners to be a direct navigation link:
     ```tsx
     // Before (Forbidden):
     <button onClick={() => setIsModalOpen(true)}>Open Ticket</button>

     // After (Mandatory):
     <Link href={route('tickets.create')}>Open Ticket</Link>
     ```

2. **Preserve Context via Query Parameters**:
   - If the previous modal received entity context (e.g., `projectId`), pass it via query parameters on the navigation link:
     ```tsx
     <Link href={currentProject?.id ? `/tickets/create?project_id=${currentProject.id}` : '/tickets/create'}>
     ```
   - Ensure the controller's `create()` method extracts query parameters and passes them as initial props (e.g., `initialProjectId`).

3. **Immediate Dead Code Elimination**:
   - Remove all modal imports and `useState` flags from parent views.
   - Delete the obsolete modal component file immediately using `git rm`. Never leave dead modal components or zombie dialog triggers coexisting alongside dedicated pages.

