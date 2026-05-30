<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\BookingSmartSlots\Services\SmartSlotEngine;

class SmartSlotAvailabilityController extends Controller
{
    protected SmartSlotEngine $engine;

    public function __construct(SmartSlotEngine $engine)
    {
        $this->engine = $engine;
    }

    public function index(Request $request, $branchId)
    {
        $date = $request->query('date', now()->toDateString());
        
        // Return dynamic generated slots instead of standard slots
        $slots = $this->engine->generateAvailability(1, $branchId, $date);

        return response()->json(['data' => $slots]);
    }
}
