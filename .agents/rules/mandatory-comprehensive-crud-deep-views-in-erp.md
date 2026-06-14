# Rule: Mandatory Comprehensive CRUD & Deep Views in ERP

## Problem Statement
Developing modules or sections in the ERP system (e.g., `erp/dashboard?section=transactions`) that only display a basic list (index) without deeper interaction creates an incomplete, toy-like experience. ERP systems require comprehensive data management. When developers skip "Show", "Edit", or "Delete" views, it limits the user's ability to effectively use the platform and manage their enterprise resources.

## Rules & Guidelines

### 1. Full CRUD is the Minimum Standard
- Every entity, resource, or section in the ERP **MUST** implement at least full CRUD (Create, Read/Show, Update, Delete) unless there is a strict, documented business rule preventing it (e.g., immutable ledger entries might not allow editing, but they still require a detailed Show view).
- Do not stop at building an `Index` page with a simple data table. You must build out the complete resource lifecycle.

### 2. Mandatory "Show" (Detail) Views
- **No dead-end lists.** Any table or list of records (such as Transactions, Invoices, Clients, Projects, Tasks, etc.) must allow the user to click into a detailed `Show` view.
- For example, if there is a page at `/erp/dashboard?section=transactions` displaying a table of transactions, there **MUST** be a dedicated `/erp/transactions/{id}` route and a corresponding `Show` UI page that displays the full transaction details, timeline, metadata, associations, and relevant actions.

### 3. Implementation Requirements
- **Backend**: Ensure controllers have standard methods implemented (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`).
- **Frontend**: Provide full Inertia/React components for:
  - `Index` (List with search, pagination, and filters)
  - `Create` (Form for new entry)
  - `Edit` (Form populated with existing data)
  - `Show` (Detailed read-only view with rich context, related data, and actions)
- **Routing**: Define full resource routes (e.g., `Route::resource('transactions', TransactionController::class)`) instead of just a single `get` route for the index.

### 4. Advanced Workflows Over Basic Forms
- Whenever appropriate, enhance basic CRUD with advanced workflows. For instance, rather than a simple form to edit a status, implement specific action buttons (e.g., "Send Invoice", "Mark as Paid", "Download PDF").
- The "Show" page should act as a micro-dashboard for that specific entity, presenting not just fields, but also related activity logs, statuses, and contextual actions.

### 5. Summary Checklist
- [ ] Does the resource have an `Index` page with a table/list?
- [ ] Is there a clickable link or button in the table taking the user to a dedicated `Show` page for the specific record?
- [ ] Are there `Create` and `Edit` forms implemented properly?
- [ ] Is there a way to safely `Delete` or archive the record?
- [ ] Are all these routes fully handled in both the Controller and the frontend views?



---
