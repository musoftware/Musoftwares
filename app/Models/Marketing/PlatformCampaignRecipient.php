<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformCampaignRecipient extends Model
{
    use HasFactory;

    protected $table = 'platform_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'recipient_id',
        'recipient_type',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(PlatformCampaign::class, 'campaign_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
