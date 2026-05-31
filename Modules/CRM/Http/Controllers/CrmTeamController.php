<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Models\CrmTeamMember;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CrmTeamController extends Controller
{
    /**
     * Only the workspace owner (not a team member) can manage team.
     */
    protected function ensureOwner()
    {
        if (session()->has('crm_team_member_id')) {
            abort(403, __('crm.only_owner_can_manage_team'));
        }
    }

    /**
     * Get the current user's workspace.
     */
    protected function getWorkspace()
    {
        $user = Auth::user();
        $workspaceId = session('crm_workspace_id');

        if ($workspaceId) {
            return Workspace::where('id', $workspaceId)->where('user_id', $user->id)->first();
        }

        return Workspace::where('user_id', $user->id)->first();
    }

    /**
     * Display the CRM team members list.
     */
    public function index()
    {
        $this->ensureOwner();

        $user = Auth::user();
        $workspace = $this->getWorkspace();

        $hasFeature = $user->hasModuleSubscription('erp-team-members');
        $capacityLimit = $user->hasModuleSubscription('crm-team-10') ? 10 : 3;
        $hasAdvancedRolesAddon = $user->hasModuleSubscription('crm-advanced-roles');

        $members = collect();
        $activeMembersCount = 0;

        if ($hasFeature && $workspace) {
            $allMembers = CrmTeamMember::where('workspace_id', $workspace->id)->latest()->get();
            $activeMembersCount = $allMembers->where('status', 'active')->count();

            $allRoles = CrmTeamMember::getAllRoles();

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

        return Inertia::render('CRM/Team/Members', [
            'members' => $members,
            'hasFeature' => $hasFeature,
            'capacityLimit' => $capacityLimit,
            'activeMembersCount' => $activeMembersCount,
            'hasAdvancedRolesAddon' => $hasAdvancedRolesAddon,
            'roles' => CrmTeamMember::getAllRoles(),
            'basicRoles' => CrmTeamMember::getBasicRoles(),
            'advancedRoles' => CrmTeamMember::getAdvancedRoles(),
            'loginUrl' => route('crm.team.login'),
            'translations' => [
                'team_members' => __('crm.team_members'),
                'team_members_desc' => __('crm.team_members_desc'),
                'seats_used' => __('crm.seats_used'),
                'upgrade_capacity' => __('crm.upgrade_capacity'),
                'invite_member' => __('crm.invite_member'),
                'team_login_info' => __('crm.team_login_info'),
                'full_name' => __('crm.full_name'),
                'email_address' => __('crm.email_address'),
                'temporary_password' => __('crm.temporary_password'),
                'access_role' => __('crm.access_role'),
                'account_status' => __('crm.account_status'),
                'basic_roles' => __('crm.basic_roles'),
                'advanced_roles' => __('crm.advanced_roles'),
                'advanced_roles_locked' => __('crm.advanced_roles_locked'),
                'advanced_roles_unlock' => __('crm.advanced_roles_unlock'),
                'cancel' => __('crm.cancel'),
                'send_invite' => __('crm.send_invite'),
                'save_changes' => __('crm.save_changes'),
                'update_member_details' => __('crm.update_member_details'),
                'remove_member' => __('crm.remove_member'),
                'remove_team_member' => __('crm.remove_team_member'),
                'no_team_members_yet' => __('crm.no_team_members_yet'),
                'no_team_members_desc' => __('crm.no_team_members_desc'),
                'last_login_at' => __('crm.last_login_at'),
                'status_active' => __('crm.status_active'),
                'status_suspended' => __('crm.status_suspended'),
                'active_access_allowed' => __('crm.active_access_allowed'),
                'suspended_access_blocked' => __('crm.suspended_access_blocked'),
                'capacity_limit_warning' => __('crm.capacity_limit_warning'),
                'role_sales_manager' => __('crm.role_sales_manager'),
            ],
        ]);
    }

    /**
     * Store a new team member.
     */
    public function store(Request $request)
    {
        $this->ensureOwner();

        $user = Auth::user();
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return back()->with('error', __('crm.tenant_not_found'));
        }

        $capacityLimit = $user->hasModuleSubscription('crm-team-10') ? 10 : 3;
        $activeMembers = CrmTeamMember::where('workspace_id', $workspace->id)->where('status', 'active')->count();

        if ($activeMembers >= $capacityLimit) {
            return back()->with('error', __('crm.team_capacity_reached', ['limit' => $capacityLimit]));
        }

        $allRoles = CrmTeamMember::getAllRoles();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('crm_team_members')->where(function ($query) use ($workspace) {
                    return $query->where('workspace_id', $workspace->id);
                }),
            ],
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:' . implode(',', array_keys($allRoles)),
        ]);

        $isAdvancedRole = array_key_exists($validated['role'], CrmTeamMember::getAdvancedRoles());
        if ($isAdvancedRole && !$user->hasModuleSubscription('crm-advanced-roles')) {
            return back()->with('error', __('crm.advanced_roles_addon_required'));
        }

        try {
            $member = CrmTeamMember::create([
                'workspace_id' => $workspace->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'status' => 'active',
                'invited_by' => $user->id,
                'invited_at' => now(),
            ]);

            return back()->with('success', __('crm.team_member_added', ['name' => $member->name]));
        } catch (\Exception $e) {
            Log::error("Failed to create CRM team member: " . $e->getMessage());
            return back()->with('error', __('errors.database_error'));
        }
    }

    /**
     * Update a team member's role or status.
     */
    public function update(Request $request, $id)
    {
        $this->ensureOwner();

        $user = Auth::user();
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return back()->with('error', __('crm.tenant_not_found'));
        }

        $member = CrmTeamMember::where('workspace_id', $workspace->id)->findOrFail($id);

        $allRoles = CrmTeamMember::getAllRoles();

        $validated = $request->validate([
            'role' => 'required|string|in:' . implode(',', array_keys($allRoles)),
            'status' => 'required|in:active,suspended',
        ]);

        $isAdvancedRole = array_key_exists($validated['role'], CrmTeamMember::getAdvancedRoles());
        if ($isAdvancedRole && !$user->hasModuleSubscription('crm-advanced-roles')) {
            return back()->with('error', __('crm.advanced_roles_addon_required'));
        }

        if ($validated['status'] === 'active' && $member->status !== 'active') {
            $capacityLimit = $user->hasModuleSubscription('crm-team-10') ? 10 : 3;
            $activeMembers = CrmTeamMember::where('workspace_id', $workspace->id)->where('status', 'active')->count();

            if ($activeMembers >= $capacityLimit) {
                return back()->with('error', __('crm.team_capacity_reached', ['limit' => $capacityLimit]));
            }
        }

        $member->update($validated);

        return back()->with('success', __('crm.team_member_updated'));
    }

    /**
     * Remove a team member.
     */
    public function destroy($id)
    {
        $this->ensureOwner();

        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return back()->with('error', __('crm.tenant_not_found'));
        }

        $member = CrmTeamMember::where('workspace_id', $workspace->id)->findOrFail($id);

        $name = $member->name;
        $member->delete();

        return back()->with('success', __('crm.team_member_removed', ['name' => $name]));
    }
}
