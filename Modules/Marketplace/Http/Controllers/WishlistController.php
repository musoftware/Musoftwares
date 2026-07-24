<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Services\WishlistService;
use App\Models\Favorite;

class WishlistController extends Controller
{
    public function __construct(protected WishlistService $wishlistService) {}

    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', auth()->id())
            ->where('favoritable_type', Service::class)
            ->with('favoritable.seller')
            ->latest()
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json(['favorites' => $favorites]);
        }

        return \Inertia\Inertia::render('Marketplace/Favorites/Index', [
            'favorites' => $favorites
        ]);
    }

    public function toggle(Request $request, Service $service)
    {
        $isFavorited = $this->wishlistService->toggleFavorite(auth()->user(), $service);

        if ($request->header('X-Inertia') || $request->wantsJson() === false) {
            return back()->with('success', $isFavorited ? __('general.added_to_favorites') : __('general.removed_from_favorites'));
        }

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Added to wishlist' : 'Removed from wishlist',
        ]);
    }
}
