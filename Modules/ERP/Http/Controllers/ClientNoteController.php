<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\ClientNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ERP Client Note Controller.
 * Parallel to platform-level Admin/UserNoteController.
 * Recovered from old project: Admin/UserNotesController.
 */
class ClientNoteController extends Controller
{
    /**
     * Create a note on a client.
     */
    public function store(Request $request, TenantClient $client)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'category' => 'required|in:password,anydesk,notes',
        ]);

        $note = ClientNote::create([
            'tenant_id'  => $client->tenant_id,
            'client_id'  => $client->id,
            'created_by' => Auth::id(),
            'category'   => $request->input('category'),
            'title'      => $request->input('title'),
            'content'    => $request->input('content'),
        ]);

        return response()->json([
            'success' => true,
            'note'    => $this->formatNote($note),
            'stats'   => $this->getStats($client->id),
        ]);
    }

    /**
     * Delete a note.
     */
    public function destroy(TenantClient $client, ClientNote $note)
    {
        $this->authorize('delete', $note);
        $note->delete();

        return response()->json([
            'success' => true,
            'stats'   => $this->getStats($client->id),
        ]);
    }

    /**
     * Archive a note — preserves original category.
     * Recovered from old project: UserNotesController::archiveNote()
     */
    public function archive(TenantClient $client, ClientNote $note)
    {
        $note->archive();

        return response()->json([
            'success' => true,
            'note'    => $this->formatNote($note->fresh()),
            'stats'   => $this->getStats($client->id),
        ]);
    }

    /**
     * Restore an archived note.
     * Recovered from old project: UserNotesController::unarchiveNote()
     */
    public function unarchive(TenantClient $client, ClientNote $note)
    {
        $note->unarchive();

        return response()->json([
            'success' => true,
            'note'    => $this->formatNote($note->fresh()),
            'stats'   => $this->getStats($client->id),
        ]);
    }

    /**
     * Per-client, per-category statistics.
     * Recovered from old project: UserNotesController::getStatistics()
     */
    private function getStats(int $clientId): array
    {
        return [
            'total'    => ClientNote::where('client_id', $clientId)->where('category', '!=', 'archived')->count(),
            'password' => ClientNote::where('client_id', $clientId)->where('category', 'password')->count(),
            'anydesk'  => ClientNote::where('client_id', $clientId)->where('category', 'anydesk')->count(),
            'notes'    => ClientNote::where('client_id', $clientId)->where('category', 'notes')->count(),
            'archived' => ClientNote::where('client_id', $clientId)->where('category', 'archived')->count(),
        ];
    }

    private function formatNote(ClientNote $note): array
    {
        return [
            'id'                => $note->id,
            'category'          => $note->category,
            'original_category' => $note->original_category,
            'title'             => $note->title,
            'content'           => $note->content,
            'created_by'        => $note->created_by,
            'created_at'        => $note->created_at?->toISOString(),
        ];
    }
}
