<?php

namespace Modules\SmsPaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;

class SmsPaymentGatewaySetting extends Model
{
    protected $table = 'sms_payment_gateway_settings';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'wallet_phone_number',
        'is_instapay_enabled',
        'is_vodafone_cash_enabled',
    ];

    protected $casts = [
        'is_instapay_enabled' => 'boolean',
        'is_vodafone_cash_enabled' => 'boolean',
    ];
}
