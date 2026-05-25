<?php

namespace Modules\Booking\app\Features\CustomDomains\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\CustomDomains\Repositories\CustomDomainRepository;
use Modules\Booking\app\Features\PublicBooking\Services\BookingPageService;

class IdentifyTenantByCustomDomain
{
    protected $repository;
    protected $bookingPageService;

    public function __construct(CustomDomainRepository $repository, BookingPageService $bookingPageService)
    {
        $this->repository = $repository;
        $this->bookingPageService = $bookingPageService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Skip logic if we are hitting the main system domain
        $appDomain = parse_url(config('app.url'), PHP_URL_HOST);
        if ($host === $appDomain || $host === 'localhost' || str_ends_with($host, '.' . $appDomain)) {
            return $next($request);
        }

        // Try to identify custom domain
        $customDomain = $this->repository->findActiveDomain($host);

        if (!$customDomain) {
            abort(404, 'Domain not configured or verified.');
        }

        // Verify the SaaS feature is still active for this tenant
        if (!feature('booking-custom-domain', $customDomain->tenant_id)) {
            abort(403, 'Custom domain feature is locked for this account.');
        }
        
        // At this point, we've identified the tenant. We can inject the tenant context 
        // into the container or request so controllers know who they are dealing with.
        $request->attributes->set('tenant_id', $customDomain->tenant_id);

        return $next($request);
    }
}
