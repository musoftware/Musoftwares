<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Core\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'active');
        
        $query = Project::with(['creator', 'platformClient', 'tenantClient']);
        
        if ($status === 'archived') {
            $query->where('status', 'archived');
        } else {
            $query->where('status', '!=', 'archived');
        }

        $projects = $query->latest()->get();
        $clients = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => $projects,
            'clients' => $clients,
            'currentTab' => $status,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'budget' => 'nullable|numeric',
        ]);

        $project = new Project();
        $project->tenant_id = 1; // Assuming 1 is platform tenant, or get from config
        $project->client_id = $validated['client_id'];
        $project->name = $validated['name'];
        $project->budget = $validated['budget'] ?? null;
        $project->status = 'active';
        $project->created_by = auth()->id();
        $project->save();

        return redirect()->back()->with('success', 'Project created successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'budget' => 'nullable|numeric',
        ]);

        $project = Project::findOrFail($id);
        $project->name = $validated['name'];
        if (isset($validated['budget'])) {
            $project->budget = $validated['budget'];
        }
        $project->save();

        return redirect()->back()->with('success', 'Project updated successfully.');
    }

    public function archive($id)
    {
        $project = Project::findOrFail($id);
        $project->status = 'archived';
        $project->save();

        return redirect()->back()->with('success', 'Project archived.');
    }

    public function restore($id)
    {
        $project = Project::findOrFail($id);
        $project->status = 'active';
        $project->save();

        return redirect()->back()->with('success', 'Project restored.');
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return redirect()->back()->with('success', 'Project deleted.');
    }
}
