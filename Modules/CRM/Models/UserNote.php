<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin notes attached to specific users.
 *
 * Recovered from old project: UserCredential model.
 * Categories: password | anydesk | notes | archived
 * Archive workflow: category → 'archived', original_category saved for restoration.
 */
class UserNote extends Model
{
    use HasFactory;

    protected $table = 'user_notes';

    protected $fillable = [
        'user_id',
        'admin_id',
        'category',
        'original_category',
        'title',
        'content',
        'is_pinned',
    ];

    /** Valid active categories (excludes 'archived') */
    public const CATEGORIES = ['password', 'anydesk', 'notes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Archive this note — saves original category for later restoration.
     * Recovered from old project: UserNotesController::archiveNote()
     */
    public function archive(): void
    {
        if ($this->category === 'archived') {
            return;
        }
        $this->update([
            'original_category' => $this->category,
            'category'          => 'archived',
        ]);
    }

    /**
     * Restore this note to its original category.
     * Recovered from old project: UserNotesController::unarchiveNote()
     */
    public function unarchive(): void
    {
        if ($this->category !== 'archived') {
            return;
        }
        $this->update([
            'category'          => $this->original_category ?: 'notes',
            'original_category' => null,
        ]);
    }
}
