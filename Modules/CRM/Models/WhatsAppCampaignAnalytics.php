<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignAnalytics extends Model
{
    use BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_analytics';

    protected $fillable = [
        'workspace_id', 'campaign_id', 'date', 'hour',
        'sent', 'delivered', 'read', 'failed',
        'replied', 'clicked', 'opted_out',
        'delivery_rate', 'read_rate', 'reply_rate', 'click_rate',
        'metadata',
    ];

    protected $casts = [
        'date'     => 'date',
        'metadata' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'campaign_id');
    }
}
