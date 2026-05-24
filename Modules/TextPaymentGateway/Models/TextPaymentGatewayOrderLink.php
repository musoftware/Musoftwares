<?php

namespace Modules\TextPaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TextPaymentGatewayOrderLink extends Model
{
    use HasFactory;

    protected $table = 'auto_sms_order_links';
protected $fillable = [
        'user_id',
        'order_id',
        'phone_number',
        'status',
    ];

    /**
     * Get the user that owns this order link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
