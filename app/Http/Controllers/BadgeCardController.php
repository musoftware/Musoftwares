<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BadgeCardImage;
use App\Services\BadgeCardService;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * The public badge card page. The route is signed, so only people who got the link can open it.
 */
class BadgeCardController extends Controller
{
    public function show(User $user, BadgeCardService $cards): View
    {
        return view('badge-card.show', [
            'card' => $cards->detailsFor($user),
            'shareUrl' => url()->full(),
            'imageUrl' => $cards->imageLinkFor($user),
        ]);
    }

    public function image(User $user, BadgeCardService $cards, BadgeCardImage $images): Response
    {
        return response($images->png($cards->detailsFor($user), app()->getLocale()), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
