<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('lang')) {
            $lang = $request->get('lang');
            if (in_array($lang, ['en', 'ar'])) {
                Session::put('locale', $lang);
            }
        }

        $locale = Session::get('locale', config('app.locale', 'en'));
        if (! in_array($locale, ['en', 'ar'])) {
            $locale = 'en';
        }
        App::setLocale($locale);

        return $next($request);
    }
}
