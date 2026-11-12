<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * A page that sends people to sign in (the QR menu maker, the AI builder chat) passes ?redirect=/its/path so they
 * come back to it afterwards. Only a path on this site is kept, so the parameter can never send anyone elsewhere.
 */
final class ReturnAfterSignIn
{
    public static function remember(Request $request): void
    {
        $back = (string) $request->query('redirect', '');
        if (str_starts_with($back, '/') && ! str_starts_with($back, '//') && ! str_contains($back, '\\')) {
            $request->session()->put('url.intended', url($back));
        }
    }
}
