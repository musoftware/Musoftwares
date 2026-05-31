<?php

return [
    'name' => 'SmsPaymentGateway',

    /*
    |--------------------------------------------------------------------------
    | Android App Download URL
    |--------------------------------------------------------------------------
    |
    | The public URL where users can download the Payment Gateway Android app.
    | This can be a direct APK download link or a Google Play Store link.
    |
    */
    'android_app_url' => env('SMS_GATEWAY_ANDROID_APP_URL', '/downloads/sms-payment-gateway.apk'),


];
