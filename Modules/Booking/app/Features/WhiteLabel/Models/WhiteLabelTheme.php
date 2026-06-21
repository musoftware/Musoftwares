<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhiteLabelTheme extends Model
{
    use SoftDeletes;

    protected $table = 'booking_white_label_themes';

    protected $fillable = [
        'tenant_id',
        'name',
        'is_default',
        'settings_json',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'settings_json' => 'array',
    ];
}
