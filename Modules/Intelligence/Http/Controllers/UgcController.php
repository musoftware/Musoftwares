<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceUgcCreator;

class UgcController extends Controller
{
    public function index(Request $request)
    {
        $query = IntelligenceUgcCreator::orderBy('name', 'asc');

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        $creators = $query->paginate(30)->withQueryString();

        return Inertia::render('Intelligence/Ugc/Index', [
            'creators' => $creators,
            'filters'  => $request->only(['platform']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'platform'     => ['required', 'string', 'max:50'],
            'handle'       => ['nullable', 'string', 'max:100'],
            'profile_url'  => ['nullable', 'url'],
            'niche'        => ['nullable', 'string', 'max:100'],
            'follower_count' => ['nullable', 'integer'],
            'notes'        => ['nullable', 'string'],
        ]);

        IntelligenceUgcCreator::create($data);
        return back()->with('success', 'UGC creator added.');
    }

    public function update(Request $request, IntelligenceUgcCreator $creator): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'platform'       => ['required', 'string', 'max:50'],
            'handle'         => ['nullable', 'string', 'max:100'],
            'profile_url'    => ['nullable', 'url'],
            'niche'          => ['nullable', 'string', 'max:100'],
            'follower_count' => ['nullable', 'integer'],
            'notes'          => ['nullable', 'string'],
        ]);

        $creator->update($data);
        return back()->with('success', 'Creator updated.');
    }

    public function destroy(IntelligenceUgcCreator $creator): RedirectResponse
    {
        $creator->delete();
        return back()->with('success', 'Creator removed.');
    }
}

