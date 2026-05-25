<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\CRM\Models\UserNote;
use App\Services\UserNoteService;
use App\Http\Requests\Admin\User\StoreUserNoteRequest;
use App\Http\Resources\UserNoteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function __construct(
        protected UserNoteService $userNoteService
    ) {}
    /**
     * Show notes index page for a user (Inertia full-page).
     */
    public function index(Request $request, int $userId): InertiaResponse
    {
        $user  = User::findOrFail($userId);
        $notes = UserNote::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($note) => (new UserNoteResource($note))->resolve());

        return Inertia::render('Admin/Users/Notes', [
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'notes' => $notes,
            'stats' => $this->userNoteService->getStats($userId),
        ]);
    }

    /**
     * Create a new note for a user.
     * Recovered from old project: UserNotesController::addNote()
     */
    public function store(StoreUserNoteRequest $request, int $userId): JsonResponse
    {
        User::findOrFail($userId);

        $note = $this->userNoteService->createNote($userId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'note'    => (new UserNoteResource($note))->resolve(),
            'stats'   => $this->userNoteService->getStats($userId),
        ]);
    }

    /**
     * Delete a note permanently.
     * Recovered from old project: UserNotesController::deleteNote()
     */
    public function destroy(Request $request, int $userId, int $noteId): JsonResponse
    {
        $this->userNoteService->deleteNote($userId, $noteId);

        return response()->json([
            'success' => true,
            'message' => 'Note deleted.',
            'stats'   => $this->userNoteService->getStats($userId),
        ]);
    }

    /**
     * Archive a note — moves to 'archived' category, saves original category.
     * Recovered from old project: UserNotesController::archiveNote()
     */
    public function archive(Request $request, int $userId, int $noteId): JsonResponse
    {
        try {
            $this->userNoteService->archiveNote($userId, $noteId);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Note archived.',
            'stats'   => $this->userNoteService->getStats($userId),
        ]);
    }

    /**
     * Restore an archived note to its original category.
     * Recovered from old project: UserNotesController::unarchiveNote()
     */
    public function unarchive(Request $request, int $userId, int $noteId): JsonResponse
    {
        try {
            $originalCategory = $this->userNoteService->unarchiveNote($userId, $noteId);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Note restored to ' . ($originalCategory ?: 'notes') . '.',
            'stats'   => $this->userNoteService->getStats($userId),
        ]);
    }
}
