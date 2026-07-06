<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserNoteAuditService extends BaseService
{
    public const ACTION_CREATED = 'user_notes.created';
    public const ACTION_UPDATED = 'user_notes.updated';
    public const ACTION_DELETED = 'user_notes.deleted';
    public const ACTION_ARCHIVED = 'user_notes.archived';
    public const ACTION_UNARCHIVED = 'user_notes.unarchived';
    public const ACTION_PIN_TOGGLED = 'user_notes.pin_toggled';
    public const ACTION_REVEALED = 'user_notes.revealed';
    public const ACTION_BULK_ARCHIVED = 'user_notes.bulk_archived';
    public const ACTION_BULK_UNARCHIVED = 'user_notes.bulk_unarchived';
    public const ACTION_BULK_DELETED = 'user_notes.bulk_deleted';

    public function log(
        string $action,
        int $targetUserId,
        ?int $noteId = null,
        array $meta = [],
        string $severity = AdminAuditLog::SEVERITY_INFO,
        ?Request $request = null,
    ): AdminAuditLog {
        $request ??= request();

        return AdminAuditLog::create([
            'actor_user_id'    => Auth::id(),
            'actor_ip'         => $request?->ip(),
            'actor_user_agent' => $request?->userAgent(),
            'action'           => $action,
            'severity'         => $severity,
            'target_type'      => User::class,
            'target_id'        => $targetUserId,
            'meta'             => array_filter([
                'note_id'    => $noteId,
                'note_count' => isset($meta['note_ids']) ? count($meta['note_ids']) : ($noteId ? 1 : null),
                'category'   => $meta['category'] ?? null,
                'is_pinned'  => array_key_exists('is_pinned', $meta) ? $meta['is_pinned'] : null,
                'note_ids'   => $meta['note_ids'] ?? null,
                'reason'     => $meta['reason'] ?? null,
            ], static fn ($v) => $v !== null),
        ]);
    }
}
