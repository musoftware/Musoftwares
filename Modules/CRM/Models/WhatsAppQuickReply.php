<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppQuickReply extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_whatsapp_quick_replies';

    protected $fillable = [
        'workspace_id',
        'shortcut',
        'title',
        'body',
        'media_url',
        'category',
        'is_global',
        'created_by',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('shortcut', 'like', "%{$term}%")
              ->orWhere('title', 'like', "%{$term}%")
              ->orWhere('body', 'like', "%{$term}%");
        });
    }
}
