<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppAutomationRule extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $table = 'crm_whatsapp_automation_rules';

    protected $fillable = [
        'workspace_id',
        'name',
        'type',
        'trigger_event',
        'conditions',
        'actions',
        'is_active',
        'priority',
        'schedule',
        'last_triggered_at',
        'trigger_count',
    ];

    protected $casts = [
        'conditions'        => 'array',
        'actions'           => 'array',
        'schedule'          => 'array',
        'is_active'         => 'boolean',
        'priority'          => 'integer',
        'trigger_count'     => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTrigger($query, string $triggerEvent)
    {
        return $query->where('trigger_event', $triggerEvent);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function incrementTriggerCount(): void
    {
        $this->increment('trigger_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Check if this rule is within business hours (if schedule is defined).
     */
    public function isWithinSchedule(): bool
    {
        if (!$this->schedule) {
            return true; // No schedule restriction
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l'));

        $todaySchedule = $this->schedule[$dayOfWeek] ?? null;
        if (!$todaySchedule || !($todaySchedule['enabled'] ?? false)) {
            return false;
        }

        $start = $now->copy()->setTimeFromTimeString($todaySchedule['start'] ?? '00:00');
        $end = $now->copy()->setTimeFromTimeString($todaySchedule['end'] ?? '23:59');

        return $now->between($start, $end);
    }
}
