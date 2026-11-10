<?php

return [
    // A cheap model talks with the customer. The expensive build model is set on the worker on the owner's PC.
    'chat_model' => env('BUILDER_CHAT_MODEL', 'claude-haiku-5-5'),
    'chat_effort' => env('BUILDER_CHAT_EFFORT', 'low'),
    // Server-side refusal fallback. Haiku does not support it, so turn it on only for Opus or Sonnet chat models.
    'chat_fallback' => (bool) env('BUILDER_CHAT_FALLBACK', false),
    // what the chat model costs, to show the owner what each chat cost (the same prices the worker uses)
    'chat_prices' => [
        'input_usd_per_mtok' => (float) env('BUILDER_CHAT_INPUT_USD_PER_MTOK', 0.1),
        'output_usd_per_mtok' => (float) env('BUILDER_CHAT_OUTPUT_USD_PER_MTOK', 0.5),
    ],

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
        'page' => (float) env('BUILDER_MIN_PAGE', 15),
        'website' => (float) env('BUILDER_MIN_WEBSITE', 40),
        'web_app' => (float) env('BUILDER_MIN_WEB_APP', 60),
        'system' => (float) env('BUILDER_MIN_SYSTEM', 150),
        'extension' => (float) env('BUILDER_MIN_EXTENSION', 25),
        'desktop' => (float) env('BUILDER_MIN_DESKTOP', 80),
        'app_simple' => (float) env('BUILDER_MIN_APP_SIMPLE', 60),
        'app_full' => (float) env('BUILDER_MIN_APP_FULL', 200),
        'app_fancy' => (float) env('BUILDER_MIN_APP_FANCY', 250),
        'other' => (float) env('BUILDER_MIN_OTHER', 20),
    ],

    // The build model is picked per job: complex or large jobs get the strong model, the rest the cheaper one.
    'build_models' => [
        'standard' => ['model' => env('BUILDER_STANDARD_MODEL', 'claude-sonnet-5-5'), 'output_usd_per_mtok' => (float) env('BUILDER_STANDARD_USD_PER_MTOK', 10)],
        'strong' => ['model' => env('BUILDER_STRONG_MODEL', 'claude-opus-5-5'), 'output_usd_per_mtok' => (float) env('BUILDER_STRONG_USD_PER_MTOK', 20)],
    ],
    'strong_model_from_tokens' => (int) env('BUILDER_STRONG_FROM_TOKENS', 60000),

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

    // Fixing a bug in a delivered program is free; a new change is paid. These limits stop a change passing as a fix.
    'free_fixes' => [
        'per_project' => (int) env('BUILDER_FREE_FIXES', 3),
        'max_tokens' => (int) env('BUILDER_FREE_FIX_MAX_TOKENS', 30000),
        'days' => (int) env('BUILDER_FREE_FIX_DAYS', 30),
    ],

    'limits' => [
        'message_chars' => 3000,
        'files_per_message' => 5,
        // 5 MB is the most Claude accepts for one image
        'file_kb' => 5120,
        'messages_per_project' => 60,
        'projects_per_ip_per_day' => 6,
        // PHP programs come with vendor/ (thousands of files); the server's upload_max_filesize must allow archive_kb
        'archive_kb' => (int) env('BUILDER_ARCHIVE_KB', 204800),
        'archive_files' => (int) env('BUILDER_ARCHIVE_FILES', 30000),
        // screenshots the build sends to the chat when a big part works
        'snapshots_per_build' => 20,
        'snapshot_kb' => 4096,
        'lease_minutes' => 120,
        'max_attempts' => 3,
    ],
];
