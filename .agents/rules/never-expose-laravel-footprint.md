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
- In controllers and API routes, use custom error responses with localized generic messages instead.
- For database query errors, catch them and return a generic system error.
- **Example**:
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
