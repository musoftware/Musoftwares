<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAdminNote;
use Illuminate\Http\Request;

class ProjectAdminNoteController extends Controller
{
    /**
     * List all admin notes for a project.
     */
    public function index(Project $project)
    {
        $notes = $project->adminNotes()
            ->with('author:id,name')
            ->latest()
            ->get();

        return response()->json($notes);
    }

    /**
     * Store a new admin note.
     */
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'content' => 'required|string',
            'category' => 'nullable|string',
            'is_pinned' => 'nullable|boolean',
        ]);

        $note = $project->adminNotes()->create([
            'author_id' => auth()->id() ?? 1,
            'content' => $data['content'],
            'category' => $data['category'] ?? 'General',
            'is_pinned' => $data['is_pinned'] ?? false,
        ]);

        return response()->json($note->load('author:id,name'));
    }

    /**
     * Update an admin note.
     */
    public function update(Request $request, Project $project, ProjectAdminNote $note)
    {
        if ($note->project_id !== $project->id) {
            abort(404);
        }

        $data = $request->validate([
            'content' => 'required|string',
            'category' => 'nullable|string',
            'is_pinned' => 'nullable|boolean',
        ]);

        $note->update([
            'content' => $data['content'],
            'category' => $data['category'] ?? $note->category,
            'is_pinned' => $data['is_pinned'] ?? $note->is_pinned,
        ]);

        return response()->json($note->load('author:id,name'));
    }

    /**
     * Delete an admin note.
     */
    public function destroy(Project $project, ProjectAdminNote $note)
    {
        if ($note->project_id !== $project->id) {
            abort(404);
        }

        $note->delete();

        return response()->json(['success' => true]);
    }
}
