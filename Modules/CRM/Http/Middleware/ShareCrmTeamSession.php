<?php

namespace Modules\CRM\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ShareCrmTeamSession
{
    /**
     * If a CRM team member is logged in, set up their workspace context
     * and authenticate the web guard as the workspace owner.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('crm_team')->check()) {
            $member = Auth::guard('crm_team')->user();

            if ($member && $member->isActive()) {
                // Set CRM workspace context
                session(['crm_workspace_id' => $member->workspace_id]);
                session(['crm_team_member_id' => $member->id]);

                // Authenticate web guard as workspace owner for subscription checks
                $owner = $member->workspace?->owner;
                if ($owner) {
                    Auth::guard('web')->setUser($owner);
                }
            } else {
                // Suspended member — log them out
                Auth::guard('crm_team')->logout();
                session()->forget(['crm_workspace_id', 'crm_team_member_id']);
            }
        }

        return $next($request);
    }
}
