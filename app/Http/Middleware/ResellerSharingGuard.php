<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Tools\Models\ToolResellerUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reseller Anti-Sharing Guard
 *
 * Detects when a reseller cheats by sharing ONE sub-user account across
 * multiple real people simultaneously.
 *
 * Detection logic:
 * ─────────────────────────────────────────────────────────────────────
 *  On every tool request:
 *   1. Record a heartbeat (IP + timestamp) for this (user, IP) pair.
 *   2. Count DISTINCT IPs that are active within the last 5 minutes.
 *   3. If > 1 IPs → sharing detected → flag & block the account.
 *
 * Why this is safe for dynamic IPs:
 * ─────────────────────────────────────────────────────────────────────
 *  Dynamic IPs change between sessions, not during an active session.
 *  When a user reconnects with a new IP, their old heartbeat is already
 *  > 5 minutes old and thus NOT counted as "concurrent".
 *  Only real simultaneous usage from two machines creates 2 live heartbeats.
 */
class ResellerSharingGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        // Only applies to reseller sub-users
        $resellerUser = ToolResellerUser::where('user_id', Auth::id())->first();

        if (!$resellerUser) {
            return $next($request);
        }

        $ip        = $this->resolveClientIp($request);
        $userAgent = $request->userAgent() ?? '';

        $allowed = $resellerUser->recordHeartbeatAndCheck($ip, $userAgent);

        if (!$allowed) {
            return $this->blockResponse($request, $resellerUser);
        }

        return $next($request);
    }

    /**
     * Resolve the real public IP, handling reverse proxies and CDNs (Cloudflare etc.)
     */
    private function resolveClientIp(Request $request): string
    {
        // Cloudflare real IP
        $cf = $request->server('HTTP_CF_CONNECTING_IP');
        if ($cf && filter_var($cf, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $cf;
        }

        // Generic real-IP header
        $realIp = $request->server('HTTP_X_REAL_IP');
        if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $realIp;
        }

        // X-Forwarded-For: take the FIRST public IP in the chain
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            foreach (array_map('trim', explode(',', $forwarded)) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Return a blocked response.
     * Inertia renders a dedicated page; JSON returns 403.
     */
    private function blockResponse(Request $request, ToolResellerUser $resellerUser): Response
    {
        $message = 'Your account has been suspended because it was detected as being used simultaneously from multiple locations. Please contact your service provider to resolve this.';

        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return response()->json([
                'error'   => 'account_sharing_detected',
                'message' => $message,
            ], 403);
        }

        return Inertia::render('Tools/AccountSuspended', [
            'reason'  => 'sharing',
            'message' => $message,
        ])->toResponse($request)->setStatusCode(403);
    }
}
