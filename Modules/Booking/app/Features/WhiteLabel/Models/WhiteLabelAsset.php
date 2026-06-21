<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhiteLabelAsset extends Model
{
    use SoftDeletes;

    protected $table = 'booking_white_label_assets';

    protected $fillable = [
        'tenant_id',
        'type',
        'path',
        'disk',
        'url',
    ];
}
