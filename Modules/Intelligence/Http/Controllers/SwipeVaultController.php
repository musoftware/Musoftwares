<?php

namespace Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\Intelligence\Models\IntelligenceSwipeCollection;
use Modules\Intelligence\Models\IntelligenceSwipeItem;

class SwipeVaultController extends Controller
{
    public function index(Request $request)
    {
        $collections = IntelligenceSwipeCollection::withCount('items')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('Intelligence/SwipeVault/Index', [
            'collections' => $collections,
        ]);
    }

    public function storeCollection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['nullable', 'string', 'max:100'],
        ]);

        IntelligenceSwipeCollection::create(array_merge($data, ['user_id' => auth()->id()]));

        return back()->with('success', 'Collection created.');
    }

    public function storeItem(Request $request, IntelligenceSwipeCollection $collection): RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'url'         => ['nullable', 'url'],
            'image_url'   => ['nullable', 'url'],
            'notes'       => ['nullable', 'string'],
            'source'      => ['nullable', 'string', 'max:100'],
        ]);

        $collection->items()->create($data);

        return back()->with('success', 'Item saved to vault.');
    }

    public function destroyItem(IntelligenceSwipeCollection $collection, IntelligenceSwipeItem $item): RedirectResponse
    {
        $item->delete();
        return back()->with('success', 'Item removed.');
    }

    public function destroyCollection(IntelligenceSwipeCollection $collection): RedirectResponse
    {
        $collection->delete();
        return back()->with('success', 'Collection deleted.');
    }
}

