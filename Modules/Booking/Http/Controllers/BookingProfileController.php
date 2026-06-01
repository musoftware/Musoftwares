<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\BookingPageConfig;
use Modules\Booking\Models\BookingEventType;
use Modules\Booking\Models\BookingBranch;
use Modules\Booking\Models\BookingProvider;
use Inertia\Inertia;

class BookingProfileController extends Controller
{
    /**
     * Resolves and displays the public profile.
     */
    public function show(Request $request, $slug = null)
    {
        $tenantId = null;
        $config = null;

        // If the request was intercepted by the Custom Domain middleware
        if ($request->attributes->has('custom_domain_tenant_id')) {
            $tenantId = $request->attributes->get('custom_domain_tenant_id');
            $config = BookingPageConfig::where('tenant_id', $tenantId)->first();
        } else {
            // Standard resolution via slug (e.g. musoftwares.com/book/{slug})
            if (!$slug) {
                abort(404);
            }
            $config = BookingPageConfig::where('slug', $slug)->firstOrFail();
            $tenantId = $config->tenant_id;
        }

        if (!$config) {
            abort(404, __('general.booking_profile_not_found'));
        }

        // Fetch public data for the tenant
        // We join against User to ensure the host is active
        $eventTypes = BookingEventType::whereHas('user', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('is_active', true)
            ->with(['providers' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        $branches = BookingBranch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $providers = BookingProvider::whereHas('host', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('is_active', true)
            ->get();

        return Inertia::render('Booking/Public/Profile', [
            'config' => $config,
            'eventTypes' => $eventTypes,
            'branches' => $branches,
            'providers' => $providers,
        ]);
    }
}
