<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! auth()->check() && ! auth('crm_team')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(401, __('general.unauthorized_access'));
            }

            return redirect()->route('login');
        }

        $user = $request->user();

        // Bypass subscription check for marketplace module
        if ($module === 'marketplace') {
            return $next($request);
        }

        // 1. Verify active subscription or admin status
        $hasModuleAccess = $this->subscriptionService->hasActiveSubscription($user, $module);

        if (! $hasModuleAccess) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Subscription required.',
                    'redirect' => route('subscriptions.plans', ['module' => $module]),
                ], 403);
            }

            if ($module === 'erp') {
                return Inertia::render('ERP/UpgradePreview');
            }

            return redirect()->route('subscriptions.plans', ['module' => $module]);
        }

        return $next($request);
    }
}
