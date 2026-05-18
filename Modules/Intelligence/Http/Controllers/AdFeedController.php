<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceAd;

class AdFeedController extends Controller
{
    public function index()
    {
        $ads = IntelligenceAd::with('competitor')
            ->orderBy('first_seen_at', 'desc')
            ->paginate(20);

        return Inertia::render('Intelligence/Ads/Feed', [
            'ads' => $ads
        ]);
    }
}
