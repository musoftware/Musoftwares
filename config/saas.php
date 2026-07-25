<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System to Module Map (For SSO and Sync)
    |--------------------------------------------------------------------------
    */
    'system_to_module' => [
        'goldsaversys' => 'gold',
        'affsys'       => 'affiliate',
        'bookingsys'   => 'booking',
        'toolsys'      => 'tool',
        'investorsys'  => 'investor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Module Pricing (EGP/Year)
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'erp' => 10000,
        'crm' => 5000,
        'sms-payment-gateway' => 1000,
        'gold-saver' => 5000,
        'booking' => 3000,
        'tool' => 1000,
        'affiliate-pos' => 4000,
        'investor' => 5000,
        'whatsapp-sender' => 3000,
    ],

    /*
    |--------------------------------------------------------------------------
    | A La Carte Add-ons (Capabilities)
    |--------------------------------------------------------------------------
    */
    'addons' => [
        // ── BOOKING ──
        'booking-wa-reminders' => ['price' => 500, 'name' => 'WhatsApp Reminders', 'desc' => 'Reminder, confirmation, & reschedule links', 'icon' => 'MessageSquare', 'parent' => 'booking'],
        'booking-online-page' => ['price' => 500, 'name' => 'Online Booking Page', 'desc' => 'Public branded booking page', 'icon' => 'Globe', 'parent' => 'booking'],
        'booking-custom-domain' => ['price' => 500, 'name' => 'Custom Domain', 'desc' => 'Use book.yourclinic.com', 'icon' => 'Link', 'parent' => 'booking'],
        'booking-multi-branch' => ['price' => 500, 'name' => 'Multi Branch', 'desc' => 'Branch calendars and staff', 'icon' => 'Building2', 'parent' => 'booking'],
        'booking-team-members' => ['price' => 500, 'name' => 'Extra Team Members', 'desc' => 'Add more employees to your schedule', 'icon' => 'Users', 'parent' => 'booking'],
        'booking-gcal-sync' => ['price' => 500, 'name' => 'Google Calendar Sync', 'desc' => 'Two-way sync & auto updates', 'icon' => 'Calendar', 'parent' => 'booking'],
        'booking-group-sessions' => ['price' => 500, 'name' => 'Group Sessions', 'desc' => 'For classes, gyms & courses', 'icon' => 'Users', 'parent' => 'booking'],
        'booking-recurring' => ['price' => 500, 'name' => 'Recurring Appointments', 'desc' => 'Automate regular visits', 'icon' => 'Repeat', 'parent' => 'booking'],
        'booking-sms' => ['price' => 500, 'name' => 'SMS Notifications', 'desc' => 'Text alerts for appointments', 'icon' => 'MessageSquare', 'parent' => 'booking'],
        'booking-widget' => ['price' => 500, 'name' => 'Booking Widget', 'desc' => 'Embed booking directly on your site', 'icon' => 'Code', 'parent' => 'booking'],
        'booking-analytics' => ['price' => 500, 'name' => 'Analytics & Reports', 'desc' => 'Utilization and no-show metrics', 'icon' => 'BarChart', 'parent' => 'booking'],
        'booking-queue' => ['price' => 500, 'name' => 'Queue Management', 'desc' => 'Live waiting room control', 'icon' => 'List', 'parent' => 'booking'],
        'booking-wa-confirm' => ['price' => 500, 'name' => 'WhatsApp Confirmations', 'desc' => 'Interactive confirm/cancel buttons', 'icon' => 'MessageSquare', 'parent' => 'booking'],
        'booking-follow-ups' => ['price' => 500, 'name' => 'Automated Follow-Ups', 'desc' => 'Post-appointment surveys', 'icon' => 'MessageSquare', 'parent' => 'booking'],
        'booking-resource-utilization' => ['price' => 500, 'name' => 'Resource Utilization', 'desc' => 'Detailed staff performance metrics', 'icon' => 'BarChart', 'parent' => 'booking'],
        'booking-white-label' => ['price' => 500, 'name' => 'White Label', 'desc' => 'Remove all system branding', 'icon' => 'Star', 'parent' => 'booking', 'limits' => ['max_custom_brands' => 1, 'max_custom_domains' => 1, 'max_white_label_assets' => 5, 'max_custom_templates' => 10]],
        'booking-api' => ['price' => 500, 'name' => 'API Access', 'desc' => 'Full programmatic access', 'icon' => 'Code', 'parent' => 'booking'],
        'booking-advanced-rules' => ['price' => 500, 'name' => 'Advanced Rules', 'desc' => 'Buffers & conditional schedules', 'icon' => 'Settings', 'parent' => 'booking'],
        'booking-priority-support' => ['price' => 500, 'name' => 'Priority Support', 'desc' => 'Skip the line support', 'icon' => 'Headset', 'parent' => 'booking'],
        'booking-smart-slots' => ['price' => 500, 'name' => 'Smart Suggestions', 'desc' => 'Earliest available & best match', 'icon' => 'Zap', 'parent' => 'booking'],

        // ── CRM (Enterprise Operations Bundles) ──
        'crm-sales-staff' => ['price' => 200, 'name' => 'Sales Staff Operations', 'desc' => 'Lead collection, pipelines, and basic telesales', 'icon' => 'Users', 'parent' => 'crm'],
        'crm-sales-management' => ['price' => 300, 'name' => 'Sales Management & Tracking', 'desc' => 'KPIs, SLA monitoring, and branch analytics', 'icon' => 'Activity', 'parent' => 'crm'],
        'crm-call-center' => ['price' => 400, 'name' => 'Enterprise Call Center', 'desc' => 'Dial queues, QA, and advanced telephony', 'icon' => 'Headset', 'parent' => 'crm'],
        'crm-advanced-operations' => ['price' => 500, 'name' => 'Advanced Operations & Workflows', 'desc' => 'Automations, webhooks, and SLA routing engines', 'icon' => 'Settings', 'parent' => 'crm'],
        'crm-advanced-roles' => ['price' => 300, 'name' => 'Advanced Roles', 'desc' => 'Unlock manager and admin team roles', 'icon' => 'Shield', 'parent' => 'crm'],

        // ── ERP Add-ons (disabled for now) ──
        // ── GOLD SAVER ──
        // Add-on catalog removed — Gold Saver now ships with every feature included at the single 500 price.
        
        // ── AFFILIATE + POS ──
        'affiliate-storefront'      => ['price' => 500, 'name' => 'Online Storefront',    'desc' => 'Public branded e-commerce page',       'icon' => 'Globe',           'parent' => 'affiliate-pos'],
        'affiliate-multi-vendor'    => ['price' => 500, 'name' => 'Multi-Vendor Portal',  'desc' => 'Allow vendors to list products',       'icon' => 'Users',           'parent' => 'affiliate-pos'],
        'affiliate-moderators'      => ['price' => 500, 'name' => 'Team Moderators',      'desc' => 'Sub-accounts for affiliates',          'icon' => 'Shield',          'parent' => 'affiliate-pos'],
        'affiliate-advanced-returns'=> ['price' => 500, 'name' => 'Advanced Returns',     'desc' => 'Automated returning & replacing',      'icon' => 'RefreshCcw',      'parent' => 'affiliate-pos'],
        'affiliate-custom-domain'   => ['price' => 500, 'name' => 'Custom Domain',        'desc' => 'Use your own domain for the store',    'icon' => 'Link',            'parent' => 'affiliate-pos'],
        'affiliate-pos-system'      => ['price' => 500, 'name' => 'POS System',           'desc' => 'Cashier, barcode, & receipts',         'icon' => 'MonitorSmartphone', 'parent' => 'affiliate-pos'],
        'affiliate-whatsapp'        => ['price' => 500, 'name' => 'WhatsApp Alerts',      'desc' => 'Order & shipping notifications',       'icon' => 'MessageSquare',   'parent' => 'affiliate-pos'],
        'affiliate-analytics'       => ['price' => 500, 'name' => 'Advanced Analytics',   'desc' => 'Conversion rates & ROI tracking',      'icon' => 'BarChart',        'parent' => 'affiliate-pos'],
        'affiliate-wallet'          => ['price' => 500, 'name' => 'Advanced Wallet',      'desc' => 'Payment requests & withdrawals',       'icon' => 'Wallet',          'parent' => 'affiliate-pos'],
        'affiliate-api'             => ['price' => 500, 'name' => 'API Access',           'desc' => 'Programmatic store access',            'icon' => 'Code',            'parent' => 'affiliate-pos'],
        'affiliate-white-label'     => ['price' => 500, 'name' => 'White Label',          'desc' => 'Remove all system branding',           'icon' => 'Star',            'parent' => 'affiliate-pos'],
        'affiliate-multi-language'  => ['price' => 500, 'name' => 'Multi Language',       'desc' => 'Multiple storefront languages',        'icon' => 'Globe',           'parent' => 'affiliate-pos'],
    ],
];
