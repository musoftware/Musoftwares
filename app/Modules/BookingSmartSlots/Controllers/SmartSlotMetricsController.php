<?php

namespace App\Modules\BookingSmartSlots\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\BookingSmartSlots\Models\BookingSmartSlotSnapshot;

class SmartSlotMetricsController extends Controller
{
    public function index(Request $request, $branchId)
    {
        $metrics = BookingSmartSlotSnapshot::where('branch_id', $branchId)
            ->orderBy('date', 'desc')
            ->take(30)
            ->get();

        return response()->json(['data' => $metrics]);
    }
}
