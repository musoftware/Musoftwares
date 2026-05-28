# Rule: No Fat Architecture (Thin Controllers & Thin Services)

## Problem Statement
Writing monolithic business logic, complex data aggregations, or multi-step processes directly within controller methods OR a single "god" service leads to a "fat architecture". This practice makes the codebase difficult to maintain, hard to test, and violates the Single Responsibility Principle. 

Controllers should strictly act as an HTTP routing and coordination layer. Services should act as orchestrators or handle a single specific domain, and they should delegate granular tasks to other specialized services, repositories, or private helper methods.

## Rules & Guidelines

### 1. Controllers are for HTTP Coordination Only
- **Responsibilities:** A controller method should only handle receiving the request, delegating work to a service or model, and returning an HTTP response or Inertia view.
- **NEVER** write raw database queries, complex conditionals, loops, or core business workflows inside controller actions. Keep controller methods as short and clean as possible (ideally under 10-15 lines).

### 2. No Fat Services & Avoid Over-Engineering
- **Single Responsibility:** A service should not become a "god class" that handles 10 different unrelated things (e.g. processing payments, fetching tickets, and calculating dashboard metrics in one giant method).
- **Decomposition:** If a service method is getting too large (e.g., aggregating data for a dashboard), break it down into smaller, focused `private` helper methods, or delegate to specialized domain services (e.g., `TicketService`, `InvoiceService`).
- **Do Not Over-Engineer:** While services should be thin, do not create unnecessary layers or a separate class for every single action if private helper methods suffice. Balance clean code with pragmatism.

### 3. Form Requests for Validation
- Always use custom **FormRequest** classes for request input validation and initial authorization logic.
- **Do not** validate request data directly inside the controller using `$request->validate()` unless it is a trivial single-field check.

### 4. Policies for Authorization
- Use Laravel **Policies** to handle all permission and ownership authorization checks (e.g., `$this->authorize('update', $client)`).
- **Never** write complex permission logic (e.g., checking roles, user IDs) inside the controller methods.

### 5. API Resources / DTOs for Response Formatting
- When returning JSON or passing structured data to the frontend (like Inertia views), use Laravel **API Resources** (`JsonResource`) or DTOs to strictly format the response payload.
- **Never** return or pass raw Eloquent models directly to the view or API response. This prevents accidental leakage of sensitive database columns.

### 6. Scopes for Database Queries
- Instead of writing complex `where`, `join`, or `orderBy` clauses in the controller or a high-level service, define **Query Scopes** on your Eloquent models or use dedicated Repository classes.
- **Example**: Use `Client::active()->recent()->get()` instead of chaining multiple query builder methods inline.
