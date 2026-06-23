<?php

namespace Modules\ERP\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TeamMember;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TeamMemberController extends Controller
{


    /**
     * Display a listing of the team members.
     */
    public function index(): InertiaResponse
    {

        $user = auth('erp_team')->user();
        $hasFeature = $user->hasModuleSubscription('erp-team-members');
        $tenant = auth('erp_team')->user()->tenant;

        $members = collect();
        $activeMembersCount = 0;
        
        $capacityLimit = $user->hasModuleSubscription('crm-team-10') ? 10 : 3;
        $hasAdvancedRolesAddon = $user->hasModuleSubscription('crm-advanced-roles');

        if ($hasFeature) {
            $allMembers = TeamMember::where('tenant_id', $tenant->id)->latest()->get();
            $activeMembersCount = $allMembers->where('status', 'active')->count();

            $allRoles = TeamMember::getAllRoles();

            $members = $allMembers->map(function ($member) use ($allRoles) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'role' => $member->role,
                        'role_label' => $allRoles[$member->role] ?? $member->role,
                        'status' => $member->status,
                        'invited_at' => $member->invited_at?->format('Y-m-d H:i') ?? '-',
                        'last_login_at' => $member->last_login_at?->format('Y-m-d H:i') ?? '-',
                    ];
                });
        }

        return Inertia::render('ERP/Team/Members', [
            'members' => $members,
            'hasFeature' => $hasFeature,
            'capacityLimit' => $capacityLimit,
            'activeMembersCount' => $activeMembersCount,
            'hasAdvancedRolesAddon' => $hasAdvancedRolesAddon,
            'basicRoles' => TeamMember::getBasicRoles(),
            'advancedRoles' => TeamMember::getAdvancedRoles(),
        ]);
    }

    /**
     * Store a newly created team member.
     */
    public function store(Request $request)
    {

        $user = auth('erp_team')->user();
        $tenant = auth('erp_team')->user()->tenant;

        $capacityLimit = $user->hasModuleSubscription('crm-team-10') ? 10 : 3;
        // Count active AND pending members against capacity limit
        $activeMembers = TeamMember::where('tenant_id', $tenant->id)->whereIn('status', ['active', 'pending'])->count();

        if ($activeMembers >= $capacityLimit) {
            return back()->with('error', __('erp.team_capacity_reached', ['limit' => $capacityLimit]));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('erp_team_members')->where(function ($query) use ($tenant) {
                    return $query->where('tenant_id', $tenant->id);
                }),
            ],
            'role' => 'required|string|in:' . implode(',', array_keys(TeamMember::getAllRoles())),
        ]);

        $isAdvancedRole = array_key_exists($validated['role'], TeamMember::getAdvancedRoles());
        if ($isAdvancedRole && !$user->hasModuleSubscription('crm-advanced-roles')) {
            return back()->with('error', __('erp.advanced_roles_addon_required'));
        }

        if ($validated['role'] === TeamMember::ROLE_ADMIN && $user->role !== TeamMember::ROLE_ADMIN) {
            abort(403, 'Privilege Escalation Prevented: Only administrators can assign the admin role.');
        }

        $member = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            // They will set their password later via the invite link
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => $validated['role'],
            'status' => 'pending',
            'invited_by' => $user->id,
            'invited_at' => now(),
        ]);

        \Illuminate\Support\Facades\Mail::to($member->email)->send(new \Modules\ERP\Mail\TeamMemberInviteMail($member));

        return back()->with('success', __('erp.team_member_added_and_invited', ['name' => $member->name]));
    }

    /**
     * Update the specified team member's role or status.
     */
    public function update(Request $request, $id)
    {

        $user = auth('erp_team')->user();
        $tenant = auth('erp_team')->user()->tenant;
        $member = TeamMember::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'role' => 'required|string|in:' . implode(',', array_keys(TeamMember::getAllRoles())),
            'status' => 'required|in:active,suspended,pending',
        ]);

        $isAdvancedRole = array_key_exists($validated['role'], TeamMember::getAdvancedRoles());
        if ($isAdvancedRole && !$user->hasModuleSubscription('crm-advanced-roles')) {
            return back()->with('error', __('erp.advanced_roles_addon_required'));
        }

        if ($validated['role'] === TeamMember::ROLE_ADMIN && $user->role !== TeamMember::ROLE_ADMIN) {
            abort(403, 'Privilege Escalation Prevented: Only administrators can assign the admin role.');
        }

        if ($member->role === TeamMember::ROLE_ADMIN && $user->role !== TeamMember::ROLE_ADMIN) {
            abort(403, 'Privilege Escalation Prevented: You cannot modify an administrator account.');
        }

        if ($validated['status'] === 'active' && $member->status !== 'active') {
            $capacityLimit = $user->hasModuleSubscription('crm-team-10') ? 10 : 3;
            $activeMembers = TeamMember::where('tenant_id', $tenant->id)->whereIn('status', ['active', 'pending'])->count();

            if ($activeMembers >= $capacityLimit) {
                return back()->with('error', __('erp.team_capacity_reached', ['limit' => $capacityLimit]));
            }
        }

        $member->update($validated);

        return back()->with('success', __('erp.team_member_updated'));
    }

    /**
     * Resend the invitation email to a pending team member.
     */
    public function resendInvite($id)
    {

        $tenant = auth('erp_team')->user()->tenant;
        $member = TeamMember::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($member->status !== 'pending') {
            return back()->with('info', __('erp.member_already_active'));
        }

        \Illuminate\Support\Facades\Mail::to($member->email)->send(new \Modules\ERP\Mail\TeamMemberInviteMail($member));

        return back()->with('success', __('erp.invite_resent_successfully'));
    }

    /**
     * Remove the specified team member.
     */
    public function destroy($id)
    {

        $user = auth('erp_team')->user();
        $tenant = auth('erp_team')->user()->tenant;
        $member = TeamMember::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($member->role === TeamMember::ROLE_ADMIN && $user->role !== TeamMember::ROLE_ADMIN) {
            abort(403, 'Privilege Escalation Prevented: You cannot delete an administrator account.');
        }

        $name = $member->name;
        $member->delete();

        return back()->with('success', __('erp.team_member_removed', ['name' => $name]));
    }
}
