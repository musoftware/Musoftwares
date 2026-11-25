<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * A random mark kept in the browser for years, so the site can tell when two accounts use the same browser.
 * The cookie is encrypted by the site like every cookie, so it cannot be set to another browser's mark.
 */
class DeviceMark
{
    public const COOKIE = 'mu_device';

    private const YEARS = 5;

    public function of(Request $request): string
    {
        $mark = (string) $request->cookie(self::COOKIE, '');
        if (preg_match('/^[A-Za-z0-9]{32}$/', $mark) === 1) {
            return $mark;
        }

        $mark = Str::random(32);
        Cookie::queue(Cookie::make(self::COOKIE, $mark, 60 * 24 * 365 * self::YEARS));
        // the same request may ask again; it gets the same mark
        $request->cookies->set(self::COOKIE, $mark);

        return $mark;
    }
}
