<?php

namespace Modules\Booking\app\Features\WhiteLabel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelSetting extends Model
{
    protected $table = 'booking_white_label_settings';

    protected $fillable = [
        'tenant_id',
        'primary_color',
        'secondary_color',
        'font_family',
        'custom_css',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
