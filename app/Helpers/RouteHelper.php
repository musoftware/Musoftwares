<?php


namespace App\Helpers;

use App\Notifications\NotificationInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use NotificationChannels\Fcm\FcmMessage;

class RouteHelper
{

    public static function hasRouteMiddleware($name){
        $route = request()->route();

        if (empty($route)) {
            return false;
        }
        $middleware = $route->middleware();
        foreach ($middleware as $item) {
            if ($item == $name) return true;
        }
        if (empty($middleware)) return false;
        return true;
    }


}
