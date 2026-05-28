<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Services\ActivityLogger;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        $clients = TenantClient::where('tenant_id', $tenant->id)->get(['id', 'name']);

        return Inertia::render('ERP/Projects/Create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        
        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'status' => 'required|string|in:Planning,Active,On Hold,Completed,Cancelled',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $project = Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $user->id,
        ]);

        ActivityLogger::log(
            'project_created',
            "Project '{$project->name}' was created.",
            $project,
            $project->client_id
        );

        return redirect()->route('erp.dashboard', ['section' => 'projects'])->with('success', 'Project created successfully.');
    }

    public function edit(Project $project)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        if ($project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $clients = TenantClient::where('tenant_id', $tenant->id)->get(['id', 'name']);

        return Inertia::render('ERP/Projects/Edit', [
            'project' => $project,
            'clients' => $clients,
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        // Ensure user owns this project via tenant
        if (!$tenant || $project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:erp_tenant_clients,id',
            'status' => 'required|string|in:Planning,Active,On Hold,Completed,Cancelled',
            'budget' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $project->update([
            'name' => $validated['name'],
            'client_id' => $validated['client_id'],
            'status' => $validated['status'],
            'budget' => $validated['budget'] ?? 0,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        ActivityLogger::log(
            'project_updated',
            "Project '{$project->name}' was updated.",
            $project,
            $project->client_id
        );

        return redirect()->route('erp.dashboard', ['section' => 'projects'])->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $name = $project->name;
        $project->delete();

        ActivityLogger::log(
            'project_deleted',
            "Project '{$name}' was deleted.",
            null,
            null
        );

        return back()->with('success', 'Project deleted successfully.');
    }
}
