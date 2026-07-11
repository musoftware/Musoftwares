<?php

namespace App\Helpers;

class RouteHelper
{
    public static function hasRouteMiddleware($name)
    {
        $route = request()->route();

        if (empty($route)) {
            return false;
        }
        $middleware = $route->middleware();
        foreach ($middleware as $item) {
            if ($item == $name) {
                return true;
            }
        }
        if (empty($middleware)) {
            return false;
        }

        return true;
    }
}
