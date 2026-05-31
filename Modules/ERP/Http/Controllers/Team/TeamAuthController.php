<?php

namespace Modules\ERP\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TeamAuthController extends Controller
{
    /**
     * Show the team member login page.
     */
    public function showLogin()
    {
        if (Auth::guard('erp_team')->check()) {
            return redirect()->route('erp.dashboard');
        }

        return Inertia::render('ERP/Team/Login');
    }

    /**
     * Handle the team member login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::guard('erp_team')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $member = Auth::guard('erp_team')->user();

        if (!$member->isActive()) {
            Auth::guard('erp_team')->logout();
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended. Please contact your workspace administrator.'],
            ]);
        }

        // Set last login time
        $member->update(['last_login_at' => now()]);

        // Put tenant ID in session
        session(['tenant_id' => $member->tenant_id]);
        session(['erp_team_member_id' => $member->id]);

        $redirectRoute = match ($member->role) {
            \Modules\ERP\Models\TeamMember::ROLE_SALES_AGENT => route('crm.workspaces.telesales'),
            \Modules\ERP\Models\TeamMember::ROLE_CALL_CENTER => route('crm.workspaces.telesales'),
            \Modules\ERP\Models\TeamMember::ROLE_SALES_MANAGER => route('crm.workspaces.manager'),
            \Modules\ERP\Models\TeamMember::ROLE_MARKETING => route('crm.workspaces.marketing'),
            \Modules\ERP\Models\TeamMember::ROLE_SUPPORT_AGENT => route('crm.workspaces.support'),
            \Modules\ERP\Models\TeamMember::ROLE_SUPPORT_MANAGER => route('crm.workspaces.support'),
            \Modules\ERP\Models\TeamMember::ROLE_SOCIAL_MEDIA => route('crm.leads.index'),
            \Modules\ERP\Models\TeamMember::ROLE_LEGACY_MANAGER => route('crm.workspaces.manager'),
            \Modules\ERP\Models\TeamMember::ROLE_LEGACY_MEMBER => route('crm.dashboard'),
            default => route('erp.dashboard'),
        };

        return redirect()->to($redirectRoute)
            ->with('success', 'Logged in successfully to workspace: ' . $member->tenant->name);
    }

    /**
     * Log the team member out of the application.
     */
    public function logout(Request $request)
    {
        Auth::guard('erp_team')->logout();

        // Clear erp team sessions
        session()->forget(['tenant_id', 'erp_team_member_id']);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('erp.team.login')
            ->with('message', 'Logged out successfully.');
    }
}
