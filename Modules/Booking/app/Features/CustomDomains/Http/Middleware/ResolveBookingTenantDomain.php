<?php

namespace Modules\Booking\app\Features\CustomDomains\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Booking\app\Features\CustomDomains\Services\TenantDomainResolver;

class ResolveBookingTenantDomain
{
    protected $resolver;

    public function __construct(TenantDomainResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Check if it's not the main app domain (simplified logic, adjust as needed)
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);
        
        if ($host !== $mainDomain && !str_ends_with($host, '.' . $mainDomain)) {
            $tenantId = $this->resolver->resolveTenantByDomain($host);

            if ($tenantId) {
                // Here we would bind the tenant to the container or set it as current
                // This assumes there's a multi-tenant package or custom logic
                // e.g., app()->singleton('currentTenant', fn() => Tenant::find($tenantId));
                $request->attributes->set('tenant_id', $tenantId);
            } else {
                // Domain not found or not verified
                abort(404, 'Tenant not found for this domain.');
            }
        }

        return $next($request);
    }
}
