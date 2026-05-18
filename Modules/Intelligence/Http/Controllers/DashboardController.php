<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceActivity;

class DashboardController extends Controller
{
    public function index()
    {
        $activities = IntelligenceActivity::with('competitor')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Intelligence/Dashboard', [
            'activities' => $activities
        ]);
    }
}
