<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignSequence extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_sequences';

    protected $fillable = [
        'workspace_id', 'campaign_id', 'name', 'description',
        'is_active', 'total_steps', 'exit_conditions', 'metadata',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'exit_conditions' => 'array',
        'metadata'        => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'campaign_id');
    }

    public function steps()
    {
        return $this->hasMany(WhatsAppCampaignSequenceStep::class, 'sequence_id')->orderBy('step_order');
    }

    public function getFirstStep(): ?WhatsAppCampaignSequenceStep
    {
        return $this->steps()->orderBy('step_order')->first();
    }

    public function getNextStep(int $currentOrder): ?WhatsAppCampaignSequenceStep
    {
        return $this->steps()->where('step_order', '>', $currentOrder)->orderBy('step_order')->first();
    }
}
