<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\CRM\app\Traits\BelongsToWorkspace;
use Modules\CRM\Infrastructure\Authorization\HasEnterpriseVisibility;

class WhatsAppConversation extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace, HasEnterpriseVisibility;

    protected $table = 'crm_whatsapp_conversations';

    protected $fillable = [
        'uuid',
        'workspace_id',
        'branch_id',
        'account_id',
        'contact_phone',
        'contact_name',
        'contact_avatar',
        'lead_id',
        'type',
        'status',
        'priority',
        'assigned_agent_id',
        'assigned_department',
        'is_pinned',
        'is_starred',
        'unread_count',
        'last_message_at',
        'last_message_preview',
        'first_response_at',
        'resolved_at',
        'sla_policy_id',
        'sla_due_at',
        'sla_breached',
        'metadata',
    ];

    protected $casts = [
        'metadata'         => 'array',
        'is_pinned'        => 'boolean',
        'is_starred'       => 'boolean',
        'sla_breached'     => 'boolean',
        'unread_count'     => 'integer',
        'last_message_at'  => 'datetime',
        'first_response_at'=> 'datetime',
        'resolved_at'      => 'datetime',
        'sla_due_at'       => 'datetime',
    ];

    // ── Boot ─────────────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function ($conversation) {
            if (!$conversation->uuid) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function account()
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_agent_id');
    }

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(WhatsAppMessage::class, 'conversation_id')->latestOfMany();
    }

    public function participants()
    {
        return $this->hasMany(WhatsAppParticipant::class, 'conversation_id');
    }

    public function assignments()
    {
        return $this->hasMany(WhatsAppAssignment::class, 'conversation_id');
    }

    public function labels()
    {
        return $this->belongsToMany(WhatsAppLabel::class, 'crm_whatsapp_conversation_labels', 'conversation_id', 'label_id')
                    ->withTimestamps();
    }

    public function slaPolicy()
    {
        return $this->belongsTo(WhatsAppSlaPolicy::class, 'sla_policy_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_agent_id');
    }

    public function scopeAssignedTo($query, int $agentId)
    {
        return $query->where('assigned_agent_id', $agentId);
    }

    public function scopeSlaDue($query)
    {
        return $query->where('sla_breached', false)
                     ->whereNotNull('sla_due_at')
                     ->where('sla_due_at', '<=', now());
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isAssigned(): bool
    {
        return $this->assigned_agent_id !== null;
    }

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }

    /**
     * Get the branch this conversation belongs to
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
