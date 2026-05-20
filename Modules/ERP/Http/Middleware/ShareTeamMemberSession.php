<?php

namespace Modules\ERP\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ShareTeamMemberSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the user has an active session under the erp_team guard
        if (Auth::guard('erp_team')->check()) {
            $member = Auth::guard('erp_team')->user();

            if ($member && $member->isActive()) {
                // Set tenant ID and team member ID in the session
                session(['tenant_id' => $member->tenant_id]);
                session(['erp_team_member_id' => $member->id]);

                // Dynamically authenticate the web guard as the tenant owner
                $owner = $member->tenant?->user;
                if ($owner) {
                    Auth::guard('web')->setUser($owner);
                }
            } else {
                // If the member is suspended or inactive, log them out
                Auth::guard('erp_team')->logout();
                session()->forget(['tenant_id', 'erp_team_member_id']);
            }
        }

        return $next($request);
    }
}
