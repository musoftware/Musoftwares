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
     * Enforce that only the tenant owner (not a team member) can manage the team.
     */
    protected function checkOwner()
    {
        if (session()->has('erp_team_member_id')) {
            abort(403, 'Only the workspace owner can manage team members.');
        }
    }

    /**
     * Display a listing of the team members.
     */
    public function index(): InertiaResponse
    {
        $this->checkOwner();

        $user = Auth::user();
        $hasFeature = $user->hasModuleSubscription('erp-team-members');
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $members = collect();
        if ($hasFeature) {
            $members = TeamMember::where('tenant_id', $tenant->id)
                ->latest()
                ->get()
                ->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'role' => $member->role,
                        'status' => $member->status,
                        'invited_at' => $member->invited_at?->format('Y-m-d H:i') ?? '-',
                        'last_login_at' => $member->last_login_at?->format('Y-m-d H:i') ?? '-',
                    ];
                });
        }

        return Inertia::render('ERP/Team/Members', [
            'members' => $members,
            'hasFeature' => $hasFeature,
        ]);
    }

    /**
     * Store a newly created team member.
     */
    public function store(Request $request)
    {
        $this->checkOwner();

        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('erp_team_members')->where(function ($query) use ($tenant) {
                    return $query->where('tenant_id', $tenant->id);
                }),
            ],
            'password' => 'required|string|min:8',
            'role' => 'required|in:manager,member',
        ]);

        $member = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => 'active',
            'invited_by' => $user->id,
            'invited_at' => now(),
        ]);

        return back()->with('success', 'Team member ' . $member->name . ' has been added successfully.');
    }

    /**
     * Update the specified team member's role or status.
     */
    public function update(Request $request, $id)
    {
        $this->checkOwner();

        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        $member = TeamMember::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'role' => 'required|in:manager,member',
            'status' => 'required|in:active,suspended',
        ]);

        $member->update($validated);

        return back()->with('success', 'Team member details updated.');
    }

    /**
     * Remove the specified team member.
     */
    public function destroy($id)
    {
        $this->checkOwner();

        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        $member = TeamMember::where('tenant_id', $tenant->id)->findOrFail($id);

        $name = $member->name;
        $member->delete();

        return back()->with('success', 'Team member ' . $name . ' has been removed.');
    }
}
