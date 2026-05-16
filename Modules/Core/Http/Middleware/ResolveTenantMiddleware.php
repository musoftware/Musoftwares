<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Tenancy\TenantResolver;

class ResolveTenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            // Simplified tenant resolution strategy based on user relationship
            // Adjust according to actual application logic where user -> tenant.
            if ($user->hasRole('super_admin')) {
                TenantResolver::enableAdminBypass();
            } else {
                // Try resolving from header, subdomain, or user
                // Example: from user's current working tenant
                // $tenant = $user->currentTenant;
                // TenantResolver::resolve($tenant);

                // For this refactor scope:
                if (method_exists($user, 'tenant') && $user->tenant) {
                    TenantResolver::resolve($user->tenant);
                }
            }
        }

        return $next($request);
    }
}
