<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Admin notes management for a specific user.
 * Recovered from old project: UserNotesController.
 *
 * Operational workflow:
 *   - Categories: password | anydesk | notes | archived
 *   - Archive moves note to 'archived' while saving original_category
 *   - Unarchive restores original_category
 *   - Statistics returned on every mutating action (for reactive UI)
 */
class UserNoteController extends Controller
{
    /**
     * Show notes index page for a user (Inertia full-page).
     */
    public function index(Request $request, int $userId): InertiaResponse
    {
        $user  = User::findOrFail($userId);
        $notes = UserNote::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($note) => $this->formatNote($note));

        return Inertia::render('Admin/Users/Notes', [
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'notes' => $notes,
            'stats' => $this->getStats($userId),
        ]);
    }

    /**
     * Create a new note for a user.
     * Recovered from old project: UserNotesController::addNote()
     */
    public function store(Request $request, int $userId): JsonResponse
    {
        User::findOrFail($userId);

        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'category' => 'required|in:password,anydesk,notes',
        ]);

        $note = UserNote::create([
            'user_id'  => $userId,
            'admin_id' => Auth::id(),
            'category' => $request->input('category'),
            'title'    => $request->input('title'),
            'content'  => $request->input('content'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'note'    => $this->formatNote($note),
            'stats'   => $this->getStats($userId),
        ]);
    }

    /**
     * Delete a note permanently.
     * Recovered from old project: UserNotesController::deleteNote()
     */
    public function destroy(Request $request, int $userId, int $noteId): JsonResponse
    {
        $note = UserNote::where('user_id', $userId)->findOrFail($noteId);
        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted.',
            'stats'   => $this->getStats($userId),
        ]);
    }

    /**
     * Archive a note — moves to 'archived' category, saves original category.
     * Recovered from old project: UserNotesController::archiveNote()
     */
    public function archive(Request $request, int $userId, int $noteId): JsonResponse
    {
        $note = UserNote::where('user_id', $userId)->findOrFail($noteId);

        if ($note->category === 'archived') {
            return response()->json([
                'success' => false,
                'message' => 'Note is already archived.',
            ], 400);
        }

        $note->archive();

        return response()->json([
            'success' => true,
            'message' => 'Note archived.',
            'stats'   => $this->getStats($userId),
        ]);
    }

    /**
     * Restore an archived note to its original category.
     * Recovered from old project: UserNotesController::unarchiveNote()
     */
    public function unarchive(Request $request, int $userId, int $noteId): JsonResponse
    {
        $note = UserNote::where('user_id', $userId)->findOrFail($noteId);

        if ($note->category !== 'archived') {
            return response()->json([
                'success' => false,
                'message' => 'Note is not archived.',
            ], 400);
        }

        $note->unarchive();

        return response()->json([
            'success' => true,
            'message' => 'Note restored to ' . ($note->original_category ?: 'notes') . '.',
            'stats'   => $this->getStats($userId),
        ]);
    }

    /**
     * Per-user, per-category statistics.
     * Recovered from old project: UserNotesController::getStatistics()
     */
    private function getStats(int $userId): array
    {
        return [
            'total'    => UserNote::where('user_id', $userId)->where('category', '!=', 'archived')->count(),
            'password' => UserNote::where('user_id', $userId)->where('category', 'password')->count(),
            'anydesk'  => UserNote::where('user_id', $userId)->where('category', 'anydesk')->count(),
            'notes'    => UserNote::where('user_id', $userId)->where('category', 'notes')->count(),
            'archived' => UserNote::where('user_id', $userId)->where('category', 'archived')->count(),
        ];
    }

    private function formatNote(UserNote $note): array
    {
        return [
            'id'                => $note->id,
            'category'          => $note->category,
            'original_category' => $note->original_category,
            'title'             => $note->title,
            'content'           => $note->content,
            'admin_id'          => $note->admin_id,
            'created_at'        => $note->created_at?->toISOString(),
            'updated_at'        => $note->updated_at?->toISOString(),
        ];
    }
}
