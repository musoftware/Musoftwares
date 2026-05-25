<?php

namespace Modules\Booking\app\Features\OnlinePage\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\OnlinePage\Services\PublicBookingPageService;
use Modules\Booking\app\Features\OnlinePage\Services\BookingSlotGenerator;
use Modules\Booking\app\Features\OnlinePage\Services\PublicBookingResolver;
use Modules\Booking\app\Features\OnlinePage\Events\PublicBookingViewed;
use Modules\Booking\app\Features\OnlinePage\Events\PublicBookingCreated;

class PublicBookingFlowController extends Controller
{
    protected $pageService;
    protected $slotGenerator;
    protected $resolver;

    public function __construct(
        PublicBookingPageService $pageService,
        BookingSlotGenerator $slotGenerator,
        PublicBookingResolver $resolver
    ) {
        $this->pageService = $pageService;
        $this->slotGenerator = $slotGenerator;
        $this->resolver = $resolver;
    }

    public function init(Request $request, string $slug)
    {
        $page = $this->pageService->getActivePageBySlug($slug);
        
        event(new PublicBookingViewed($page->tenant_id, $page->id, $request->ip()));

        return response()->json([
            'page' => $page,
            // In reality, also return available services/resources for this tenant
        ]);
    }

    public function slots(Request $request, string $slug)
    {
        $request->validate([
            'resource_id' => 'required|integer',
            'date' => 'required|date',
            'duration' => 'integer'
        ]);

        $page = $this->pageService->getActivePageBySlug($slug);
        
        $slots = $this->slotGenerator->generateSlots(
            $page->tenant_id, 
            $request->resource_id, 
            $request->date, 
            $request->input('duration', 30)
        );

        return response()->json(['data' => $slots]);
    }

    public function book(Request $request, string $slug)
    {
        $page = $this->pageService->getActivePageBySlug($slug);

        $validated = $request->validate([
            'resource_id' => 'required|integer',
            'service_id' => 'required|integer',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
        ]);

        $validated['tenant_id'] = $page->tenant_id;

        try {
            $booking = $this->resolver->createReservation($validated);
            event(new PublicBookingCreated($booking));

            return response()->json(['message' => 'Booking created successfully!', 'booking' => $booking], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422); // e.g. Slot no longer available
        }
    }
}
