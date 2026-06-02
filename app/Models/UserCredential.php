<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredential extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected $fillable = [
        'user_id',
        'admin_id',
        'category',
        'original_category',
        'title',
        'content',
        'note',
        'is_pinned',
    ];

    /** Valid active categories (excludes 'archived') */
    public const CATEGORIES = ['password', 'anydesk', 'notes'];

    /**
     * Map 'content' attribute to 'note' database column.
     */
    public function getContentAttribute()
    {
        return $this->attributes['note'] ?? '';
    }

    public function setContentAttribute($value)
    {
        $this->attributes['note'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Archive this credential/note.
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
     * Restore this credential/note from archives.
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
