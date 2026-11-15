<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BadgeCardImage;
use App\Services\BadgeCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * The public badge card page. The route is signed, so only people who got the link can open it.
 */
class BadgeCardController extends Controller
{
    public function show(string $token, BadgeCardService $cards): View
    {
        $user = $cards->userFor($token) ?? abort(404);

        return view('badge-card.show', [
            'card' => $cards->detailsFor($user),
            'shareUrl' => $cards->linkFor($user),
            'imageUrl' => $cards->imageLinkFor($user),
        ]);
    }

    public function image(string $token, BadgeCardService $cards, BadgeCardImage $images): Response
    {
        $user = $cards->userFor($token) ?? abort(404);

        return response($images->png($cards->detailsFor($user), app()->getLocale()), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** A card shared before the codes: the signed link with the user id still opens the card, at its new address. */
    public function oldLink(User $user, BadgeCardService $cards): RedirectResponse
    {
        return redirect()->to($cards->linkFor($user), 301);
    }
}
