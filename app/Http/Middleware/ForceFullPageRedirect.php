<?php

namespace App\Http\Middleware;

use App\Helpers\InertiaHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceFullPageRedirect
{
    /**
     * Handle an incoming request.
     * If the request originates from Inertia SPA navigation when attempting to load a Blade page,
     * force a full-page reload so the browser navigates directly to the Blade view.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (InertiaHelper::isInertia()) {
            return InertiaHelper::location($request->fullUrl());
        }

        return $next($request);
    }
}
