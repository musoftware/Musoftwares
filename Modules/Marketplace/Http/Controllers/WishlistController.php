<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Services\WishlistService;

class WishlistController extends Controller
{
    public function __construct(protected WishlistService $wishlistService) {}

    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()?->id)
            ->where('favoritable_type', Service::class)
            ->with('favoritable.seller')
            ->latest()
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json(['favorites' => $favorites]);
        }

        return view('marketplace::favorites.index', compact('favorites'));
    }

    public function toggle(Request $request, Service $service): RedirectResponse|JsonResponse
    {
        $isFavorited = $this->wishlistService->toggleFavorite($request->user(), $service);

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return back()->with('success', $isFavorited ? __('general.added_to_favorites') : __('general.removed_from_favorites'));
        }

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Added to wishlist' : 'Removed from wishlist',
        ]);
    }
}

