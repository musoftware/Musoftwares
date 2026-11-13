<?php

namespace Modules\AppBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The AI builder is used only from an account that signed in with Google, so nobody can make throwaway accounts
 * with made-up emails to get free chats, free builds or trials. A visitor (or an account that never came through
 * Google) is sent to Google and comes back to the same page. Admins are not asked.
 */
class SignedInWithGoogle
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user !== null && ($user->provider_id !== null || $user->isAdmin())) {
            return $next($request);
        }

        $back = $request->isMethod('GET') ? $request->getRequestUri() : route('builder.index', absolute: false);
        $google = route('social.google.redirect', ['redirect' => $back]);

        return $request->expectsJson()
            ? response()->json(['message' => __('builder.errors.sign_in_with_google'), 'redirect' => $google], 401)
            : redirect()->to($google);
    }
}
