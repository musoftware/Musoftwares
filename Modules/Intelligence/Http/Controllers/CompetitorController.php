<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceCompetitor;

class CompetitorController extends Controller
{
    public function index()
    {
        $competitors = IntelligenceCompetitor::withCount(['trackedAssets', 'ads', 'activities'])->get();

        return Inertia::render('Intelligence/Competitors/Index', [
            'competitors' => $competitors
        ]);
    }

    public function show(IntelligenceCompetitor $competitor)
    {
        $competitor->load(['trackedAssets', 'ads', 'activities']);
        
        return Inertia::render('Intelligence/Competitors/Show', [
            'competitor' => $competitor
        ]);
    }
}
