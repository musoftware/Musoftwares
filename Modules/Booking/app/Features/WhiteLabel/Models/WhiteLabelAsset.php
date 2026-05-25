<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteLabelAsset extends Model
{
    protected $table = 'booking_white_label_assets';

    protected $fillable = [
        'tenant_id',
        'type',
        'path',
        'disk',
        'url',
    ];
}
