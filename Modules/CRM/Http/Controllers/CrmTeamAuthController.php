<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\CRM\Models\CrmTeamMember;

class CrmTeamAuthController extends Controller
{
    /**
     * Show the CRM team member login page.
     */
    public function showLogin()
    {
        if (Auth::guard('crm_team')->check()) {
            return redirect()->route('crm.dashboard');
        }

        return Inertia::render('CRM/Team/Login');
    }

    /**
     * Handle the CRM team member login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::guard('crm_team')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $member = Auth::guard('crm_team')->user();

        if ($member->status === 'pending') {
            Auth::guard('crm_team')->logout();
            throw ValidationException::withMessages([
                'email' => [__('crm.account_pending_setup')],
            ]);
        }

        if (!$member->isActive()) {
            Auth::guard('crm_team')->logout();
            throw ValidationException::withMessages([
                'email' => [__('crm.account_suspended')],
            ]);
        }

        // Update last login
        $member->update(['last_login_at' => now()]);

        // Set CRM workspace context
        session(['crm_workspace_id' => $member->workspace_id]);
        session(['crm_team_member_id' => $member->id]);

        // Determine redirect based on role
        $redirectRoute = match ($member->role) {
            CrmTeamMember::ROLE_SALES_AGENT => route('crm.workspaces.telesales'),
            CrmTeamMember::ROLE_SOCIAL_MEDIA => route('crm.leads.index'),
            CrmTeamMember::ROLE_SALES_MANAGER => route('crm.workspaces.manager'),
            CrmTeamMember::ROLE_SUPPORT_AGENT => route('crm.workspaces.support'),
            CrmTeamMember::ROLE_SUPPORT_MANAGER => route('crm.workspaces.support'),
            CrmTeamMember::ROLE_MANAGER => route('crm.workspaces.manager'),
            default => route('crm.dashboard'),
        };

        return redirect()->to($redirectRoute)
            ->with('success', __('crm.login_success'));
    }

    /**
     * Log the CRM team member out.
     */
    public function logout(Request $request)
    {
        Auth::guard('crm_team')->logout();

        session()->forget(['crm_workspace_id', 'crm_team_member_id']);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('crm.team.login')
            ->with('message', __('crm.logout_success'));
    }
}
