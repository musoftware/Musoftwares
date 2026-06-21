<?php

namespace Modules\Booking\app\Features\OnlinePage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicPageView extends Model
{
    use SoftDeletes;

    protected $table = 'booking_public_page_views';
    public $timestamps = false; // using viewed_at manually

    protected $fillable = [
        'tenant_id',
        'page_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];
}
