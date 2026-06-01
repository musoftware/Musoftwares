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
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->guard('web')->check() && !auth()->guard('erp_team')->check()) {
            abort(403, __('general.unauthorized_access'));
        }

        // Stub: Checks if the user has an active ERP subscription.
        // Needs proper logic once subscription relations are implemented.
        $hasErpSubscription = true;

        if (!$hasErpSubscription) {
            abort(403, __('general.requires_an_active_erp_subscription'));
        }

        return $next($request);
    }
}
