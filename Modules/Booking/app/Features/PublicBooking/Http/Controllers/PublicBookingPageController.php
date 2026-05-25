<?php

namespace Modules\Booking\app\Features\PublicBooking\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Booking\app\Features\PublicBooking\Services\BookingPageService;

class PublicBookingPageController extends Controller
{
    protected $pageService;

    public function __construct(BookingPageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     * Fetch the configuration and available services for a public booking page by its slug.
     */
    public function show(string $slug)
    {
        // getPublicPageData handles abort(404) if not found and abort(403) if not active
        $data = $this->pageService->getPublicPageData($slug);
        
        // We also need to verify that the tenant's SaaS feature flag is active
        // This requires bypassing the auth() check and checking via the tenant ID directly.
        // Assuming `feature()` accepts an optional tenant_id as a second parameter in your architecture:
        if (!feature('booking-online-page', $data['tenant_id'])) {
            abort(403, 'This booking page is currently unavailable.');
        }

        return response()->json($data);
    }
}
