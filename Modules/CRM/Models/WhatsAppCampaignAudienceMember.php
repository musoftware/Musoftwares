<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignAudienceMember extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_audience_members';

    protected $fillable = [
        'workspace_id', 'audience_id', 'phone', 'name', 'email',
        'contactable_type', 'contactable_id',
        'merge_data', 'is_opted_out', 'is_suppressed', 'suppression_reason',
    ];

    protected $casts = [
        'merge_data'    => 'array',
        'is_opted_out'  => 'boolean',
        'is_suppressed' => 'boolean',
    ];

    public function audience()
    {
        return $this->belongsTo(WhatsAppCampaignAudience::class, 'audience_id');
    }

    public function contactable()
    {
        return $this->morphTo();
    }

    public function isEligible(): bool
    {
        return !$this->is_opted_out && !$this->is_suppressed;
    }
}
