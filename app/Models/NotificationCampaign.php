<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'target_url',
        'sent_count',
        'clicks_count',
        'views_count',
        'status',
        'audience_type',
    ];

    public function views()
    {
        return $this->hasMany(NotificationCampaignView::class, 'notification_campaign_id');
    }
}
