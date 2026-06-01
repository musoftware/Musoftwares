<?php

namespace App\Services;

use App\Models\UserNote;
use Illuminate\Support\Facades\Auth;

class UserNoteService
{
    public function createNote(int $userId, array $data): UserNote
    {
        return UserNote::create([
            'user_id'  => $userId,
            'admin_id' => Auth::id(),
            'category' => $data['category'],
            'title'    => $data['title'],
            'content'  => $data['content'],
        ]);
    }

    public function deleteNote(int $userId, int $noteId): void
    {
        $note = UserNote::where('user_id', $userId)->findOrFail($noteId);
        $note->delete();
    }

    public function archiveNote(int $userId, int $noteId): void
    {
        $note = UserNote::where('user_id', $userId)->findOrFail($noteId);

        if ($note->category === 'archived') {
            throw new \Exception('Note is already archived.');
        }

        $note->archive();
    }

    public function unarchiveNote(int $userId, int $noteId): ?string
    {
        $note = UserNote::where('user_id', $userId)->findOrFail($noteId);

        if ($note->category !== 'archived') {
            throw new \Exception('Note is not archived.');
        }

        $note->unarchive();
        
        return $note->original_category;
    }

    public function getStats(int $userId): array
    {
        return [
            'total'    => UserNote::where('user_id', $userId)->where('category', '!=', 'archived')->count(),
            'password' => UserNote::where('user_id', $userId)->where('category', 'password')->count(),
            'anydesk'  => UserNote::where('user_id', $userId)->where('category', 'anydesk')->count(),
            'notes'    => UserNote::where('user_id', $userId)->where('category', 'notes')->count(),
            'archived' => UserNote::where('user_id', $userId)->where('category', 'archived')->count(),
        ];
    }
}
