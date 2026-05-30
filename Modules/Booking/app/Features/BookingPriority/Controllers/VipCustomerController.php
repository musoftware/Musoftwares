<?php

namespace Modules\Booking\app\Features\BookingPriority\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\BookingPriority\Services\VipCustomerManager;
use Modules\Booking\app\Features\BookingPriority\Models\BookingPriorityAssignment;

class VipCustomerController extends Controller
{
    protected VipCustomerManager $vipManager;

    public function __construct(VipCustomerManager $vipManager)
    {
        $this->vipManager = $vipManager;
    }

    public function index(Request $request)
    {
        $assignments = BookingPriorityAssignment::where('model_type', 'App\Models\Customer')
            ->with('level')
            ->get();
            
        return response()->json(['data' => $assignments]);
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'priority_level_id' => 'required|exists:booking_priority_levels,id',
            'reason' => 'nullable|string',
        ]);

        $this->vipManager->assignVipStatus(
            1, // tenant_id
            $validated['customer_id'],
            $validated['priority_level_id'],
            $validated['reason'] ?? ''
        );

        return response()->json(['message' => 'VIP status assigned successfully']);
    }
}
