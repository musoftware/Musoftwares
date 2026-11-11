<?php

return [
    // A cheap model talks with the customer. The expensive build model is set on the worker on the owner's PC.
    'chat_model' => env('BUILDER_CHAT_MODEL', 'claude-haiku-5-5'),
    'chat_effort' => env('BUILDER_CHAT_EFFORT', 'low'),
    // Server-side refusal fallback. Haiku does not support it, so turn it on only for Opus or Sonnet chat models.
    'chat_fallback' => (bool) env('BUILDER_CHAT_FALLBACK', false),
    // the chat may search the web before answering about something the team has not built before (a game, a
    // new kind of system); Haiku has only the basic search tool. About $0.01 per search.
    // a guest's first messages before any price go to Google's free Gemini tier; Claude answers everyone else and
    // whenever Gemini fails or its free limit is reached. Off while GEMINI_API_KEY is empty.
    'free_chat' => [
        'key' => env('GEMINI_API_KEY', ''),
        'model' => env('BUILDER_FREE_CHAT_MODEL', 'gemini-3.6-flash'),
        'url' => env('BUILDER_FREE_CHAT_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
        'turns' => (int) env('BUILDER_FREE_CHAT_TURNS', 15),
    ],
    // a customer who went quiet before paying: a reminder email after these days, then the discount email
    'follow_up' => [
        'remind_after_days' => (int) env('BUILDER_FOLLOW_UP_DAYS', 2),
        'offer_after_days' => (int) env('BUILDER_FOLLOW_UP_OFFER_DAYS', 7),
    ],
    // after delivery the price follows the real build cost plus this share (more when a referrer also earns);
    // a signed-in customer gets what they paid above it back as site balance
    'settlement' => [
        'enabled' => (bool) env('BUILDER_SETTLEMENT', true),
        'markup' => (float) env('BUILDER_SETTLEMENT_MARKUP', 0.20),
        'referral_markup' => (float) env('BUILDER_SETTLEMENT_REFERRAL_MARKUP', 0.50),
    ],
    // a signed-in customer's first simple page is built free, once per account, with a hard spending cap
    'free_build' => [
        'enabled' => (bool) env('BUILDER_FREE_BUILD', true),
        'types' => ['page'],
        'budget_usd' => (float) env('BUILDER_FREE_BUILD_BUDGET', 1.0),
    ],
    'chat_web_search' => [
        'enabled' => (bool) env('BUILDER_CHAT_WEB_SEARCH', true),
        'tool' => env('BUILDER_CHAT_WEB_SEARCH_TOOL', 'web_search_20250305'),
        'max_uses' => (int) env('BUILDER_CHAT_WEB_SEARCH_MAX', 3),
    ],
    // what the chat model costs, to show the owner what each chat cost (the same prices the worker uses)
    'chat_prices' => [
        'input_usd_per_mtok' => (float) env('BUILDER_CHAT_INPUT_USD_PER_MTOK', 0.1),
        'output_usd_per_mtok' => (float) env('BUILDER_CHAT_OUTPUT_USD_PER_MTOK', 0.5),
    ],

    // the cards of Musoftware's ready programs a new project can start from; empty means Resources/starters.json
    'starters_file' => env('BUILDER_STARTERS_FILE', ''),

    // Shared secret the worker sends as a Bearer token. Empty means the worker API is closed.
    'worker_token' => env('BUILDER_WORKER_TOKEN', ''),

    // Price = build cost in USD x markup, never below the minimum. Build cost comes from Claude's token estimate
    // and the build model's price. A markup of 3 also covers a build that runs past its estimate: the worker can
    // spend up to budget x 1.5 = 3 x the estimate, so even the worst build does not lose money.
    'pricing' => [
        'currency' => 'USD',
        // an agent that writes N tokens of code also thinks, re-reads files and runs tests; this covers that work.
        // It is the starting value: once a kind of project has enough delivered builds, their real cost is used.
        'work_factor' => (float) env('BUILDER_WORK_FACTOR', 4),
        'learning' => [
            'samples' => 10,
            'min_samples' => 5,
            'min_factor' => 1,
            'max_factor' => 20,
        ],
        'markup' => (float) env('BUILDER_MARKUP', 3),
        'minimum' => (float) env('BUILDER_MINIMUM_PRICE', 0),
        // the worker stops spending once real cost passes the estimate times this
        'budget_factor' => (float) env('BUILDER_BUDGET_FACTOR', 2),
        'min_tokens' => 8000,
        'max_tokens' => 200000,
    ],

    // The least a project of each type costs, whatever the token estimate says: the price follows what the work
    // is worth, and the build gets enough budget to finish it properly. USD.
    'project_types' => [
        'page' => (float) env('BUILDER_MIN_PAGE', 1),
        'website' => (float) env('BUILDER_MIN_WEBSITE', 40),
        'web_app' => (float) env('BUILDER_MIN_WEB_APP', 60),
        'system' => (float) env('BUILDER_MIN_SYSTEM', 150),
        'extension' => (float) env('BUILDER_MIN_EXTENSION', 25),
        'desktop' => (float) env('BUILDER_MIN_DESKTOP', 80),
        'app_simple' => (float) env('BUILDER_MIN_APP_SIMPLE', 60),
        'app_full' => (float) env('BUILDER_MIN_APP_FULL', 200),
        'app_fancy' => (float) env('BUILDER_MIN_APP_FANCY', 250),
        // a game is a joint project: design, art, sounds and several rounds of play-testing with the customer
        'game' => (float) env('BUILDER_MIN_GAME', 300),
        'other' => (float) env('BUILDER_MIN_OTHER', 20),
    ],

    // The build model is picked per job: Sonnet builds by default; only a job the chat marks as hard (`complex`) gets
    // Opus. Size alone does not make a job hard: a big but ordinary system is still Sonnet's work.
    'build_models' => [
        'standard' => ['model' => env('BUILDER_STANDARD_MODEL', 'claude-sonnet-5-5'), 'output_usd_per_mtok' => (float) env('BUILDER_STANDARD_USD_PER_MTOK', 10)],
        'strong' => ['model' => env('BUILDER_STRONG_MODEL', 'claude-opus-5-5'), 'output_usd_per_mtok' => (float) env('BUILDER_STRONG_USD_PER_MTOK', 20)],
    ],

    // A feasibility study for bigger projects, written by the strong model once the scope is agreed;
    // what it costs (times the markup) is added to the price.
    'study' => [
        'for_types' => ['system', 'web_app', 'desktop', 'app_full', 'app_fancy'],
        'model' => env('BUILDER_STUDY_MODEL', 'claude-opus-5-5'),
        'effort' => env('BUILDER_STUDY_EFFORT', 'medium'),
        'input_usd_per_mtok' => (float) env('BUILDER_STUDY_INPUT_USD_PER_MTOK', 4),
        'output_usd_per_mtok' => (float) env('BUILDER_STUDY_OUTPUT_USD_PER_MTOK', 20),
    ],

    // The first payment can be part of the price; the files are handed over when the rest is paid.
    'deposit_shares' => [100, 50, 25],

    // The page shows the price as an estimate in a range. The customer pays the price; if the work turns out
    // bigger, the owner can ask for more before the files are handed over, never above the top of the range.
    'price_range' => ['from' => 0.9, 'to' => 1.5],

    // Offered once to a customer who starts to leave after seeing the price. It comes off the programming part
    // only (the designer and the study are real costs), and the build's spending cap goes down with it.
    'leave_discount' => [
        'percent' => (int) env('BUILDER_LEAVE_DISCOUNT_PERCENT', 10),
        // a customer with notifications on who closed the page on a price hears about the discount this much later
        'remind_after_minutes' => (int) env('BUILDER_LEAVE_REMIND_AFTER_MINUTES', 30),
    ],

    // A professional design by the Musoftware designer is a fixed fee on top of the programming (not multiplied).
    'design' => [
        'minimum_fee' => (float) env('BUILDER_DESIGN_MINIMUM_FEE', 200),
    ],

    // A customer from a referral link pays the normal price plus this share (0.30 = 30%). After payment the
    // share is the referrer's earning, moved into their balance after the clearing days.
    'referral' => [
        'share' => (float) env('BUILDER_REFERRAL_SHARE', 0.30),
        'clearing_days' => (int) env('BUILDER_REFERRAL_CLEARING_DAYS', 14),
    ],

    // A fix or an adjustment within the agreed idea is free after delivery; a new idea is paid. These limits keep a
    // new idea from passing as an adjustment.
    'free_fixes' => [
        'per_project' => (int) env('BUILDER_FREE_FIXES', 5),
        'max_tokens' => (int) env('BUILDER_FREE_FIX_MAX_TOKENS', 30000),
        'days' => (int) env('BUILDER_FREE_FIX_DAYS', 30),
    ],

    'limits' => [
        'message_chars' => 3000,
        'files_per_message' => 5,
        // 5 MB is the most Claude accepts for one image
        'file_kb' => 5120,
        // Excel, Word and CSV files are read by the worker, not sent to Claude as they are
        'office_file_kb' => 10240,
        // the full-quality design pictures and PDFs uploaded after payment, for the build only
        'original_file_kb' => 51200,
        'original_files' => 15,
        'originals_wait_minutes' => 30,
        'messages_per_project' => 60,
        // a guest who talks this long without a price is asked to sign in to go on
        'guest_messages_before_sign_in' => (int) env('BUILDER_GUEST_MESSAGES', 15),
        // many customers on mobile data share one address, so this stays generous; admins have no limit
        'projects_per_ip_per_day' => (int) env('BUILDER_CHATS_PER_IP_PER_DAY', 30),
        // PHP programs come with vendor/ (thousands of files); the server's upload_max_filesize must allow archive_kb
        'archive_kb' => (int) env('BUILDER_ARCHIVE_KB', 409600),
        'archive_files' => (int) env('BUILDER_ARCHIVE_FILES', 30000),
        // screenshots the build sends to the chat when a big part works
        'snapshots_per_build' => 20,
        'snapshot_kb' => 4096,
        'lease_minutes' => 120,
        'max_attempts' => 3,
    ],
];
