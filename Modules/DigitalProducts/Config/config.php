<?php

return [
    'name' => 'DigitalProducts',
    'route_prefix' => 'library',
    'admin_prefix' => 'admin/library',
    'download_token_lifetime_hours' => 48,
    'allowed_extensions' => ['pdf', 'epub', 'zip'],
    'max_file_size_mb' => 150,
    'currency' => 'USD',
    'storage_disk' => 'local',
    'covers_disk' => 'public',
];
