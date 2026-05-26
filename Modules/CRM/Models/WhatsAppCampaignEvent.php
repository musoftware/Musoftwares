<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignEvent extends Model
{
    use BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_events';

    protected $fillable = [
        'workspace_id', 'campaign_id', 'event',
        'description', 'user_id', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Create a campaign event entry.
     */
    public static function record(WhatsAppCampaign $campaign, string $event, ?string $description = null, ?int $userId = null, ?array $metadata = null): self
    {
        return static::create([
            'workspace_id' => $campaign->workspace_id,
            'campaign_id'  => $campaign->id,
            'event'        => $event,
            'description'  => $description,
            'user_id'      => $userId ?? auth()->id(),
            'metadata'     => $metadata ?? [
                'sent_count'      => $campaign->sent_count,
                'delivered_count' => $campaign->delivered_count,
                'failed_count'    => $campaign->failed_count,
            ],
        ]);
    }
}
