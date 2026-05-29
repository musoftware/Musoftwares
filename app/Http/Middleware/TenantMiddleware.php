<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->guard('web')->check() && !auth()->guard('erp_team')->check()) {
            abort(403, 'Unauthorized access.');
        }

        // Stub: Checks if the user has an active ERP subscription.
        // Needs proper logic once subscription relations are implemented.
        $hasErpSubscription = true;

        if (!$hasErpSubscription) {
            abort(403, 'Requires an active ERP subscription.');
        }

        return $next($request);
    }
}
