<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceAd;
use Modules\Intelligence\Models\IntelligenceCompetitor;

class AdFeedController extends Controller
{
    public function index(Request $request)
    {
        $query = IntelligenceAd::with('competitor')->orderBy('first_seen_at', 'desc');

        if ($request->filled('competitor_id')) {
            $query->where('competitor_id', $request->competitor_id);
        }
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        $ads = $query->paginate(24)->withQueryString();
        $competitors = IntelligenceCompetitor::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Intelligence/Ads/Feed', [
            'ads'         => $ads,
            'competitors' => $competitors,
            'filters'     => $request->only(['competitor_id', 'platform']),
            'platforms'   => ['facebook', 'instagram', 'tiktok', 'google', 'youtube', 'twitter'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'competitor_id' => ['required', 'exists:intelligence_competitors,id'],
            'platform'      => ['required', 'string', 'max:50'],
            'ad_type'       => ['required', 'in:image,video,carousel,text'],
            'headline'      => ['nullable', 'string', 'max:255'],
            'body'          => ['nullable', 'string'],
            'cta'           => ['nullable', 'string', 'max:100'],
            'media_url'     => ['nullable', 'url'],
            'landing_url'   => ['nullable', 'url'],
            'first_seen_at' => ['nullable', 'date'],
            'last_seen_at'  => ['nullable', 'date'],
            'tags'          => ['nullable', 'array'],
        ]);

        IntelligenceAd::create($data);
        return back()->with('success', 'Ad captured.');
    }

    public function destroy(IntelligenceAd $ad): RedirectResponse
    {
        $ad->delete();
        return back()->with('success', 'Ad removed.');
    }
}

