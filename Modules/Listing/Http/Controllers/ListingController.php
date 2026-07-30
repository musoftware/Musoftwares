<?php

namespace Modules\Listing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Listing\Models\Listing;

class ListingController extends Controller
{
    /**
     * Display the public listings index (search board).
     */
    public function index(Request $request)
    {
        $query = Listing::query()
            ->where('status', 'active')
            ->latest();

        // Search query
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // City filter
        if ($city = $request->get('city')) {
            $query->where('city', $city);
        }

        $listings = $query->paginate(15)->through(fn (Listing $listing) => [
            'id' => $listing->id,
            'title' => $listing->title,
            'description' => $listing->description,
            'price' => (float) $listing->price,
            'currency' => $listing->currency,
            'city' => $listing->city,
            'images' => $listing->images ?? [],
            'created_at' => $listing->created_at?->diffForHumans(),
        ]);

        // Get unique cities for filter dropdown
        $cities = Listing::where('status', 'active')
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->filter()
            ->values()
            ->toArray();

        return Inertia::render('Listing/Index', [
            'listings' => $listings,
            'cities' => $cities,
            'filters' => $request->only(['q', 'city']),
        ]);
    }

    /**
     * Display a single public listing detail.
     */
    public function show($id)
    {
        $listing = Listing::where('status', 'active')->findOrFail($id);

        return Inertia::render('Listing/Show', [
            'listing' => [
                'id' => $listing->id,
                'title' => $listing->title,
                'description' => $listing->description,
                'price' => (float) $listing->price,
                'currency' => $listing->currency,
                'city' => $listing->city,
                'phone' => $listing->phone,
                'email' => $listing->email,
                'images' => $listing->images ?? [],
                'original_url' => $listing->original_url,
                'created_at' => $listing->created_at?->diffForHumans(),
            ]
        ]);
    }

    /**
     * Display the employer's private dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();

        $listings = Listing::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (Listing $listing) => [
                'id' => $listing->id,
                'title' => $listing->title,
                'description' => $listing->description,
                'price' => (float) $listing->price,
                'currency' => $listing->currency,
                'city' => $listing->city,
                'phone' => $listing->phone,
                'email' => $listing->email,
                'images' => $listing->images ?? [],
                'status' => $listing->status,
                'created_at' => $listing->created_at?->diffForHumans(),
            ]);

        return Inertia::render('Listing/Dashboard', [
            'listings' => $listings,
        ]);
    }

    /**
     * Show the edit form for a listing.
     */
    public function edit($id)
    {
        $listing = Listing::where('user_id', auth()->id())->findOrFail($id);

        return Inertia::render('Listing/Edit', [
            'listing' => [
                'id' => $listing->id,
                'title' => $listing->title,
                'description' => $listing->description,
                'price' => (float) $listing->price,
                'city' => $listing->city,
                'status' => $listing->status,
            ]
        ]);
    }

    /**
     * Update an employer's listing.
     */
    public function update(Request $request, $id)
    {
        $listing = Listing::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'city' => 'nullable|string|max:255',
            'status' => 'required|in:active,draft,archived',
        ]);

        $listing->update($validated);

        return redirect()->route('listing.dashboard')->with('success', 'تم تحديث الإعلان بنجاح');
    }

    /**
     * Delete an employer's listing.
     */
    public function destroy($id)
    {
        $listing = Listing::where('user_id', auth()->id())->findOrFail($id);
        $listing->delete();

        return redirect()->back()->with('success', 'تم حذف الإعلان بنجاح');
    }
}
