<?php

namespace Modules\ERP\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\TeamMember;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Password;

class InviteController extends Controller
{
    /**
     * Show the password setup form for an invited team member.
     */
    public function showAcceptForm(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(403, __('erp.invite_link_invalid_or_expired'));
        }

        $member = TeamMember::findOrFail($id);

        if ($member->status !== 'pending') {
            return redirect()->route('erp.login')->with('info', __('erp.account_already_active'));
        }

        return Inertia::render('ERP/Auth/AcceptInvite', [
            'memberId' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'signature' => $request->query('signature'),
            'expires' => $request->query('expires'),
        ]);
    }

    /**
     * Handle the incoming password setup request.
     */
    public function accept(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(403, __('erp.invite_link_invalid_or_expired'));
        }

        $member = TeamMember::findOrFail($id);

        if ($member->status !== 'pending') {
            return redirect()->route('erp.login')->with('info', __('erp.account_already_active'));
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $member->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        Auth::guard('erp_team')->login($member);

        return redirect()->route('erp.dashboard')->with('success', __('erp.account_activated_successfully'));
    }
}
