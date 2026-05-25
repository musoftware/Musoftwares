<?php

namespace Modules\Booking\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Booking\Models\TenantDomain;

class ResolveTenantDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $baseDomain = config('app.url'); // e.g., musoftwares.com
        
        // Strip out protocols for accurate matching
        $cleanBaseDomain = preg_replace('#^https?://#', '', $baseDomain);
        
        // Skip middleware if accessing the base domain directly
        if ($host === $cleanBaseDomain || $host === 'localhost' || $host === '127.0.0.1') {
            return $next($request);
        }

        // It's a custom domain, look it up
        $tenantDomain = TenantDomain::where('domain', $host)
            ->where('is_verified', true)
            ->first();

        if (!$tenantDomain) {
            abort(404, "Custom domain not registered or not verified.");
        }

        // Bind the tenant ID to the request attributes so controllers know 
        // they are operating under a white-labeled custom domain.
        $request->attributes->add(['custom_domain_tenant_id' => $tenantDomain->tenant_id]);

        return $next($request);
    }
}
