<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationCampaignView extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_campaign_id',
        'user_id',
        'type',
    ];

    public function campaign()
    {
        return $this->belongsTo(NotificationCampaign::class, 'notification_campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
