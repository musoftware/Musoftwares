<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppLabel extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_whatsapp_labels';

    protected $fillable = [
        'workspace_id',
        'name',
        'color',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function conversations()
    {
        return $this->belongsToMany(WhatsAppConversation::class, 'crm_whatsapp_conversation_labels', 'label_id', 'conversation_id')
                    ->withTimestamps();
    }
}
