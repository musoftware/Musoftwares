<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Campaign;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $stats = [
            'total_leads' => Lead::where('user_id', $userId)->count(),
            'new_leads' => Lead::where('user_id', $userId)->where('status', 'new')->count(),
            'active_campaigns' => Campaign::where('user_id', $userId)->where('status', 'active')->count(),
        ];

        $recentLeads = Lead::where('user_id', $userId)
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('CRM/Dashboard', [
            'stats' => $stats,
            'recentLeads' => $recentLeads
        ]);
    }
}
