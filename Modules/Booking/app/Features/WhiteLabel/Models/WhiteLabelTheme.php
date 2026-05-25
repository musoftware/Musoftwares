<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteLabelTheme extends Model
{
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
