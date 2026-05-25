<?php

return [
    'name' => 'Booking',

    /*
    |--------------------------------------------------------------------------
    | Database Table Prefix
    |--------------------------------------------------------------------------
    |
    | Defines the prefix used for all tables within the Booking module.
    |
    */
    'table_prefix' => 'booking_',

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | These flags control the availability of various advanced booking features
    | within the SaaS platform.
    |
    */
    'features' => [
        'online_booking' => true,
        'whatsapp_reminders' => false,
        'group_sessions' => false,
        'recurring_reservations' => false,
        'multi_resource' => false,
        'multi_branch' => false,
        'calendar_sync' => false,
    ],
];
