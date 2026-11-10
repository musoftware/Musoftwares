<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Laravel's inline limits ('throttle:10,1') count by visitor only, so every inline limit on the site shared one
 * counter per visitor: a page that polls one route used up the limit of another (the AI chat's polling blocked
 * sending a message), and repeated refusals then got the address blocked. Here each route counts on its own.
 * Named limits ('throttle:web') are not affected; they keep their own keys.
 */
class ThrottleRequestsPerRoute extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        $routeKey = $route ? ($route->getName() ?: implode('|', $route->methods()).' '.$route->uri()) : '';

        return sha1(parent::resolveRequestSignature($request).'|'.$routeKey);
    }
}
