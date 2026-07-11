<?php

return [
    'host'         => env('IMAP_HOST', 'imap.hostinger.com'),
    'port'         => (int) env('IMAP_PORT', 993),
    'encryption'   => env('IMAP_ENCRYPTION', 'ssl'),
    'validate_cert' => env('IMAP_VALIDATE_CERT', true),
    'username'     => env('IMAP_USERNAME', env('MAIL_USERNAME', '')),
    'password'     => env('IMAP_PASSWORD', env('MAIL_PASSWORD', '')),
    'folder'       => env('IMAP_FOLDER', 'INBOX'),
    'lookback_days' => (int) env('IMAP_LOOKBACK_DAYS', 14),

    'inbox'        => env('GUEST_TICKET_INBOX', env('MAIL_FROM_ADDRESS', 'admin@musoftwares.com')),

    'domain'       => env('IMAP_DOMAIN', 'musoftwares.com'),

    'lock_key'     => 'imap-pull',
    'lock_seconds' => 110,

    'max_attachments_mb' => 10,
    'connection_timeout' => 15,
];
