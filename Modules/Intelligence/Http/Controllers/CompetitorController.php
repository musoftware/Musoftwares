<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceCompetitor;

class CompetitorController extends Controller
{
    public function index(Request $request)
    {
        $query = IntelligenceCompetitor::withCount(['trackedAssets', 'ads', 'activities']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('website', 'like', "%{$request->search}%");
            });
        }

        $competitors = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Intelligence/Competitors/Index', [
            'competitors' => $competitors,
            'filters'     => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'website'      => ['nullable', 'url', 'max:255'],
            'description'  => ['nullable', 'string'],
            'industry'     => ['nullable', 'string', 'max:100'],
            'tags'         => ['nullable', 'array'],
        ]);

        IntelligenceCompetitor::create(array_merge($data, ['user_id' => auth()->id()]));

        return back()->with('success', 'Competitor added.');
    }

    public function show(IntelligenceCompetitor $competitor)
    {
        $competitor->load([
            'trackedAssets',
            'ads' => fn($q) => $q->latest('first_seen_at')->limit(30),
            'activities' => fn($q) => $q->latest()->limit(20),
        ]);

        return Inertia::render('Intelligence/Competitors/Show', [
            'competitor' => $competitor,
        ]);
    }

    public function update(Request $request, IntelligenceCompetitor $competitor): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'website'     => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'industry'    => ['nullable', 'string', 'max:100'],
            'tags'        => ['nullable', 'array'],
        ]);

        $competitor->update($data);

        return back()->with('success', 'Competitor updated.');
    }

    public function destroy(IntelligenceCompetitor $competitor): RedirectResponse
    {
        $competitor->delete();
        return back()->with('success', 'Competitor removed.');
    }
}

