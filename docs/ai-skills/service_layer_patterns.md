# Service Layer Patterns (AI Skill)

Use these patterns to ensure code is loosely coupled, testable, and secure.

## 1. Fat Services, Thin Controllers
- Controllers should only handle HTTP validation (`FormRequest`) and HTTP responses.
- Move business logic to specific Service classes in `Modules\{Module}\Services\`.
- Example: Creating a project shouldn't happen in `ProjectController@store`. It should pass validated data to `ProjectService->createProject($data)`.

## 2. LedgerService (Canonical Bookkeeping)
- `Modules\Core\Services\LedgerService`
- **Purpose:** Double-entry journal system. Every financial state change (wallet deposit, invoice payment, subscription fee) must invoke this service.
- **Rule:** Never execute raw DB updates on wallet balances if the money movement spans two accounts. Use `recordTransaction`.

## 3. ActivityService (Audit Logging)
- `Modules\Core\Services\ActivityService`
- **Purpose:** Immutable audit logging for the SaaS ecosystem.
- **Rule:** Do not call `ActivityService::log()` directly from Controllers.
- **Pattern:** Fire a domain event (`App\Events\ResourceCreated`). The `App\Listeners\ActivityEventListener` translates the domain event into a generic `ActivityService::log()` call.

## 4. RecurringService (Automation)
- `Modules\Core\Services\RecurringService`
- **Purpose:** Handling cron-based generation of invoices, income, and expenses.
- **Rule:** Never assume an active request lifecycle. Services triggered via Artisan commands must handle their own tenancy contexts or rely on explicitly passed IDs (e.g., bypassing `TenantModel` global scopes using `withoutGlobalScopes()` if processing across all tenants).
