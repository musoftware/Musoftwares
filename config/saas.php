<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Module Pricing (EGP/Year)
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'erp' => 5000,
        'crm' => 5000,
        'sms-payment-gateway' => 1000,
        'gold-saver' => 1000,
        'booking' => 3000,
        'tool' => 1000,
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

        // ── CRM ──
        'crm-wa-inbox' => ['price' => 500, 'name' => 'WhatsApp Inbox', 'desc' => 'Shared multi-agent inbox', 'icon' => 'MessageSquare', 'parent' => 'crm'],
        'crm-wa-campaigns' => ['price' => 500, 'name' => 'WhatsApp Campaigns', 'desc' => 'Bulk marketing campaigns', 'icon' => 'Send', 'parent' => 'crm'],
        'crm-automations' => ['price' => 500, 'name' => 'Automations', 'desc' => 'Trigger-based workflow rules', 'icon' => 'Zap', 'parent' => 'crm'],
        'crm-extra-user' => ['price' => 500, 'name' => 'Additional Users', 'desc' => 'Add more agents to your team', 'icon' => 'Users', 'parent' => 'crm'],
        'crm-advanced-pipelines' => ['price' => 500, 'name' => 'Advanced Pipelines', 'desc' => 'Multiple sales pipelines', 'icon' => 'GitMerge', 'parent' => 'crm'],
        'crm-advanced-analytics' => ['price' => 500, 'name' => 'Advanced Analytics', 'desc' => 'Conversion & velocity metrics', 'icon' => 'BarChart', 'parent' => 'crm'],
        'crm-custom-fields' => ['price' => 500, 'name' => 'Custom Fields', 'desc' => 'Tailor leads to your business', 'icon' => 'Database', 'parent' => 'crm'],
        'crm-api' => ['price' => 500, 'name' => 'API Access', 'desc' => 'Connect your external systems', 'icon' => 'Code', 'parent' => 'crm'],
        'crm-lead-scoring' => ['price' => 500, 'name' => 'Lead Scoring', 'desc' => 'Rule-based lead qualification', 'icon' => 'Star', 'parent' => 'crm'],
        'crm-email-marketing' => ['price' => 500, 'name' => 'Email Marketing', 'desc' => 'Built-in email sequences', 'icon' => 'Mail', 'parent' => 'crm'],
        'crm-funnel-tracking' => ['price' => 500, 'name' => 'Funnel Tracking', 'desc' => 'Drop-off analysis', 'icon' => 'Filter', 'parent' => 'crm'],
        'crm-workflow-builder' => ['price' => 500, 'name' => 'Workflow Builder', 'desc' => 'Visual automation canvas', 'icon' => 'Workflow', 'parent' => 'crm'],
        'crm-webhooks' => ['price' => 500, 'name' => 'Webhooks', 'desc' => 'Real-time event triggers', 'icon' => 'Webhook', 'parent' => 'crm'],
        'crm-import-export' => ['price' => 500, 'name' => 'Advanced Export', 'desc' => 'Deep data extraction', 'icon' => 'Download', 'parent' => 'crm'],
        'crm-activity-intelligence' => ['price' => 500, 'name' => 'Activity Intelligence', 'desc' => 'Identify inactive & cold leads', 'icon' => 'Brain', 'parent' => 'crm'],
        'crm-shared-inbox' => ['price' => 500, 'name' => 'Shared Team Inbox', 'desc' => 'Centralized email/sms inbox', 'icon' => 'Inbox', 'parent' => 'crm'],
        'crm-audit-logs' => ['price' => 500, 'name' => 'Audit Logs', 'desc' => 'Track every user action', 'icon' => 'History', 'parent' => 'crm'],
        'crm-white-label' => ['price' => 500, 'name' => 'White Label', 'desc' => 'Remove CRM branding', 'icon' => 'Star', 'parent' => 'crm'],
        'crm-multi-branch' => ['price' => 500, 'name' => 'Multi Branch', 'desc' => 'Data isolation per branch', 'icon' => 'Building2', 'parent' => 'crm'],
        'crm-advanced-permissions' => ['price' => 500, 'name' => 'Advanced Permissions', 'desc' => 'View only own leads', 'icon' => 'Shield', 'parent' => 'crm'],

        // ── ERP ──
        'erp-multi-branch' => ['price' => 500, 'name' => 'Multi Branch', 'desc' => 'Branch inventory & staff', 'icon' => 'Building2', 'parent' => 'erp'],
        'erp-inventory' => ['price' => 500, 'name' => 'Advanced Inventory', 'desc' => 'Warehouses & stock alerts', 'icon' => 'Package', 'parent' => 'erp'],
        'erp-pos' => ['price' => 500, 'name' => 'POS System', 'desc' => 'Cashier, barcode, receipts', 'icon' => 'MonitorSmartphone', 'parent' => 'erp'],
        'erp-analytics' => ['price' => 500, 'name' => 'Advanced Reports', 'desc' => 'Profit, cash flow, analytics', 'icon' => 'BarChart', 'parent' => 'erp'],
        'erp-extra-employees' => ['price' => 500, 'name' => 'Extra Employees', 'desc' => 'Add more staff to your ERP', 'icon' => 'Users', 'parent' => 'erp'],
        'erp-permissions' => ['price' => 500, 'name' => 'Role & Permissions', 'desc' => 'Advanced access control', 'icon' => 'Shield', 'parent' => 'erp'],
        'erp-accounting' => ['price' => 500, 'name' => 'Accounting Module', 'desc' => 'Invoices, expenses, journal', 'icon' => 'Calculator', 'parent' => 'erp'],
        'erp-payroll' => ['price' => 500, 'name' => 'Payroll System', 'desc' => 'Salaries, attendance, deductions', 'icon' => 'Banknote', 'parent' => 'erp'],
        'erp-whatsapp' => ['price' => 500, 'name' => 'WhatsApp Notifications', 'desc' => 'Order & invoice updates', 'icon' => 'MessageSquare', 'parent' => 'erp'],
        'erp-purchase-orders' => ['price' => 500, 'name' => 'Purchase Orders', 'desc' => 'Manage supplier orders', 'icon' => 'ShoppingCart', 'parent' => 'erp'],
        'erp-supplier-management' => ['price' => 500, 'name' => 'Supplier Management', 'desc' => 'Vendor tracking & portals', 'icon' => 'Truck', 'parent' => 'erp'],
        'erp-warehouse' => ['price' => 500, 'name' => 'Warehouse Management', 'desc' => 'Advanced stock locations', 'icon' => 'Warehouse', 'parent' => 'erp'],
        'erp-api' => ['price' => 500, 'name' => 'API Access', 'desc' => 'Full programmatic access', 'icon' => 'Code', 'parent' => 'erp'],
        'erp-approvals' => ['price' => 500, 'name' => 'Approval Workflows', 'desc' => 'Expense & purchase approvals', 'icon' => 'CheckSquare', 'parent' => 'erp'],
        'erp-exports' => ['price' => 500, 'name' => 'Advanced Exporting', 'desc' => 'Excel, PDF, CSV, Accounting', 'icon' => 'Download', 'parent' => 'erp'],
        'erp-white-label' => ['price' => 500, 'name' => 'White Label', 'desc' => 'Remove all branding', 'icon' => 'Star', 'parent' => 'erp'],
        'erp-audit-logs' => ['price' => 500, 'name' => 'Audit Logs', 'desc' => 'Track every user action', 'icon' => 'History', 'parent' => 'erp'],
        'erp-multi-currency' => ['price' => 500, 'name' => 'Multi Currency', 'desc' => 'Handle foreign transactions', 'icon' => 'Coins', 'parent' => 'erp'],
        'erp-multi-language' => ['price' => 500, 'name' => 'Multi Language', 'desc' => 'Multiple interface languages', 'icon' => 'Globe', 'parent' => 'erp'],
        'erp-tax-engine' => ['price' => 500, 'name' => 'Tax Engine', 'desc' => 'Advanced taxation rules', 'icon' => 'Receipt', 'parent' => 'erp'],
        'erp-document-storage' => ['price' => 500, 'name' => 'Document Storage', 'desc' => 'Upload invoices & contracts', 'icon' => 'Files', 'parent' => 'erp'],
        'erp-ocr' => ['price' => 500, 'name' => 'OCR Invoice Scanning', 'desc' => 'Auto-read supplier invoices', 'icon' => 'ScanLine', 'parent' => 'erp'],
        'erp-attendance' => ['price' => 500, 'name' => 'Attendance System', 'desc' => 'Track employee hours', 'icon' => 'Clock', 'parent' => 'erp'],
        'erp-advanced-notifications' => ['price' => 500, 'name' => 'Advanced Notifications', 'desc' => 'Low stock & overdue alerts', 'icon' => 'Bell', 'parent' => 'erp'],
        'erp-customer-portal' => ['price' => 500, 'name' => 'Customer Portal', 'desc' => 'Client invoice & order view', 'icon' => 'UserCircle', 'parent' => 'erp'],
        'erp-vendor-portal' => ['price' => 500, 'name' => 'Vendor Portal', 'desc' => 'Supplier management dashboard', 'icon' => 'Truck', 'parent' => 'erp'],
        'erp-recurring-billing' => ['price' => 500, 'name' => 'Recurring Billing', 'desc' => 'Monthly subscriptions', 'icon' => 'Repeat', 'parent' => 'erp'],
        'erp-asset-management' => ['price' => 500, 'name' => 'Asset Management', 'desc' => 'Track company assets', 'icon' => 'Laptop', 'parent' => 'erp'],
        'erp-mobile-app' => ['price' => 500, 'name' => 'Mobile App Access', 'desc' => 'Manage ERP from mobile', 'icon' => 'Smartphone', 'parent' => 'erp'],
        'erp-smart-insights' => ['price' => 500, 'name' => 'Smart Insights', 'desc' => 'Best selling & high expenses', 'icon' => 'Lightbulb', 'parent' => 'erp'],
        // ── GOLD SAVER ──
        'gold-live-prices'      => ['price' => 500, 'name' => 'Live Gold Prices',    'desc' => 'Realtime prices & charts',          'icon' => 'TrendingUp',   'parent' => 'gold-saver'],
        'gold-analytics'        => ['price' => 500, 'name' => 'Portfolio Analytics', 'desc' => 'Profit/loss & growth charts',        'icon' => 'PieChart',     'parent' => 'gold-saver'],
        'gold-investment-reports' => ['price' => 500, 'name' => 'Investment Reports','desc' => 'Monthly/yearly returns',             'icon' => 'FileText',     'parent' => 'gold-saver'],
        'gold-historical-charts'=> ['price' => 500, 'name' => 'Historical Charts',   'desc' => 'Advanced historical price data',     'icon' => 'LineChart',    'parent' => 'gold-saver'],
        'gold-buy-sell-analytics' => ['price' => 500, 'name' => 'Buy/Sell Analytics','desc' => 'History & trade performance',        'icon' => 'Activity',     'parent' => 'gold-saver'],
        'gold-smart-insights'   => ['price' => 500, 'name' => 'Smart Insights',      'desc' => 'Trends, opportunities & top prices', 'icon' => 'Lightbulb',   'parent' => 'gold-saver'],
        'gold-price-alerts'     => ['price' => 500, 'name' => 'Price Alerts',        'desc' => 'WhatsApp, SMS & Push alerts',        'icon' => 'Bell',         'parent' => 'gold-saver'],
        'gold-whatsapp-reports' => ['price' => 500, 'name' => 'WhatsApp Reports',    'desc' => 'Weekly portfolio summary via WA',    'icon' => 'MessageSquare','parent' => 'gold-saver'],
        'gold-api'              => ['price' => 500, 'name' => 'API Access',           'desc' => 'Programmatic data access',           'icon' => 'Code',         'parent' => 'gold-saver'],
        'gold-multi-wallets'    => ['price' => 500, 'name' => 'Multi Wallets',        'desc' => 'Separate goals & funds',             'icon' => 'Wallet',       'parent' => 'gold-saver'],
        'gold-goal-tracking'    => ['price' => 500, 'name' => 'Goal Tracking',        'desc' => 'Target-based saving system',         'icon' => 'Target',       'parent' => 'gold-saver'],
        'gold-shop-integration' => ['price' => 500, 'name' => 'Gold Shop Integration','desc' => 'Connect with local vendors',         'icon' => 'Store',        'parent' => 'gold-saver'],
    ],
];
