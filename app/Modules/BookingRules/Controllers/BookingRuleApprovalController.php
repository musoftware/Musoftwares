<?php

namespace App\Modules\BookingRules\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\BookingRules\Models\BookingAdvancedRuleExecution;

class BookingRuleApprovalController extends Controller
{
    public function index(Request $request)
    {
        $pendingApprovals = BookingAdvancedRuleExecution::where('status', 'pending_approval')
            ->with(['rule', 'logs'])
            ->get();

        return response()->json(['data' => $pendingApprovals]);
    }

    public function approve(Request $request, BookingAdvancedRuleExecution $execution)
    {
        $execution->update(['status' => 'approved']);
        // Trigger approval action/webhook
        return response()->json(['message' => 'Approved successfully']);
    }

    public function reject(Request $request, BookingAdvancedRuleExecution $execution)
    {
        $execution->update(['status' => 'rejected']);
        // Trigger rejection action/webhook
        return response()->json(['message' => 'Rejected successfully']);
    }
}
