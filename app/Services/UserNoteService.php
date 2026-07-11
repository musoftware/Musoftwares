<?php

namespace App\Services;

use App\Models\UserCredential;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UserNoteService extends BaseService
{
    public function __construct(
        protected UserNoteAuditService $audit,
    ) {}

    public function createNote(int $userId, array $data): UserCredential
    {
        return $this->executeInTransaction(function () use ($userId, $data) {
            $note = UserCredential::create([
                'user_id' => $userId,
                'admin_id' => Auth::id(),
                'category' => $data['category'],
                'title' => $data['title'],
                'content' => $data['content'],
                'rotated_at' => $data['category'] === 'password' ? now() : null,
                'expires_at' => $this->resolveExpiry($data),
            ]);

            $this->audit->log(
                UserNoteAuditService::ACTION_CREATED,
                $userId,
                $note->id,
                ['category' => $note->category]
            );

            return $note;
        }, 'Failed to create user note');
    }

    public function updateNote(int $userId, int $noteId, array $data): UserCredential
    {
        return $this->executeInTransaction(function () use ($userId, $noteId, $data) {
            $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);
            $before = $note->only(['category', 'title', 'is_pinned', 'rotated_at', 'expires_at']);

            $note->fill([
                'title' => $data['title'] ?? $note->title,
                'content' => $data['content'] ?? $note->content,
                'category' => $data['category'] ?? $note->category,
                'rotated_at' => ($data['category'] ?? $note->category) === 'password' ? now() : $note->rotated_at,
                'expires_at' => array_key_exists('expires_at', $data)
                    ? ($data['expires_at'] ? Carbon::parse($data['expires_at']) : null)
                    : $this->resolveExpiry($data, $note->category),
            ])->save();

            $this->audit->log(
                UserNoteAuditService::ACTION_UPDATED,
                $userId,
                $note->id,
                [
                    'category' => $note->category,
                    'changed' => array_keys(array_diff_assoc($note->only(array_keys($before)), $before)),
                ]
            );

            return $note;
        }, 'Failed to update user note');
    }

    public function deleteNote(int $userId, int $noteId): void
    {
        $this->executeInTransaction(function () use ($userId, $noteId) {
            $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);
            $category = $note->category;
            $note->delete();

            $this->audit->log(
                UserNoteAuditService::ACTION_DELETED,
                $userId,
                $noteId,
                ['category' => $category]
            );
        }, 'Failed to delete user note');
    }

    public function archiveNote(int $userId, int $noteId): void
    {
        $this->executeInTransaction(function () use ($userId, $noteId) {
            $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);

            if ($note->category === 'archived') {
                throw new \Exception('Note is already archived.');
            }

            $note->archive();

            $this->audit->log(
                UserNoteAuditService::ACTION_ARCHIVED,
                $userId,
                $noteId,
                ['category' => $note->original_category]
            );
        }, 'Failed to archive user note');
    }

    public function unarchiveNote(int $userId, int $noteId): ?string
    {
        $originalCategory = null;

        $this->executeInTransaction(function () use ($userId, $noteId, &$originalCategory) {
            $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);

            if ($note->category !== 'archived') {
                throw new \Exception('Note is not archived.');
            }

            $note->unarchive();
            $originalCategory = $note->category;

            $this->audit->log(
                UserNoteAuditService::ACTION_UNARCHIVED,
                $userId,
                $noteId,
                ['category' => $originalCategory]
            );
        }, 'Failed to unarchive user note');

        return $originalCategory;
    }

    public function togglePin(int $userId, int $noteId): UserCredential
    {
        return $this->executeInTransaction(function () use ($userId, $noteId) {
            $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);
            $note->is_pinned = ! $note->is_pinned;
            $note->save();

            $this->audit->log(
                UserNoteAuditService::ACTION_PIN_TOGGLED,
                $userId,
                $noteId,
                ['category' => $note->category, 'is_pinned' => $note->is_pinned]
            );

            return $note;
        }, 'Failed to toggle pin');
    }

    public function revealNote(int $userId, int $noteId): UserCredential
    {
        $note = UserCredential::where('user_id', $userId)->findOrFail($noteId);
        $note->forceFill([
            'last_revealed_at' => now(),
            'last_revealed_by' => Auth::id(),
        ])->save();

        $this->audit->log(
            UserNoteAuditService::ACTION_REVEALED,
            $userId,
            $noteId,
            ['category' => $note->category],
        );

        return $note;
    }

    public function bulkArchive(int $userId, array $noteIds): int
    {
        return $this->executeInTransaction(function () use ($userId, $noteIds) {
            $notes = UserCredential::where('user_id', $userId)
                ->whereIn('id', $noteIds)
                ->where('category', '!=', 'archived')
                ->get();

            $count = 0;
            foreach ($notes as $note) {
                $note->archive();
                $count++;
            }

            $this->audit->log(
                UserNoteAuditService::ACTION_BULK_ARCHIVED,
                $userId,
                null,
                ['note_ids' => $notes->pluck('id')->all()],
            );

            return $count;
        }, 'Failed bulk archive');
    }

    public function bulkUnarchive(int $userId, array $noteIds): int
    {
        return $this->executeInTransaction(function () use ($userId, $noteIds) {
            $notes = UserCredential::where('user_id', $userId)
                ->whereIn('id', $noteIds)
                ->where('category', 'archived')
                ->get();

            $count = 0;
            foreach ($notes as $note) {
                $note->unarchive();
                $count++;
            }

            $this->audit->log(
                UserNoteAuditService::ACTION_BULK_UNARCHIVED,
                $userId,
                null,
                ['note_ids' => $notes->pluck('id')->all()],
            );

            return $count;
        }, 'Failed bulk unarchive');
    }

    public function bulkDelete(int $userId, array $noteIds): int
    {
        return $this->executeInTransaction(function () use ($userId, $noteIds) {
            $notes = UserCredential::where('user_id', $userId)
                ->whereIn('id', $noteIds)
                ->get();

            $count = 0;
            foreach ($notes as $note) {
                $note->delete();
                $count++;
            }

            $this->audit->log(
                UserNoteAuditService::ACTION_BULK_DELETED,
                $userId,
                null,
                ['note_ids' => $notes->pluck('id')->all()],
            );

            return $count;
        }, 'Failed bulk delete');
    }

    public function getStats(int $userId): array
    {
        $base = UserCredential::where('user_id', $userId);
        $expiringSoon = (clone $base)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(7))
            ->count();
        $expired = (clone $base)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        return [
            'total' => (clone $base)->where('category', '!=', 'archived')->count(),
            'password' => (clone $base)->where('category', 'password')->count(),
            'anydesk' => (clone $base)->where('category', 'anydesk')->count(),
            'notes' => (clone $base)->where('category', 'notes')->count(),
            'archived' => (clone $base)->where('category', 'archived')->count(),
            'pinned' => (clone $base)->where('is_pinned', true)->count(),
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ];
    }

    protected function resolveExpiry(array $data, ?string $category = null): ?Carbon
    {
        if (! empty($data['expires_at'])) {
            return Carbon::parse($data['expires_at']);
        }
        if (($data['category'] ?? $category) === 'password' && config('user_notes.default_password_ttl_days')) {
            return now()->addDays((int) config('user_notes.default_password_ttl_days'));
        }

        return null;
    }
}
