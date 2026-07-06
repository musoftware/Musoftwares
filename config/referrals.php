<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Referral / Affiliate Commission Settings
    |--------------------------------------------------------------------------
    |
    | boost_percent — Commission percent applied to a referred user's first
    |                 month of payments (after they make their first paid
    |                 invoice). Default 10% as per the
    |                 2026_03_02_add_first_referral_payment_at migration.
    |
    | boost_days    — How many days after `first_referral_payment_at` the
    |                 boosted commission window lasts. Default 30.
    |
    | view_throttle_per_min — IP rate-limit applied to /r/{ref} to deter
    |                          view inflation. Default 30.
    */

    'boost_percent' => (float) env('REFERRAL_BOOST_PERCENT', 10),
    'boost_days'    => (int) env('REFERRAL_BOOST_DAYS', 30),

    'view_throttle_per_min' => (int) env('REFERRAL_VIEW_THROTTLE', 30),

    /*
    |--------------------------------------------------------------------------
    | Mass-registration guard for referrer-driven user creation.
    |--------------------------------------------------------------------------
    */
    'store_user_max_per_minute' => (int) env('REFERRAL_STORE_USER_THROTTLE', 5),

];