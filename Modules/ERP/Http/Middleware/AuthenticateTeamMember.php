<?php

namespace Modules\ERP\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTeamMember
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('erp_team')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('erp.team.login');
        }

        $member = Auth::guard('erp_team')->user();
        if (!$member->isActive()) {
            Auth::guard('erp_team')->logout();
            return redirect()->route('erp.team.login')->withErrors(['email' => 'Your account is suspended.']);
        }

        // Set tenant ID in session so global scopes work
        session(['tenant_id' => $member->tenant_id]);
        session(['erp_team_member_id' => $member->id]);

        // Login as the tenant owner on the web guard dynamically for this request
        // so that all existing tenant controllers and Auth::user() work seamlessly!
        $owner = $member->tenant?->user;
        if ($owner) {
            Auth::guard('web')->setUser($owner);
        }

        return $next($request);
    }
}
