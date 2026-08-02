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

    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/whatsapp-sender/auth/facebook/callback'),
        'test_phone_number_id' => env('META_TEST_PHONE_NUMBER_ID', '114811102562039'),
        'test_waba_id' => env('META_TEST_WABA_ID', '109283748291029'),
        'test_access_token' => env('META_TEST_ACCESS_TOKEN', 'EAAG_META_TEST_SANDBOX_TOKEN_DEMO'),
    ],

    /*
     * Shared HMAC secret for the public serial-device license check-in API.
     * Each client software instance ships with this secret baked in (per-
     * software, ideally), and signs every POST to /api/serial/device with
     * `X-Musoftwares-Signature: sha256=<hex>` over the raw request body.
     *
     * Set SERIAL_DEVICE_API_SECRET in your environment. When the secret is
     * unset, signature verification fails closed (401). This means existing
     * deployments MUST set the env var before upgrading.
     */
    'serial_device' => [
        'api_secret' => env('SERIAL_DEVICE_API_SECRET'),
        // Optional comma-separated CIDR allowlist (e.g. "10.0.0.0/8,192.168.1.0/24").
        // Empty = no IP restriction (relies on signature only).
        'ip_allowlist' => env('SERIAL_DEVICE_IP_ALLOWLIST', ''),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY', env('GEMINI_API_KEY_1')),
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

    'goldsaversys' => [
        'url' => env('GOLDSAVERSYS_URL', 'http://localhost:8006'),
        'shared_secret' => env('GOLDSAVERSYS_SHARED_SECRET', env('MONOLITH_SHARED_SECRET')),
    ],

    'investorsys' => [
        'url' => env('INVESTORSYS_URL', 'http://localhost:8008'),
        'shared_secret' => env('INVESTORSYS_SHARED_SECRET', env('MONOLITH_SHARED_SECRET')),
    ],

    'toolsys' => [
        'url' => env('TOOLSYS_URL', 'http://localhost:8007'),
        'shared_secret' => env('TOOLSYS_SHARED_SECRET', 'local-shared-secret-change-me'),
        'timeout' => env('TOOLSYS_TIMEOUT', 10),
        'retry_times' => env('TOOLSYS_RETRY_TIMES', 2),
        'retry_sleep' => env('TOOLSYS_RETRY_SLEEP', 300),
    ],

    'iphub' => [
        'key' => env('IPHUB_API_KEY'),
    ],

];
