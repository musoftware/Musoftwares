<?php

namespace Modules\Booking\app\Features\WhiteLabel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Booking\app\Features\WhiteLabel\Services\WhiteLabelDomainResolver;
use Modules\Booking\app\Features\WhiteLabel\Services\WhiteLabelBrandingResolver;
use Inertia\Inertia;

class ResolveWhiteLabelBranding
{
    protected WhiteLabelDomainResolver $domainResolver;
    protected WhiteLabelBrandingResolver $brandingResolver;

    public function __construct(WhiteLabelDomainResolver $domainResolver, WhiteLabelBrandingResolver $brandingResolver)
    {
        $this->domainResolver = $domainResolver;
        $this->brandingResolver = $brandingResolver;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $tenantId = $this->domainResolver->resolveTenantFromDomain($host);

        if (!$tenantId && $request->hasHeader('X-Tenant')) {
            $tenantId = $request->header('X-Tenant');
        }

        if ($tenantId) {
            $branding = $this->brandingResolver->resolve((int) $tenantId);
            
            // Share with Inertia for SPA rendering
            Inertia::share('whiteLabel', $branding);

            // Also attach to request for Blade views or API responses
            $request->attributes->set('whiteLabel', $branding);
        }

        return $next($request);
    }
}
