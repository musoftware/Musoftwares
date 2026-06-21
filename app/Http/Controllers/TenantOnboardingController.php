<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TeamMember;
use Illuminate\Support\Str;
use App\Models\User;

class TenantOnboardingController extends Controller
{
    public function showSetup(Request $request)
    {
        $user = $request->user();
        
        if (Tenant::where('user_id', $user->id)->exists()) {
            return redirect()->route('onboarding.tenant.roles');
        }

        return Inertia::render('Onboarding/TenantSetup', [
            'user' => $user
        ]);
    }

    public function storeSetup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|alpha_dash|max:255|unique:erp_tenants,subdomain',
            'logo' => 'nullable|image|max:2048'
        ]);

        $user = $request->user();

        if (Tenant::where('user_id', $user->id)->exists()) {
            return redirect()->route('onboarding.tenant.roles');
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('tenant-logos', 'public');
        }

        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'subdomain' => $request->subdomain,
            'logo' => $logoPath,
            'status' => 'active',
            'base_currency_id' => \App\Models\Currency::first()->id ?? 1,
        ]);

        return redirect()->route('onboarding.tenant.roles')->with('success', 'Workspace created successfully.');
    }

    public function showRoles(Request $request)
    {
        $user = $request->user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return redirect()->route('onboarding.tenant.setup');
        }

        // We can just fetch team members from this tenant
        $teamMembers = \Modules\ERP\Models\TeamMember::where('tenant_id', $tenant->id)->get();
        $roles = \Spatie\Permission\Models\Role::where('guard_name', 'erp_team')->get();

        return Inertia::render('Onboarding/RoleAssignment', [
            'tenant' => $tenant,
            'teamMembers' => $teamMembers,
            'roles' => $roles
        ]);
    }

    public function inviteRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user = $request->user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        // Check if user exists in the system or just create team member
        // In this ERP module, team members are separate from users but wait, let's look at TeamMember
        $teamMember = \Modules\ERP\Models\TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt(Str::random(10)), // they will reset via email
            'status' => 'active',
            'role' => $request->role,
        ]);

        return back()->with('success', 'Team member invited successfully.');
    }

    public function finish(Request $request)
    {
        return redirect()->route('dashboard');
    }
}
