<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhiteLabelDomain extends Model
{
    use SoftDeletes;

    protected $table = 'booking_white_label_domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'txt_record',
        'ssl_status',
    ];
}
