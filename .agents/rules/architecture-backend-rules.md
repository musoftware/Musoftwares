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



---


---
name: Module Separation Rules (ERP & CRM)
description: Strict architectural boundaries between ERP and CRM modules. Must communicate via Events. No shared translations.
---

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


# Rule: ERP vs Admin Boundaries

## Problem Statement
There is a clear architectural boundary between the "Admin/Main System" and the "ERP Module". Confusing the two leads to incorrect edits—for example, editing an Admin controller when the user intended to modify an ERP tenant feature, or vice versa. This rule enforces strict separation of contexts based on file paths, users, and functionality to prevent accidental modifications in the wrong scope.

## Rules & Guidelines

### 1. Admin / Main System (Platform Management)
- **Purpose**: Used by the platform owners (Super Admins, Moderators) to manage the overall platform, users, global subscriptions, marketplace, addons, and support tickets.
- **File Locations**:
  - Backend Controllers: `app/Http/Controllers/Admin/`
  - Frontend Views (React/Inertia): `resources/js/Pages/Admin/`
  - Routes: `routes/admin.php` or `routes/web.php` (for global non-module specific routes).
- **Target Audience**: Platform Administrators.
- **Key Entities**: Users, Global Subscriptions, Support Tickets, Marketplace Plugins.

### 2. ERP Module (Tenant Business Management)
- **Purpose**: Used by the end Users (Business Owners/Tenants) to manage their own clients, projects, invoices, expenses, and internal business operations.
- **File Locations**:
  - Backend Controllers: `Modules/ERP/Http/Controllers/`
  - Frontend Views (React/Inertia): `Modules/ERP/resources/js/Pages/`
  - Routes: `Modules/ERP/routes/`
  - Models: `Modules/ERP/Models/`
- **Target Audience**: Business Owners (Tenants) and their employees.
- **Key Entities**: ERP Clients, Projects, Invoices, Transactions, Expenses.

### 3. Context Isolation Rules
- **Strict Boundary**: **NEVER** modify a file in `Modules/ERP/` when the user asks to change something in the "Admin Dashboard".
- **Strict Boundary**: **NEVER** modify a file in `app/Http/Controllers/Admin/` or `resources/js/Pages/Admin/` when the user asks to change something in the "ERP" or for user-level "Clients/Projects/Invoices" unless specifically requested.
- **Ambiguity Check**: If the user request is ambiguous (e.g., "fix the dashboard" or "update the invoice page"), use context clues (like currently open files or error logs) or ask for clarification before making any code changes.



---


# Rule: Tri-Path Validation (Happy, Edge, Security)

## Problem Statement
When debugging issues or ensuring a feature "works", focusing solely on the "Happy Path" (the optimal, expected scenario) leads to missing hidden bugs, data inconsistencies, and security vulnerabilities. Features may appear functional on the surface but fail drastically when backend data is stale or malicious payloads are sent.

## Rules & Guidelines

Whenever requested to "ensure something works", "test a feature", or "debug a problem", you MUST explicitly evaluate and document your checks against the following **Three Paths**:

### 1. The Happy Path (Normal Execution)
- **Objective:** Ensure the primary intended behavior functions seamlessly.
- **Checks:**
  - Do all UI components render without breaking?
  - Do frontend endpoints point to the correct Backend routes (e.g., checking Ziggy aliases like `admin.transactions.create` vs `transactions.create`)?
  - Does the core transaction/submission save correctly to the database?

### 2. The Unhappy Path (Edge Cases & Data States)
- **Objective:** Anticipate failure states, empty states, and corrupted or stale environment data.
- **Checks:**
  - What happens if the database query returns an empty array `[]` or `null`? (e.g., the cronjob hasn't run, missing fallback exchange rates).
  - How does the UI handle `undefined` or missing relations?
  - Are strict type comparisons causing issues? (e.g., parsing a JSON boolean `true` against a string `'true'`).
  - Does the code break if a user has no projects, zero balance, or a missing currency setting?

### 3. The Security Path (Anti-Hacking & Exploits)
- **Objective:** Prevent malicious actors from bypassing logic or manipulating data.
- **Checks:**
  - **Authorization:** Does the endpoint verify that the user has the right permissions/subscriptions to perform the action?
  - **Payload Integrity:** Are we blindly trusting the frontend? (e.g., updating a balance using a frontend-supplied `amount` instead of recalculating it securely in the backend).
  - **Data Leakage:** Does an exception or error message expose database schema details to the user? (Refer to the `never-expose-laravel-footprint` rule).
  - **Validation:** Are all inputs strictly validated using Laravel Form Requests?

### Enforcement Checklist
Whenever you conclude a debugging session or verify a feature, explicitly confirm:
- [ ] Happy Path verified.
- [ ] Edge Cases and empty states checked.
- [ ] Security and payload integrity enforced.



---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: For any logic written in models, controllers, or services, fully cover it with unit and feature tests, handle all related UI changes, and create all necessary supporting structures (migrations, factories, requests, resources).
---

# Rule: Always Write Unit/Feature Tests and Handle UI

## Problem Statement
Introducing code changes to models, controllers, or services without test coverage increases regression risk and makes it difficult to verify correct behavior. Additionally, failing to complete the implementation cycle by ignoring frontend updates or omitting related supporting classes (requests, resources, migrations) leaves features broken or half-implemented.

## Rules & Guidelines

### 1. Mandatory Test Coverage
- **Unit Tests**: Write unit tests for custom calculations, status checking helper methods, service class logic, or domain rules that can be run in isolation.
- **Feature Tests**: Write feature tests for any new or modified routes, controller actions, or middleware logic to ensure proper response status, redirected routes, views, database state validation, event triggers, and permission boundaries.
- **Test File Organization**:
  - Keep PHPUnit tests under `Tests/Unit` or `Tests/Feature` matching Laravel conventions.
  - For modules, keep tests within the module's own `Tests` folder (e.g., `Modules/ERP/Tests/Feature` or `Modules/ERP/Tests/Unit`).

### 2. Complete UI Handling
- If a backend logic change has any impact on the user interface, you must update or implement the corresponding frontend views (e.g., React/TSX pages, Blade views).
- Handle page state, validation error rendering, success alerts/toasts, loader indicators, and disabled states on forms.
- Ensure Inertia components receive the correct updated props from controllers.

### 3. Create All Related Artifacts
- Never write code in isolation. If a feature needs a data change, create:
  - **Migrations**: Secure schema updates.
  - **Factories & Seeders**: To assist in manual and automated testing.
  - **Request Classes**: For input validation.
  - **Resource Classes**: For structured API/Inertia data representation.
  - **Translation Keys**: In accordance with the `always-use-translatable-text` rule.



---


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


# Rule: Migration Editing and Data Transfer

## Problem Statement
When modifying database schemas, it is an absolute requirement to keep module migrations self-contained and pristine. Creating random `add_column_to_table` migrations inside the main Laravel `database/migrations` directory for tables that belong to a module is strictly forbidden. It pollutes the core schema and breaks the modular architecture.

## Rules & Guidelines

### 1. Module Migrations (STRICT POLICY)
- **Anything related to a Module MUST stay inside the Module.**
- If you need to modify a table schema that belongs to a **Module** (e.g., `Modules/{ModuleName}/Database/Migrations/`), **you MUST edit the original migration file directly.**
- **NEVER** create a new external migration (e.g., `add_x_to_module_table`) inside `database/migrations` or anywhere else. If a column needs to be added, open the original module migration file where the table was created and add the column directly there.

### 2. Main Laravel Migrations
- If you need to modify tables or columns defined in the **Main Laravel Migrations** (`database/migrations/` like `users` or `sessions`), **NEVER edit the original file**.
- For main framework tables, you must **create a new migration** (e.g., `php artisan make:migration ...`) to implement your changes, preserving the core framework history.

### 3. Migrating Legacy Data
- When writing migrations that transfer, migrate, or seed data from legacy systems (like old services) into new structures, write these data transfer migrations **inside the relevant Module** (e.g., `Modules/{ModuleName}/Database/Migrations/`).
- **Do not** place legacy data migration scripts in the main `database/migrations` directory.



---


---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx,json}"
description: Ensure the Laravel framework footprint, default error messages, stack traces, session cookie names, and header flags are never exposed, making it impossible to fingerprint the application as a Laravel app.
---

# Rule: Never Expose Laravel Footprint & Default Errors

## Problem Statement
Exposing framework-specific footprints (such as default Laravel/Symfony exception pages, raw SQL database exceptions, standard cookies like `laravel_session`, or HTTP headers like `X-Powered-By`) allows malicious actors to fingerprint the tech stack. Once an attacker knows the project runs Laravel, they can launch targeted exploits. We must completely hide and customize all errors, headers, cookies, and exceptions to prevent this.

## Rules & Guidelines

### 1. Zero Database/SQL Leakage in Exceptions
- **Never** catch an exception and return the raw message (e.g. `$e->getMessage()`) or stack trace to the user, as this can leak database schema details (table names, columns, queries).
- In pure API routes (`api/*`), use custom error responses with localized generic JSON messages instead.
- **CRITICAL INERTIA RULE**: If the controller is handling a standard web form submission via Inertia (e.g., React `useForm().post()`), you **MUST NOT** return a raw JSON response (`response()->json()`). Inertia requires a valid HTTP redirect (e.g., `redirect()->back()->with('error', '...')`) to trigger a page refresh and display flash messages. Only use `response()->json()` for pure API endpoints or specific async dropdown searches.
- For database query errors in APIs, catch them and return a generic system error JSON. For Inertia web routes, catch them and redirect back with a generic flash message.
- **API Endpoint Example**:
  ```php
  // ❌ INCORRECT (Leaks database exception details)
  try {
      $client->save();
  } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
  }
  
  // ✅ CORRECT (Generic, localized, secure response)
  try {
      $client->save();
  } catch (\Exception $e) {
      Log::error("Failed to save client: " . $e->getMessage());
      return response()->json(['error' => __('errors.database_error')], 500);
  }
  ```

### 2. Uniform JSON Response Formatting & Graceful Route Model Errors
- Ensure the global exception handler (configured in `bootstrap/app.php`) intercepts all uncaught exceptions (including `NotFoundHttpException`, `ModelNotFoundException`, `ValidationException`, `QueryException`) and returns standardized, generic error responses without exposing framework classes or path traces.
- **Graceful Modular Error Pages (Best Practice)**: For web requests, when a key ERP model is not found (e.g. accessing a non-existent project like `/erp/projects/2/edit`), do NOT crash with a default 404 page, and do NOT redirect. Instead, return a 404 response that renders an Inertia error page using the normal ERP Layout (`ERPLayout`).
- **Do Not Write in app.php for Modules**: To keep modules decoupled, register these exception renderers inside the module's Service Provider (e.g. `ERPServiceProvider`) rather than the global `bootstrap/app.php`.
- **Example Service Provider Registration**:
  ```php
  // In Modules/ERP/Providers/ERPServiceProvider.php:
  use Illuminate\Contracts\Debug\ExceptionHandler;
  use Illuminate\Database\Eloquent\ModelNotFoundException;
  use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

  public function boot(): void
  {
      // ...
      $this->registerExceptionRenderers();
  }

  protected function registerExceptionRenderers(): void
  {
      $handler = $this->app->make(ExceptionHandler::class);

      $handleModelNotFound = function ($model, \Illuminate\Http\Request $request) {
          $mapping = [
              \Modules\ERP\Models\Project::class => [
                  'section' => 'projects',
                  'message' => __('erp.project_not_found'),
              ],
              \Modules\ERP\Models\TenantClient::class => [
                  'section' => 'clients',
                  'message' => __('erp.client_not_found'),
              ],
          ];

          if (array_key_exists($model, $mapping)) {
              $section = $mapping[$model]['section'];
              $message = $mapping[$model]['message'];

              if ($request->expectsJson() || $request->is('api/*')) {
                  return response()->json([
                      'status' => 'error',
                      'message' => $message,
                  ], 404);
              }

              // Renders inside the normal ERP layout, while keeping the 404 status code!
              return \Inertia\Inertia::render('ERP/Errors/NotFound', [
                  'message' => $message,
                  'section' => $section,
              ])->toResponse($request)->setStatusCode(404);
          }

          return null;
      };

      $handler->renderable(function (ModelNotFoundException $e, \Illuminate\Http\Request $request) use ($handleModelNotFound) {
          return $handleModelNotFound($e->getModel(), $request);
      });

      $handler->renderable(function (NotFoundHttpException $e, \Illuminate\Http\Request $request) use ($handleModelNotFound) {
          $previous = $e->getPrevious();
          if ($previous instanceof ModelNotFoundException) {
              return $handleModelNotFound($previous->getModel(), $request);
          }
          return null;
      });
  }
  ```

### 3. Override Laravel Defaults for Cookies, Sessions, and Headers
- Customize cookie names in configuration files (`config/session.php` and `config/auth.php`) to avoid standard Laravel defaults.
- Do NOT use standard `laravel_session`, `laravel_token`, or `XSRF-TOKEN` cookie/header names directly. Customise them to generic names (e.g., `web_sess`, `app_token`, `app_xsrf_token` via configuration).
- Strip headers like `X-Powered-By: PHP` or `X-Powered-By: Laravel` by using a global middleware.
- **Example Middleware**:
  ```php
  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;

  class RemoveSecurityHeaders
  {
      public function handle(Request $request, Closure $next): Response
      {
          $response = $next($request);
          
          // Remove PHP/Laravel footprint headers
          if (method_exists($response, 'header')) {
              $response->header('X-Powered-By', '');
              $response->headers->remove('X-Powered-By');
          }
          
          return $response;
      }
  }
  ```

### 4. Custom Error Views
- Custom Blade error templates MUST be created at `resources/views/errors/` (e.g., `404.blade.php`, `500.blade.php`, `403.blade.php`, `419.blade.php`, `503.blade.php`).
- These views must NOT use default Laravel styling, SVG graphics, fonts, or footer links that reference Laravel or Symfony. They should be styled with the project's unique design system or be completely generic.

### 5. Disable/Guard Debugging and Admin Utilities
- Never keep Telescope, Horizon, Log Viewer, or Ignition endpoints accessible in production.
- Dev tools should be strictly registered inside `AppServiceProvider` using env check:
  ```php
  if ($this->app->environment('local')) {
      $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
  }
  ```
- Make sure debug-only packages are in `require-dev` in `composer.json` to prevent deployment to production.



---


---
trigger: always_on
glob: "**/*.{php,env,json,yml,yaml,sh}"
description: Architecture must strictly rely on a simple hosting stack consisting only of Apache, MySQL, and Cronjobs. Redis, Elasticsearch, external message queues, Node.js backends, or any other infrastructure dependencies are strictly forbidden.
---

# Rule: Simple Hosting Stack (Apache, MySQL, Cronjobs Only)

## Problem Statement
Introducing complex infrastructure dependencies (like Redis, Elasticsearch, Node.js background processors, Supervisor, RabbitMQ, SQS, or specialized runners) makes the application difficult to host on simple, traditional, or standard VPS hosting environments. The architecture must remain ultra-portable and lightweight, relying exclusively on basic standard hosting features: Apache, MySQL, and standard Linux Cronjobs.

## Rules & Guidelines

### 1. Strictly Allowed Technologies
You may only rely on the following infrastructural components for production deployment:
- **Web Server**: Apache (with `.htaccess` for rewrites and rules).
- **Database**: MySQL (or MariaDB).
- **Task Scheduling**: Standard Linux Cronjobs (`crontab`).
- **Runtime**: PHP.

### 2. Forbidden Technologies
Do **NOT** introduce, configure, or require any of the following for the application to function:
- **Redis / Memcached**: Do not use in-memory key-value stores for caching or sessions.
- **Message Brokers**: Do not use RabbitMQ, AWS SQS, Kafka, etc.
- **Search Engines**: Do not use Elasticsearch, Meilisearch, or Algolia as hard requirements.
- **Node.js (Runtime)**: Do not require persistent Node.js processes (e.g., SSR servers, WebSocket servers running in Node, PM2). *Note: Node.js/NPM is perfectly fine for local compilation/build steps (like Vite), but NOT for production server execution.*
- **Process Monitors**: Do not rely on Supervisor, Systemd, or PM2 to keep daemon workers alive. All background processing must be triggerable via Cronjobs.

### 3. Caching and Sessions
- **Sessions**: Must use the `database` or `file` session driver. Do not use `redis` or `memcached`.
- **Cache**: Must use the `database` or `file` cache driver. Do not use `redis` or `memcached`.

### 4. Background Jobs and Queues
- **Queue Driver**: All asynchronous jobs must use the `database` driver. 
- **Queue Execution**: Since Supervisor or continuous daemon processes (like `php artisan queue:work`) are not always supported on simple shared hosting, queue processing must be designed to be triggered by Cronjobs. (e.g., scheduling a command that runs `php artisan queue:work --stop-when-empty` every minute, or using the Laravel Task Scheduler to run queued jobs).

### 5. WebSockets & Real-Time
- If real-time features are necessary, they must either use a third-party managed service (like Pusher API) or be designed to fallback gracefully to standard HTTP polling if a local WebSocket server (which requires a persistent daemon) cannot be run. Do not require a persistent local WebSocket daemon (like Laravel Reverb) as a strict dependency for the application to boot and function.

### 6. Summary Checklist
- [ ] Are we using the `database` or `file` driver for Cache and Session?
- [ ] Is the Queue driver set to `database`?
- [ ] Have we avoided introducing Redis, Meilisearch, or other external infrastructure services?
- [ ] Can all background tasks be scheduled and executed purely via standard Cronjobs?



---


# Rule: Always Refer to the Old System for Feature Parity

## Problem Statement
When developing or migrating features into the new system (e.g., React/Inertia), there is a high risk of missing small but critical features, buttons, calculations, or behaviors that existed in the legacy system (e.g., old Blade templates, controllers). Furthermore, if the AI agent relies only on analyzing a single file (like a controller) without tracing the data flow to the UI (React Props) or searching across the whole repository, UI bugs and missing features will occur.

## Rules & Guidelines

### 1. Mandatory Deep Legacy Search (Global Grep)
- **NEVER** assume a feature's full logic is contained within a single `Controller` or `Livewire` component.
- **Deep Search:** You MUST use global `grep_search` across the legacy system's directories (`app`, `resources/views`, etc.) for keywords related to the feature to catch generic controllers, partial views, or traits that might contain missing features like `external_pay`, `exchange`, etc.

### 2. Strict UI-to-Backend Data Tracing
- You cannot just verify that a Controller method is "working". You **MUST** trace the data flow all the way to the frontend React component.
- **Props and Types:** Ensure the data passed from the controller matches what the React component expects (e.g., if the UI expects `currency.code`, ensure the controller isn't just passing `currency_id`).
- **Route Parameters:** Check all UI Action Links and Form submissions (e.g., `<Link href={...}>` or `router.post()`) and verify the parameters match the expected Request parameters in the backend (e.g., `?client_id=` vs `?user=`).

### 3. Feature Parity is the Goal
- The technical stack may change, but the **Final Result and User Experience must have 100% feature parity**.
- **Do not miss the details:** If the old system had a specific button, specific discount logic, or external links, the new React component MUST include them.

### 4. Execution Steps for Any Review/Migration
1. **Analyze User Request:** What is the feature?
2. **Deep Search (grep):** Search globally for the feature in the old codebase.
3. **Map Features:** Note every button, input, action, and calculation present in the legacy views and logic.
4. **Trace Frontend:** In the new system, verify the React component correctly receives, maps, and submits data back to the Controller without mismatches.
5. **Implement:** Build the equivalent containing ALL the mapped features.

### 5. Summary Checklist
- [ ] Did I use global `grep` to find all parts of the old feature instead of relying on one file?
- [ ] Did I verify the frontend React component data types and parameters against the Backend Controller?
- [ ] Does the new implementation include every action button that was present in the old system?



---


# Rule: Mandatory Local SQLite Database for Scraper Tools

## Problem Statement
Web scraping operations can be long-running and fragile. If a page is accidentally refreshed, the browser crashes, or a network error occurs, any scraped data held only in memory (RAM) is entirely lost. This forces the user to restart the scraping process from scratch, wasting significant time and resources. Furthermore, data without timestamps lacks historical context.

## Rules & Guidelines

### 1. Mandatory Local SQLite Database
- **Never** rely solely on in-memory storage (e.g., arrays, variables) for scraped data.
- **Every** scraper tool must instantiate and utilize a local SQLite database (or an equivalent local relational storage if restricted by environment) on the user's PC.
- The database must remain strictly local to the user's machine. It must **not** be hosted on, or primarily saved to, a remote or central server.

### 2. Continuous and Immediate Saving
- Scraped data must be inserted into the local SQLite database continuously as the tool runs (e.g., row-by-row or in small, frequent batches).
- **Do not** wait until the end of the scraping operation to bulk-save the data. This guarantees that if a disruption occurs, the maximum amount of data is preserved.

### 3. Required Timestamping
- Every single record saved to the database must include a datetime stamp (e.g., `scraped_at` or `created_at`).
- This allows the user to access the data anytime in the future and precisely track when the information was gathered.

### 4. Crash Recovery and Resumption
- Because the data is saved locally in real-time, the scraper tool should be designed to leverage this database to prevent data loss. 
- If a page is refreshed by mistake, the tool should be able to query the local database to recognize what has already been scraped and resume operations without losing progress or creating duplicates.



---


