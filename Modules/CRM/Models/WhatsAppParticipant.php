<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppParticipant extends Model
{
    use BelongsToWorkspace;

    protected $table = 'crm_whatsapp_participants';

    protected $fillable = [
        'workspace_id',
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function conversation()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->left_at === null;
    }
}
