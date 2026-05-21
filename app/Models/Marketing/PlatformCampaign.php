<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformCampaign extends Model
{
    use HasFactory;

    protected $table = 'platform_campaigns';

    protected $fillable = [
        'name',
        'type',
        'target_audience',
        'email_subject',
        'email_content',
        'whatsapp_content',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'email_subject' => 'array',
        'email_content' => 'array',
        'whatsapp_content' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function recipients()
    {
        return $this->hasMany(PlatformCampaignRecipient::class, 'campaign_id');
    }
}
