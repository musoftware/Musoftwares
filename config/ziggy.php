<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziggy Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Exclude admin, debug, and internal routes from the public/frontend
    | Ziggy route manifest to prevent HTML bloat and security leaks.
    |
    */
    'except' => [
        'admin.*',
        'horizon.*',
        'telescope.*',
        'sanctum.*',
        'debugbar.*',
        'ignition.*',
        '_debugbar.*',
    ],
];
