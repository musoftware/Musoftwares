<?php

namespace App\Services;

use App\Models\UserCredential;
use Illuminate\Support\Facades\Auth;

class UserNoteService
{
    public function createNote(int $userId, array $data): UserCredential
    {
        return UserCredential::create([
            'user_id'  => $userId,
            'admin_id' => Auth::id(),
            'category' => $data['category'],
            'title'    => $data['title'],
            'content'  => $data['content'],
        ]);
    }

    public function deleteNote(int $userId, int $noteId): void
    {
        $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);
        $note->delete();
    }

    public function archiveNote(int $userId, int $noteId): void
    {
        $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);

        if ($note->category === 'archived') {
            throw new \Exception('Note is already archived.');
        }

        $note->archive();
    }

    public function unarchiveNote(int $userId, int $noteId): ?string
    {
        $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);

        if ($note->category !== 'archived') {
            throw new \Exception('Note is not archived.');
        }

        $note->unarchive();
        
        return $note->original_category;
    }

    public function getStats(int $userId): array
    {
        return [
            'total'    => UserCredential::where('user_id', $userId)->where('category', '!=', 'archived')->count(),
            'password' => UserCredential::where('user_id', $userId)->where('category', 'password')->count(),
            'anydesk'  => UserCredential::where('user_id', $userId)->where('category', 'anydesk')->count(),
            'notes'    => UserCredential::where('user_id', $userId)->where('category', 'notes')->count(),
            'archived' => UserCredential::where('user_id', $userId)->where('category', 'archived')->count(),
        ];
    }
}
