<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized access.');
        }

        // Stub: Checks if the user has access to a specific module
        $hasModuleAccess = true;

        if (!$hasModuleAccess) {
             abort(403, "Requires access to the {$module} module.");
        }

        return $next($request);
    }
}
