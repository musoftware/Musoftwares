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

    /*
    |--------------------------------------------------------------------------
    | Default Allowed Senders
    |--------------------------------------------------------------------------
    |
    | Global list of SMS sender names that the app will listen to by default
    | for all users. Users can still add their own custom senders via the Web Panel.
    |
    */
    'allowed_senders' => [
        'Vodafone',
        'VF-Cash',
        'Orange',
        'Orange Cash',
        'Etisalat',
        'WE Pay',
        'Instapay',
        'Fawry',
        'BMWallet',
        'NBEWallet',
        'CIBWallet',
        'QNBWallet'
    ],
];
