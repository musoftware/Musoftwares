<?php

namespace Modules\CRM\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\CrmTeamMember;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class InviteController extends Controller
{
    /**
     * Show the accept invite page.
     */
    public function show(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'This invitation link has expired or is invalid.');
        }

        $member = CrmTeamMember::findOrFail($request->id);

        if ($member->status !== 'pending') {
            return redirect()->route('crm.team.login')->with('info', __('crm.account_already_active'));
        }

        return Inertia::render('CRM/Auth/AcceptInvite', [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
            ],
        ]);
    }

    /**
     * Accept the invite and set the password.
     */
    public function accept(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'This invitation link has expired or is invalid.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $member = CrmTeamMember::findOrFail($request->id);

        if ($member->status !== 'pending') {
            return redirect()->route('crm.team.login')->with('info', __('crm.account_already_active'));
        }

        $member->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        // Log the member in directly
        Auth::guard('crm_team')->login($member);
        
        session(['crm_workspace_id' => $member->workspace_id]);
        session(['crm_team_member_id' => $member->id]);

        return redirect()->route('crm.dashboard')->with('success', __('crm.account_setup_success'));
    }
}
