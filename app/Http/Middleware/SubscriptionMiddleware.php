<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    protected $subscriptionService;

    public function __construct(\App\Services\SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(401, 'Unauthorized access.');
            }
            return redirect()->route('login');
        }

        $user = $request->user();

        // 1. Verify active subscription or admin status
        $hasModuleAccess = $this->subscriptionService->hasActiveSubscription($user, $module);

        if (!$hasModuleAccess) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Subscription required.',
                    'redirect' => route('subscriptions.plans', ['module' => $module])
                ], 403);
            }
            return redirect()->route('subscriptions.plans', ['module' => $module]);
        }

        return $next($request);
    }
}
