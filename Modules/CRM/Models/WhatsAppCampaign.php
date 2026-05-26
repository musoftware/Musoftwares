<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaign extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $table = 'crm_wa_campaigns';

    protected $fillable = [
        'uuid', 'workspace_id', 'name', 'description', 'type', 'status',
        'template_id', 'audience_id', 'account_id', 'account_rotation',
        'scheduled_at', 'started_at', 'completed_at', 'cancelled_at', 'paused_at',
        'batch_size', 'batch_delay_seconds', 'max_per_minute', 'max_per_hour',
        'total_recipients', 'sent_count', 'delivered_count', 'read_count',
        'failed_count', 'replied_count', 'clicked_count', 'opted_out_count',
        'message_body', 'message_type', 'media_url', 'buttons',
        'trigger_event', 'trigger_conditions',
        'metadata', 'created_by',
    ];

    protected $casts = [
        'account_rotation'   => 'array',
        'buttons'            => 'array',
        'trigger_conditions' => 'array',
        'metadata'           => 'array',
        'scheduled_at'       => 'datetime',
        'started_at'         => 'datetime',
        'completed_at'       => 'datetime',
        'cancelled_at'       => 'datetime',
        'paused_at'          => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $campaign) {
            $campaign->uuid = $campaign->uuid ?: (string) Str::uuid();
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function template()
    {
        return $this->belongsTo(WhatsAppCampaignTemplate::class, 'template_id');
    }

    public function audience()
    {
        return $this->belongsTo(WhatsAppCampaignAudience::class, 'audience_id');
    }

    public function account()
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    public function sequences()
    {
        return $this->hasMany(WhatsAppCampaignSequence::class, 'campaign_id');
    }

    public function deliveries()
    {
        return $this->hasMany(WhatsAppCampaignDelivery::class, 'campaign_id');
    }

    public function events()
    {
        return $this->hasMany(WhatsAppCampaignEvent::class, 'campaign_id')->latest();
    }

    public function analytics()
    {
        return $this->hasMany(WhatsAppCampaignAnalytics::class, 'campaign_id');
    }

    public function abVariants()
    {
        return $this->hasMany(WhatsAppCampaignAbVariant::class, 'campaign_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeDraft($q)     { return $q->where('status', 'draft'); }
    public function scopeScheduled($q) { return $q->where('status', 'scheduled'); }
    public function scopeRunning($q)   { return $q->where('status', 'running'); }
    public function scopePaused($q)    { return $q->where('status', 'paused'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    public function scopeActive($q)    { return $q->whereIn('status', ['running', 'paused', 'scheduled']); }

    // ── Helpers ──────────────────────────────────────────────────

    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isRunning(): bool   { return $this->status === 'running'; }
    public function isPaused(): bool    { return $this->status === 'paused'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function canStart(): bool    { return in_array($this->status, ['draft', 'scheduled']); }
    public function canPause(): bool    { return $this->status === 'running'; }
    public function canResume(): bool   { return $this->status === 'paused'; }
    public function canCancel(): bool   { return in_array($this->status, ['running', 'paused', 'scheduled']); }

    public function getDeliveryRate(): float
    {
        return $this->sent_count > 0 ? round(($this->delivered_count / $this->sent_count) * 100, 2) : 0;
    }

    public function getReadRate(): float
    {
        return $this->delivered_count > 0 ? round(($this->read_count / $this->delivered_count) * 100, 2) : 0;
    }

    public function getReplyRate(): float
    {
        return $this->delivered_count > 0 ? round(($this->replied_count / $this->delivered_count) * 100, 2) : 0;
    }

    public function getProgressPercentage(): float
    {
        return $this->total_recipients > 0
            ? round((($this->sent_count + $this->failed_count) / $this->total_recipients) * 100, 1)
            : 0;
    }

    public function getRotationAccounts(): array
    {
        return $this->account_rotation ?? ($this->account_id ? [$this->account_id] : []);
    }
}
