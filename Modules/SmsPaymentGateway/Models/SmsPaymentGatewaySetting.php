<?php

namespace Modules\SmsPaymentGateway\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class SmsPaymentGatewaySetting extends Model
{
    use SoftDeletes;

    protected $table = 'sms_payment_gateway_settings';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'wallet_phone_number',
        'instapay_phone_number',
        'vodafone_cash_phone_number',
        'is_instapay_enabled',
        'is_vodafone_cash_enabled',
        'whitelist_senders',
        'macrodroid_token',
        'custom_logos',
        'brand_name',
        'hide_method_name',
        'checkout_language',
    ];

    protected $casts = [
        'is_instapay_enabled' => 'boolean',
        'is_vodafone_cash_enabled' => 'boolean',
        'hide_method_name' => 'boolean',
        'whitelist_senders' => 'array',
        'custom_logos' => 'array',
    ];
}
