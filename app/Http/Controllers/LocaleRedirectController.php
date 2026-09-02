<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleRedirectController extends Controller
{
    /**
     * Redirect a localized request to its canonical non-prefixed URL while saving locale to session.
     */
    public function redirect(Request $request, string $locale, ?string $path = null): RedirectResponse
    {
        if (in_array($locale, ['ar', 'en'])) {
            Session::put('locale', $locale);
            App::setLocale($locale);
        }

        $targetPath = $path ? '/' . ltrim($path, '/') : '/';
        $queryString = $request->getQueryString();
        $targetUrl = $queryString ? $targetPath . '?' . $queryString : $targetPath;

        return redirect($targetUrl, 301);
    }
}
