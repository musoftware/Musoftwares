<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The pages an admin keeps at the top of the side menu, in their order. Saved on the account, so every device shows them. */
class AdminSidebarFavoritesController extends Controller
{
    private const MAX_FAVORITES = 20;

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'urls' => ['present', 'array', 'max:'.self::MAX_FAVORITES],
            'urls.*' => ['string', 'distinct', 'regex:#^/[A-Za-z0-9/_-]{1,100}$#'],
        ]);
        $user = $request->user();
        $user->workspace_settings = ['admin_favorites' => array_values($data['urls'])] + ($user->workspace_settings ?? []);
        $user->save();

        return back();
    }
}
