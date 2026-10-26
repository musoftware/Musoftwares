<?php

// Client account and help pages: profile, notifications, support tickets, identity verification.
return [
    'profile' => [
        'title' => 'Profile',
        'hint' => 'Your name, e-mail addresses, password and account.',
        'all_settings' => 'All settings',
        'name' => 'Name',
        'email' => 'E-mail',
        'password' => 'Password',
        'current_password' => 'Current password',
        'new_password' => 'New password',
        'confirm_password' => 'Confirm new password',
    ],

    'notifications' => [
        'title' => 'Notifications',
        'hint' => 'Updates about your invoices, projects and tickets.',
        'mark_all' => 'Mark all as read',
        'mark_read' => 'Mark as read',
        'empty' => 'You are all caught up. No notifications.',
        'new' => 'New notification',
    ],

    'tickets' => [
        'title' => 'Support',
        'hint' => 'Ask us anything. We reply here and by e-mail.',
        'hint_admin' => 'Client tickets. Filter by status to find the ones that need a reply.',
        'new' => 'New ticket',
        'empty' => 'No tickets yet. Open one when you need help.',
        'all' => 'All tickets',
        'status' => 'Status',
        'priority' => 'Priority',
        'all_priorities' => 'All priorities',
        'priority_high' => 'High priority',
        'priority_medium' => 'Medium priority',
        'priority_low' => 'Low priority',
        'updated' => 'Updated :date',
        'opened' => 'Opened :date',
        'ticket' => 'Ticket #:id',
        'back' => 'All tickets',
        'resolve' => 'Mark as resolved',
        'waiting_you_hint' => 'We replied. Answer below so we can continue.',
        'closed_hint' => 'This ticket is closed. Open a new one if you need more help.',
        'quote_title' => 'Price for this request',
        'quote_hint' => 'Our team priced this request.',
        'state' => [
            'open' => 'Open',
            'waiting_you' => 'Waiting for you',
            'waiting_us' => 'Waiting for our reply',
            'needs_reply' => 'Needs a reply',
            'waiting_client' => 'Waiting for the client',
            'closed' => 'Closed',
        ],
    ],

    'kyc' => [
        'title' => 'Identity verification',
        'hint' => 'Upload your documents once. We review them and let you know.',
        'required' => 'Required',
        'optional' => 'Optional',
        'choose_file' => 'Tap to choose a file, or drop it here',
        'submit' => 'Submit for review',
        'submit_hint' => 'Upload the required documents first.',
    ],
];
