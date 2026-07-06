<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Notes Configuration
    |--------------------------------------------------------------------------
    |
    | Operational settings for the admin Secure Notes feature
    | (resources/js/Pages/Admin/Users/Notes.jsx).
    |
    */

    // Auto-expire password notes after N days (0 = never).
    'default_password_ttl_days' => (int) env('USER_NOTES_PWD_TTL_DAYS', 90),

    // Auto-lock client-side encryption session after this many minutes of inactivity (0 = never).
    'idle_lock_minutes' => (int) env('USER_NOTES_IDLE_LOCK_MINUTES', 15),

    // Auto-clear clipboard after this many seconds (client-side; UI hint only).
    'clipboard_autoclear_seconds' => (int) env('USER_NOTES_CLIPBOARD_AUTOCLEAR', 30),

    // Server-side content cipher prefix expected (SimpleCrypto compatibility).
    'cipher_prefix' => 'sc1:',

    // Pagination defaults for the notes index.
    'pagination' => [
        'per_page' => (int) env('USER_NOTES_PER_PAGE', 24),
        'max_per_page' => 50,
    ],
];
