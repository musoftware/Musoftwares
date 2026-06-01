<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantNote;

/**
 * Workspace Scratchpad Note Controller.
 * Handles CRUD for tenant-level personal notes on the ERP Dashboard.
 */
class TenantNoteController extends Controller
{
    // ── Guards ───────────────────────────────────────────────────────

    private function resolveTenant(): Tenant
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail();
    }

    private function authorizeNote(TenantNote $note, Tenant $tenant): void
    {
        if ($note->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_note'));
        }
    }

    // ── Store (Create) ───────────────────────────────────────────────

    /**
     * Create a new workspace note.
     */
    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();

        $validated = $request->validate([
            'title'    => 'sometimes|string|max:255',
            'content'  => 'nullable|string',
            'category' => 'sometimes|string|in:Internal,Client,Project',
        ]);

        $note = TenantNote::create([
            'tenant_id'  => $tenant->id,
            'created_by' => Auth::id(),
            'title'      => $validated['title'] ?? 'Untitled Note',
            'content'    => $validated['content'] ?? '',
            'category'   => $validated['category'] ?? 'Internal',
            'pinned'     => false,
        ]);

        return back()->with('success', __('general.note_created'));
    }

    // ── Update (Save) ────────────────────────────────────────────────

    /**
     * Update a workspace note's content, title, and category.
     */
    public function update(Request $request, TenantNote $note)
    {
        $tenant = $this->resolveTenant();
        $this->authorizeNote($note, $tenant);

        $validated = $request->validate([
            'title'    => 'sometimes|string|max:255',
            'content'  => 'nullable|string',
            'category' => 'sometimes|string|in:Internal,Client,Project',
        ]);

        $note->update($validated);

        return back()->with('success', __('general.note_saved'));
    }

    // ── Toggle Pin ───────────────────────────────────────────────────

    /**
     * Toggle the pinned state of a workspace note.
     */
    public function togglePin(TenantNote $note)
    {
        $tenant = $this->resolveTenant();
        $this->authorizeNote($note, $tenant);

        $note->update(['pinned' => !$note->pinned]);

        return back()->with('success', $note->pinned ? 'Note pinned.' : 'Note unpinned.');
    }

    // ── Destroy (Delete) ─────────────────────────────────────────────

    /**
     * Delete a workspace note.
     */
    public function destroy(TenantNote $note)
    {
        $tenant = $this->resolveTenant();
        $this->authorizeNote($note, $tenant);

        $note->delete();

        return back()->with('success', __('general.note_deleted'));
    }
}
