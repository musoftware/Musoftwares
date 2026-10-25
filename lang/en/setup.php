<?php

// Texts of the admin Setup page (App\Services\Admin\SetupChecklist).
return [
    'title' => 'Setup',
    'intro' => 'Everything that still needs an action from you. Items with a check box are done outside the site, so tick them yourself when you finish.',
    'all_done' => 'All done. Nothing needs your action now.',
    'progress' => ':done of :total done',
    'open' => 'Open',
    'mark_done' => 'Mark as done',
    'mark_not_done' => 'Mark as not done',

    'group_site' => 'Site settings',
    'group_seo' => 'Google and search engines',
    'group_queue' => 'Waiting for you',

    'migrations' => 'Run the pending database updates',
    'migrations_note' => ':count update(s) are waiting. Take a backup, then run them.',
    'analytics' => 'Add Google Analytics or Google Tag Manager',
    'analytics_note' => 'Without it you cannot see visitors or which pages bring customers.',
    'payments' => 'Add the real Kashier payment keys',
    'payments_note' => 'Set KASHIER_MERCHANT_ID and KASHIER_SECRET in the .env file on the server. The site uses test values now.',
    'mail' => 'Set up sending email',
    'mail_note' => 'MAIL_MAILER in .env is ":mailer", so no email leaves the site. Use smtp or another real mailer.',
    'product_screenshots' => 'Add screenshots to every program in the store',
    'product_screenshots_note' => 'No screenshots: :names',

    'search_console_sitemap' => 'Send the sitemap to Google Search Console',
    'search_console_sitemap_note' => 'Search Console > Sitemaps > add :url. Do it again after big changes to the site.',
    'search_console_reindex' => 'Ask Google to read the main pages again',
    'search_console_reindex_note' => 'Search Console > URL inspection > Request indexing, for the home page and /services, in English and with ?lang=ar.',
    'bing_webmaster' => 'Add the site to Bing Webmaster Tools',
    'bing_webmaster_note' => 'Import it from Search Console in one click, then send the same sitemap. Bing also feeds ChatGPT search.',
    'google_business_profile' => 'Create a Google Business Profile',
    'google_business_profile_note' => 'Add the Suez address, phone, working hours and a link to the site, so you show on Google Maps.',

    'open_tickets' => 'Answer open support tickets',
    'open_tickets_note' => ':count ticket(s) are waiting.',
    'pending_services' => 'Review marketplace services waiting for approval',
    'pending_services_note' => ':count service(s) are waiting.',
    'withdrawals' => 'Review withdrawal requests',
    'withdrawals_note' => ':count request(s) are waiting.',
];
