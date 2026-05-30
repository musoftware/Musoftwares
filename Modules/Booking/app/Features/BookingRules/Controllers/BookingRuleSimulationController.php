<?php

namespace Modules\Booking\app\Features\BookingRules\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\BookingRules\Services\SimulationService;

class BookingRuleSimulationController extends Controller
{
    protected SimulationService $simulationService;

    public function __construct(SimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    public function simulate(Request $request, $ruleId)
    {
        $validated = $request->validate([
            'payload' => 'required|array',
        ]);

        $tenantId = 1; // Mock
        $result = $this->simulationService->simulate($tenantId, $ruleId, $validated['payload']);

        return response()->json(['data' => $result]);
    }
}
