<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Sequence;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // For now, these use the current user's scoped data if BelongsToTenant is applied.
        // Assuming models are properly scoped.
        
        $stats = [
            'total_leads' => Lead::count(),
            'new_leads' => Lead::where('status', 'new')->count(),
            'active_campaigns' => Campaign::where('status', 'running')->count(),
            'total_sequences' => Sequence::count(),
        ];

        return Inertia::render('CRM/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
