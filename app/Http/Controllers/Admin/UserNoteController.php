<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserNoteRequest;
use App\Http\Requests\Admin\User\UpdateUserNoteRequest;
use App\Http\Resources\UserNoteResource;
use App\Models\User;
use App\Models\UserCredential;
use App\Services\UserNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
 *   - Every mutation writes an entry to AdminAuditLog
 */
class UserNoteController extends Controller
{
    public function __construct(
        protected UserNoteService $userNoteService
    ) {}

    public function index(Request $request, int $userId): InertiaResponse
    {
        $user = User::findOrFail($userId);
        $notes = $this->fetchNotes($request, $userId);

        return Inertia::render('Admin/Users/Notes', [
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'notes' => $notes,
            'stats' => $this->userNoteService->getStats($userId),
            'flash' => session()->only(['success', 'error', 'warning']),
        ]);
    }

    public function indexJson(Request $request, int $userId): JsonResponse
    {
        User::findOrFail($userId);

        return response()->json([
            'data' => $this->fetchNotes($request, $userId),
            'stats' => $this->userNoteService->getStats($userId),
        ]);
    }

    public function store(StoreUserNoteRequest $request, int $userId)
    {
        User::findOrFail($userId);

        $note = $this->userNoteService->createNote($userId, $request->validated());

        return $this->respond($request, $userId, 'Note added successfully.', [
            'note' => (new UserNoteResource($note))->resolve(),
        ]);
    }

    public function update(UpdateUserNoteRequest $request, int $userId, int $noteId)
    {
        $note = $this->userNoteService->updateNote($userId, $noteId, $request->validated());

        return $this->respond($request, $userId, 'Note updated.', [
            'note' => (new UserNoteResource($note))->resolve(),
        ]);
    }

    public function destroy(Request $request, int $userId, int $noteId)
    {
        $this->userNoteService->deleteNote($userId, $noteId);

        return $this->respond($request, $userId, 'Note deleted.');
    }

    public function archive(Request $request, int $userId, int $noteId)
    {
        try {
            $this->userNoteService->archiveNote($userId, $noteId);
        } catch (\Exception $e) {
            return $this->respondError($request, $e->getMessage());
        }

        return $this->respond($request, $userId, 'Note archived.');
    }

    public function unarchive(Request $request, int $userId, int $noteId)
    {
        try {
            $originalCategory = $this->userNoteService->unarchiveNote($userId, $noteId);
        } catch (\Exception $e) {
            return $this->respondError($request, $e->getMessage());
        }

        return $this->respond(
            $request,
            $userId,
            'Note restored to '.($originalCategory ?: 'notes').'.'
        );
    }

    public function togglePin(Request $request, int $userId, int $noteId)
    {
        $note = $this->userNoteService->togglePin($userId, $noteId);

        return $this->respondJsonOrRedirect($request,
            ['success' => true, 'message' => 'Note pin status toggled.', 'is_pinned' => (bool) $note->is_pinned, 'stats' => $this->userNoteService->getStats($userId)],
            'Note pin status toggled.'
        );
    }

    public function reveal(Request $request, int $userId, int $noteId)
    {
        $note = $this->userNoteService->revealNote($userId, $noteId);

        return $this->respondJsonOrRedirect($request,
            ['success' => true, 'stats' => $this->userNoteService->getStats($userId)],
            'Note revealed.'
        );
    }

    public function bulkAction(Request $request, int $userId)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:archive,unarchive,delete',
            'note_ids' => 'required|array|min:1|max:100',
            'note_ids.*' => 'integer',
        ]);

        $count = match ($validated['action']) {
            'archive' => $this->userNoteService->bulkArchive($userId, $validated['note_ids']),
            'unarchive' => $this->userNoteService->bulkUnarchive($userId, $validated['note_ids']),
            'delete' => $this->userNoteService->bulkDelete($userId, $validated['note_ids']),
        };

        $message = ucfirst($validated['action'])." applied to {$count} note(s).";

        return $this->respond($request, $userId, $message, ['count' => $count]);
    }

    protected function fetchNotes(Request $request, int $userId): array
    {
        $query = UserCredential::where('user_id', $userId);

        if ($category = $request->query('category')) {
            if ($category === 'active') {
                $query->where('category', '!=', 'archived');
            } elseif (in_array($category, ['password', 'anydesk', 'notes', 'archived'], true)) {
                $query->where('category', $category);
            }
        }

        if ($pinned = $request->query('pinned')) {
            $query->where('is_pinned', (bool) $pinned);
        }

        $perPage = min(50, max(5, (int) $request->query('per_page', 24)));

        $page = $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = collect($page->items())
            ->map(fn ($note) => (new UserNoteResource($note))->resolve())
            ->all();

        return [
            'items' => $items,
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
            'per_page' => $page->perPage(),
        ];
    }

    protected function respond(Request $request, int $userId, string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        $payload = array_merge([
            'success' => true,
            'message' => $message,
            'stats' => $this->userNoteService->getStats($userId),
        ], $extra);

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->back()->with('success', $message);
    }

    protected function respondJsonOrRedirect(Request $request, array $jsonPayload, string $redirectMessage): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json($jsonPayload);
        }

        return redirect()->back()->with('success', $redirectMessage);
    }

    protected function respondError(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 400);
        }

        return redirect()->back()->with('error', $message);
    }
}
