<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignDelivery extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_deliveries';

    protected $fillable = [
        'workspace_id', 'campaign_id', 'sequence_step_id', 'account_id',
        'phone', 'contact_name', 'contactable_type', 'contactable_id',
        'rendered_body', 'message_type', 'media_url',
        'status', 'whatsapp_message_id', 'failed_reason',
        'retry_count', 'max_retries',
        'has_replied', 'has_clicked', 'has_opted_out',
        'queued_at', 'sent_at', 'delivered_at', 'read_at',
        'replied_at', 'clicked_at',
        'metadata',
    ];

    protected $casts = [
        'metadata'      => 'array',
        'has_replied'   => 'boolean',
        'has_clicked'   => 'boolean',
        'has_opted_out' => 'boolean',
        'queued_at'     => 'datetime',
        'sent_at'       => 'datetime',
        'delivered_at'  => 'datetime',
        'read_at'       => 'datetime',
        'replied_at'    => 'datetime',
        'clicked_at'    => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'campaign_id');
    }

    public function sequenceStep()
    {
        return $this->belongsTo(WhatsAppCampaignSequenceStep::class, 'sequence_step_id');
    }

    public function account()
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    public function contactable()
    {
        return $this->morphTo();
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending($q)   { return $q->where('status', 'pending'); }
    public function scopeQueued($q)    { return $q->where('status', 'queued'); }
    public function scopeSent($q)      { return $q->where('status', 'sent'); }
    public function scopeDelivered($q) { return $q->where('status', 'delivered'); }
    public function scopeFailed($q)    { return $q->where('status', 'failed'); }
    public function scopeRetryable($q) { return $q->where('status', 'failed')->whereColumn('retry_count', '<', 'max_retries'); }

    // ── Helpers ──────────────────────────────────────────────────

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    public function markAsSent(string $whatsappMessageId): void
    {
        $this->update([
            'status'              => 'sent',
            'whatsapp_message_id' => $whatsappMessageId,
            'sent_at'             => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status'        => 'failed',
            'failed_reason' => $reason,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update(['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function markAsRead(): void
    {
        $this->update(['status' => 'read', 'read_at' => now()]);
    }
}
