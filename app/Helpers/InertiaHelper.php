<?php

namespace App\Helpers;

use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class InertiaHelper
{
    /**
     * Check if the request is an Inertia request.
     */
    public static function isInertia(): bool
    {
        return request()->hasHeader('X-Inertia');
    }

    /**
     * Return a full page reload location response for Inertia.
     */
    public static function location(string $url): Response
    {
        return Inertia::location($url);
    }
}
