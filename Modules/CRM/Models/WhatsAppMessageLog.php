<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppMessageLog extends Model
{
    use BelongsToWorkspace;

    protected $table = 'crm_whatsapp_message_logs';

    protected $fillable = [
        'workspace_id',
        'message_id',
        'conversation_id',
        'user_id',
        'action',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function message()
    {
        return $this->belongsTo(WhatsAppMessage::class, 'message_id');
    }

    public function conversation()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeErrors($query)
    {
        return $query->where('action', 'failed');
    }
}
