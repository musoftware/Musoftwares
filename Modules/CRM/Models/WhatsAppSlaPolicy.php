<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppSlaPolicy extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_whatsapp_sla_policies';

    protected $fillable = [
        'workspace_id',
        'name',
        'first_response_time',
        'resolution_time',
        'priority',
        'business_hours_only',
        'notify_on_breach',
        'escalation_user_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'first_response_time' => 'integer',
        'resolution_time'     => 'integer',
        'business_hours_only' => 'boolean',
        'notify_on_breach'    => 'boolean',
        'is_default'          => 'boolean',
        'is_active'           => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function conversations()
    {
        return $this->hasMany(WhatsAppConversation::class, 'sla_policy_id');
    }

    public function escalationUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'escalation_user_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Calculate the SLA due datetime from a given start time.
     */
    public function calculateDueAt(\Carbon\Carbon $startedAt): \Carbon\Carbon
    {
        return $startedAt->copy()->addMinutes($this->first_response_time);
    }

    /**
     * Calculate the resolution due datetime from a given start time.
     */
    public function calculateResolutionDueAt(\Carbon\Carbon $startedAt): \Carbon\Carbon
    {
        return $startedAt->copy()->addMinutes($this->resolution_time);
    }
}
