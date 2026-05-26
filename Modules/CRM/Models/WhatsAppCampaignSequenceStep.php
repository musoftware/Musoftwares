<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignSequenceStep extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_sequence_steps';

    protected $fillable = [
        'workspace_id', 'sequence_id', 'step_order', 'action_type',
        'template_id', 'message_body', 'message_type',
        'delay_minutes', 'delay_unit',
        'conditions', 'on_true_step', 'on_false_step',
        'skip_if_replied', 'stop_on_reply',
        'metadata',
    ];

    protected $casts = [
        'conditions'       => 'array',
        'metadata'         => 'array',
        'skip_if_replied'  => 'boolean',
        'stop_on_reply'    => 'boolean',
    ];

    public function sequence()
    {
        return $this->belongsTo(WhatsAppCampaignSequence::class, 'sequence_id');
    }

    public function template()
    {
        return $this->belongsTo(WhatsAppCampaignTemplate::class, 'template_id');
    }

    public function isSendMessage(): bool { return $this->action_type === 'send_message'; }
    public function isWait(): bool        { return $this->action_type === 'wait'; }
    public function isCondition(): bool   { return $this->action_type === 'condition'; }
    public function isExit(): bool        { return $this->action_type === 'exit'; }

    /**
     * Get the total delay in minutes regardless of unit.
     */
    public function getDelayInMinutes(): int
    {
        return match ($this->delay_unit) {
            'hours' => $this->delay_minutes * 60,
            'days'  => $this->delay_minutes * 1440,
            default => $this->delay_minutes,
        };
    }
}
