<?php

namespace Modules\Booking\app\Features\PublicBooking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\PublicBooking\Repositories\BookingPageRepository;
use Modules\Booking\app\Features\PublicBooking\Http\Requests\UpdateBookingPageRequest;
use Modules\Booking\app\Features\PublicBooking\Events\BookingPageSettingsUpdated;
use Modules\Booking\app\Features\PublicBooking\Events\BookingPagePublished;

class BookingPageSettingsController extends Controller
{
    protected $repository;

    public function __construct(BookingPageRepository $repository)
    {
        $this->repository = $repository;
        
        // Admin endpoints are guarded by the SaaS feature flag
        $this->middleware(function ($request, $next) {
            if (!feature('booking-online-page')) {
                return response()->json([
                    'message' => 'Feature locked. Upgrade your subscription to enable the Online Booking Page.'
                ], 403);
            }
            return $next($request);
        });
    }

    public function show()
    {
        $settings = $this->repository->getSettings();
        return response()->json($settings);
    }

    public function update(UpdateBookingPageRequest $request)
    {
        $settings = $this->repository->getSettings();
        
        $wasInactive = !$settings->is_active;

        $settings->update($request->validated());
        
        event(new BookingPageSettingsUpdated($settings));
        
        if ($wasInactive && $settings->is_active) {
            event(new BookingPagePublished($settings));
        }

        return response()->json($settings);
    }
}
