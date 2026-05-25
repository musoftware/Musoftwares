<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteLabelDomain extends Model
{
    protected $table = 'booking_white_label_domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'txt_record',
        'ssl_status',
    ];
}
