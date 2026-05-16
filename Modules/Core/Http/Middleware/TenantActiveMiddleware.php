<?php

namespace Modules\Core\Http\Middleware;

class TenantActiveMiddleware
{
    public function handle($request, \Closure $next)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return $next($request);
        }

        $subscription = $user->subscription()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if (!$subscription) {
            return redirect()->route('erp.subscribe');
        }

        return $next($request);
    }
}
