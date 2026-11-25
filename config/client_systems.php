<?php

/**
 * Licensed client systems (see App\Models\ClientSystem).
 */
return [
    // How long a client system keeps working with its last license when it cannot reach us.
    'offline_hours' => (int) env('CLIENT_SYSTEMS_OFFLINE_HOURS', 72),

    // How often the client system should ask for a fresh license (written into the integration prompt).
    'check_every_hours' => 6,

    // Signed requests older or newer than this many seconds are refused (stops replays).
    'signature_window_seconds' => 300,

    // Most sales the client system may report in one request.
    'max_sales_per_request' => 200,

    // Grace days after a bill before the system locks, for new systems.
    'default_grace_days' => 7,
];
