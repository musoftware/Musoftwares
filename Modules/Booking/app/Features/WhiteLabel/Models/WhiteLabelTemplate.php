<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteLabelTemplate extends Model
{
    protected $table = 'booking_white_label_templates';

    protected $fillable = [
        'tenant_id',
        'type',
        'body',
        'subject',
    ];
}
