<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'kashier' => [
        'merchant_id' => env('KASHIER_MERCHANT_ID', 'MID-12345'),
        'secret_key' => env('KASHIER_SECRET', env('KASHIER_SECRET_KEY', 'secret')),
        'mode' => env('KASHIER_MODE', 'live'),
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_calendar' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CALENDAR_REDIRECT_URI', '/admin/google-calendar/callback'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'erp' => [
        'url' => env('ERP_URL', 'http://localhost:8001'),
    ],

    'crm' => [
        'url' => env('CRM_URL', 'http://localhost:8002'),
    ],

    'affsys' => [
        'url' => env('AFFSYS_URL', 'http://localhost:8003'),
    ],

    'bookingsys' => [
        'url' => env('BOOKINGSYS_URL', 'http://localhost:8004'),
    ],

    'freelancesys' => [
        'url' => env('FREELANCESYS_URL', 'http://localhost:8005'),
    ],

    'goldsaversys' => [
        'url' => env('GOLDSAVERSYS_URL', 'http://localhost:8006'),
    ],

];
