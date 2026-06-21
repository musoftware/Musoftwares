<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhiteLabelTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'booking_white_label_templates';

    protected $fillable = [
        'tenant_id',
        'type',
        'body',
        'subject',
    ];
}
