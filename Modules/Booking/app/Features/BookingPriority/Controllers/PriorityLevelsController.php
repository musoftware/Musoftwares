<?php

namespace Modules\Booking\app\Features\BookingPriority\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\BookingPriority\Models\BookingPriorityLevel;

class PriorityLevelsController extends Controller
{
    public function index(Request $request)
    {
        $levels = BookingPriorityLevel::orderBy('weight', 'desc')->get();
        return response()->json(['data' => $levels]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:booking_priority_levels,code',
            'name' => 'required|string',
            'weight' => 'required|integer',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $level = BookingPriorityLevel::create(array_merge($validated, ['tenant_id' => 1]));
        
        return response()->json(['data' => $level], 201);
    }
}
