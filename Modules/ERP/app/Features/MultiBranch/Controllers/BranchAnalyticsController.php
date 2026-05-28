<?php

namespace Modules\ERP\app\Features\MultiBranch\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\app\Features\MultiBranch\Services\BranchAnalyticsAggregator;

class BranchAnalyticsController extends Controller
{
    protected BranchAnalyticsAggregator $analyticsAggregator;

    public function __construct(BranchAnalyticsAggregator $analyticsAggregator)
    {
        $this->analyticsAggregator = $analyticsAggregator;
    }

    public function show(Request $request, $branchId)
    {
        $metrics = $this->analyticsAggregator->getBranchMetrics($branchId);
        return response()->json(['data' => $metrics]);
    }

    public function global(Request $request)
    {
        $metrics = $this->analyticsAggregator->getGlobalMetrics($request->user()->tenant_id);
        return response()->json(['data' => $metrics]);
    }
}
