<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    private const LOCALES = ['en', 'ar'];

    /**
     * ?lang= picks the language for this session. A signed-in user's pick is also kept on the account:
     * users.locale is the explicit choice (empty until they pick one, so nobody is switched by a column
     * default) and users.lang follows it because their e-mails are sent in that language.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $lang = $request->query('lang');

        if (is_string($lang) && in_array($lang, self::LOCALES, true)) {
            Session::put('locale', $lang);

            if ($user && ($user->locale !== $lang || $user->lang !== $lang)) {
                $user->forceFill(['locale' => $lang, 'lang' => $lang])->saveQuietly();
            }
        } elseif (! Session::has('locale') && $user && in_array($user->locale, self::LOCALES, true)) {
            Session::put('locale', $user->locale);
        }

        $locale = Session::get('locale', config('app.locale', 'en'));
        if (! in_array($locale, self::LOCALES, true)) {
            $locale = 'en';
        }
        App::setLocale($locale);

        return $next($request);
    }
}
