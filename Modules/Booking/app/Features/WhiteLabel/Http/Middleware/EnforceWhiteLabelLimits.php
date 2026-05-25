<?php

namespace Modules\Booking\app\Features\WhiteLabel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceWhiteLabelLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitType): Response
    {
        $tenantId = $request->attributes->get('tenant_id') ?? ($request->user() ? $request->user()->tenant_id : null);

        if (!$tenantId) {
            abort(403, 'Tenant context not found.');
        }

        if (!feature('booking.white_label')) {
            abort(403, 'White label feature is not enabled for your plan.');
        }

        $limitsService = app(\Modules\Booking\app\Features\WhiteLabel\Services\BookingWhiteLabelLimitsService::class);

        $allowed = match ($limitType) {
            'domain' => $limitsService->canAddDomain($tenantId),
            'asset' => $limitsService->canAddAsset($tenantId),
            'template' => $limitsService->canAddTemplate($tenantId),
            default => false,
        };

        if (!$allowed) {
            abort(403, "You have reached the maximum allowed limit for {$limitType}s.");
        }

        return $next($request);
    }
}
