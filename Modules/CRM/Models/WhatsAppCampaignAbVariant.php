<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignAbVariant extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_ab_variants';

    protected $fillable = [
        'workspace_id', 'campaign_id', 'variant_name',
        'template_id', 'message_body', 'audience_percentage',
        'sent_count', 'delivered_count', 'read_count', 'replied_count',
        'is_winner', 'metadata',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
        'metadata'  => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'campaign_id');
    }

    public function template()
    {
        return $this->belongsTo(WhatsAppCampaignTemplate::class, 'template_id');
    }

    public function getReadRate(): float
    {
        return $this->delivered_count > 0
            ? round(($this->read_count / $this->delivered_count) * 100, 2) : 0;
    }
}
