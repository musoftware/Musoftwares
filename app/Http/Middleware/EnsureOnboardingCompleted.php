<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->onboarding_completed) {
            if (!$request->routeIs('onboarding.*') && !$request->routeIs('logout') && !$request->routeIs('verification.*')) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'error' => 'Onboarding required.',
                        'redirect' => route('onboarding.wizard')
                    ], 403);
                }
                return redirect()->route('onboarding.wizard');
            }
        }

        return $next($request);
    }
}
