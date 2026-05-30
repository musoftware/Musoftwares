<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\BookingSmartSlots\Jobs\OptimizeScheduleGapsJob;

class SmartSlotOptimizationController extends Controller
{
    public function optimize(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        // Dispatch manual optimization
        OptimizeScheduleGapsJob::dispatch(
            1, // tenant_id
            $validated['branch_id'],
            $validated['date']
        );

        return response()->json(['message' => 'Optimization triggered successfully']);
    }
}
