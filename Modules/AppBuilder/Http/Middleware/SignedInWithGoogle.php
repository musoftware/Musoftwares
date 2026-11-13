<?php

namespace Modules\AppBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AppBuilder\Models\BuilderProject;
use Symfony\Component\HttpFoundation\Response;

/**
 * The AI builder is used only from an account that signed in with Google, so nobody can make throwaway accounts
 * with made-up emails to get free chats or trials. A visitor (or an account that never came through Google) first
 * sees a short page that asks, with one button to Google, and comes back to the same page. Admins are not asked.
 */
class SignedInWithGoogle
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user !== null && ($user->provider_id !== null || $user->isAdmin())) {
            return $next($request);
        }

        $google = route('social.google.redirect', ['redirect' => $this->pageToComeBackTo($request)]);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('builder.errors.sign_in_with_google'), 'redirect' => $google], 401);
        }

        // a page opened in the browser asks first; a form sent without a session goes back to the start page
        return $request->isMethod('GET')
            ? response()->view('appbuilder::google-sign-in', ['googleUrl' => $google])
            : redirect()->route('builder.index');
    }

    /** The page itself when one was opened; for the chat's own calls, the chat they came from (or the start page). */
    private function pageToComeBackTo(Request $request): string
    {
        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            return $request->getRequestUri();
        }
        $project = $request->route('project');

        return $project instanceof BuilderProject ? route('builder.show', $project, false) : route('builder.index', absolute: false);
    }
}
