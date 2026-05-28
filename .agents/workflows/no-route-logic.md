---
description: Strictly forbids placing any business logic, database queries, or complex anonymous functions inside route definition files (e.g., web.php, api.php).
---


# No Route Logic

This skill enforces a strict architectural rule: **Route files must only contain routing declarations.**

Route files (such as `routes/api.php`, `routes/web.php`, `routes/console.php`) are configuration files that map URLs to Controller methods. They are not the place for business logic.

## Core Rules

1. **No Anonymous Functions for Logic:** Do not use closure-based routes (e.g., `Route::get('/path', function () { ... })`) if they contain any business logic, data fetching, or complex operations. Closures should generally be avoided entirely in favor of Controllers.
2. **No Database Queries:** Never interact with Models or run DB queries directly in a route file.
3. **No File System Operations:** Checking for files, reading files, or downloading files must be handled by a Controller.
4. **Use Controllers:** All routes must point to a specific Controller method using the tuple syntax: `[ControllerName::class, 'methodName']`.
5. **No Inline Authorization:** Do not write custom authorization logic or token validation inside the route file. Use standard Middleware or Form Requests inside the Controller.
6. **Use Module Routes:** Never place module-specific routes in the main `routes/web.php` or `routes/api.php` files. Every module must define its own routes in `Modules/<ModuleName>/routes/web.php` (or `api.php`), and the system will automatically load them via the module's Service Provider.

## Anti-Pattern (What to Avoid)

```php
// BAD: Business logic, DB queries, and file checks inside the route file.
Route::get('/agent/plugins', function (\Illuminate\Http\Request $request) {
    $agentType = $request->query('agent', 'nodejs');
    
    $subscriptions = \Modules\Tools\Models\ToolSubscription::where('user_id', auth()->id())
        ->where('status', 'active')
        ->get();

    // ... mapping and logic
    return response()->json(['plugins' => $subscriptions]);
})->name('agent.plugins');
```

## Correct Pattern (What to Do)

```php
// GOOD: Clean, simple routing pointing to a Controller.
Route::get('/agent/plugins', [AgentPluginController::class, 'index'])->name('agent.plugins');
```

Then, inside `AgentPluginController.php`:

```php
public function index(\Illuminate\Http\Request $request)
{
    $agentType = $request->query('agent', 'nodejs');
    
    // Logic, DB queries, and response handling happen here
    // or are delegated to a dedicated Service class.
    // ...
}
```

## Refactoring Existing Violations

If you encounter a route file containing inline logic:
1. Create a new Controller (if one doesn't exist for the domain) or identify an appropriate existing Controller.
2. Move the logic from the route closure into a new method on the Controller.
3. Update the route file to point to the new Controller method.
4. Ensure all necessary `use` imports are updated in both files.
