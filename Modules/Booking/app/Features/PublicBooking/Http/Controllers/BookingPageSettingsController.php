<?php

namespace Modules\Booking\app\Features\PublicBooking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\PublicBooking\Repositories\BookingPageRepository;
use Modules\Booking\app\Features\PublicBooking\Http\Requests\UpdateBookingPageRequest;
use Modules\Booking\app\Features\PublicBooking\Events\BookingPageSettingsUpdated;
use Modules\Booking\app\Features\PublicBooking\Events\BookingPagePublished;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BookingPageSettingsController extends Controller implements HasMiddleware
{
    protected $repository;

    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!feature('booking-online-page')) {
                    return response()->json([
                        'message' => 'Feature locked. Upgrade your subscription to enable the Online Booking Page.'
                    ], 403);
                }
                return $next($request);
            }),
        ];
    }

    public function __construct(BookingPageRepository $repository)
    {
        $this->repository = $repository;
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
