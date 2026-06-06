<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'target_url',
        'sent_count',
        'clicks_count',
        'views_count',
        'status',
    ];
}
