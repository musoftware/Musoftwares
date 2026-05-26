<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppAssignment extends Model
{
    use BelongsToWorkspace;

    protected $table = 'crm_whatsapp_assignments';

    protected $fillable = [
        'workspace_id',
        'conversation_id',
        'assigned_from_id',
        'assigned_to_id',
        'assigned_by_id',
        'assignment_type',
        'reason',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function conversation()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function assignedFrom()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_from_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('assignment_type', $type);
    }
}
