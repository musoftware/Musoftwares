<?php

namespace App\Http\Middleware;

use App\Services\IpGeolocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    private const LOCALES = ['en', 'ar'];

    /** Visitors in these countries always get this language. Only admins can still choose. */
    private const COUNTRY_LOCALES = ['EG' => 'ar'];

    public function __construct(private readonly IpGeolocationService $geolocation) {}

    /**
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $countryLocale = $this->countryLocale($request);

        $request->attributes->set('locale_locked', $countryLocale !== null);
        App::setLocale($countryLocale ?? $this->chosenLocale($request));

        return $next($request);
    }

    private function countryLocale(Request $request): ?string
    {
        if ($request->user()?->isAdmin()) {
            return null;
        }

        return self::COUNTRY_LOCALES[$this->geolocation->getCountryFromIp($request->ip()) ?? ''] ?? null;
    }

    /**
     * ?lang= picks the language for this session. A signed-in user's pick is also kept on the account:
     * users.locale is the explicit choice (empty until they pick one, so nobody is switched by a column
     * default) and users.lang follows it because their e-mails are sent in that language.
     */
    private function chosenLocale(Request $request): string
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

        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }
}
