<?php

namespace Modules\Marketplace\Services;

use App\Models\Favorite;
use App\Models\User;
use Modules\Marketplace\Models\Service;

class WishlistService
{
    /**
     * Toggle service favorite / wishlist bookmark.
     */
    public function toggleFavorite(User $user, Service $service): bool
    {
        $existing = Favorite::where('user_id', $user->id)
            ->where('favoritable_type', Service::class)
            ->where('favoritable_id', $service->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return false; // Removed from wishlist
        }

        Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => Service::class,
            'favoritable_id' => $service->id,
            'created_at' => now('Africa/Cairo'),
        ]);

        return true; // Added to wishlist
    }
}
