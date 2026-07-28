<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziggy Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Exclude internal dev/debugger routes from the Ziggy route manifest.
    |
    */
    'except' => [
        'horizon.*',
        'telescope.*',
        'sanctum.*',
        'debugbar.*',
        'ignition.*',
        '_debugbar.*',
    ],
];
