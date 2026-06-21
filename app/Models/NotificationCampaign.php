<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationCampaign extends Model
{
    use SoftDeletes, HasFactory;

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
