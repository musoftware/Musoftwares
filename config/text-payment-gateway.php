<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Text Payment Gateway Payment Hub Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AutoSMS Payment Hub service that connects
    | mobile apps to websites for automatic SMS transaction detection.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed SMS Senders
    |--------------------------------------------------------------------------
    |
    | List of SMS sender names that are allowed to trigger transaction
    | detection. Only SMS messages from these senders will be processed
    | as potential payment transactions.
    |
    */
    'allowed_senders' => [
        'e& money',
        'e&',
        'VF-Cash',
        'CIB'
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Code Expiration
    |--------------------------------------------------------------------------
    |
    | Time in minutes that a connection code (QR code) remains valid.
    | Default is 10 minutes.
    |
    */
    'connection_code_expiry_minutes' => 10,
    /*
    |--------------------------------------------------------------------------
    | Minimum Transaction Amount
    |--------------------------------------------------------------------------
    |
    | Minimum amount (in EGP) to be considered a valid transaction.
    | Default is 1 EGP.
    |
    */
    'min_transaction_amount' => 1,

    /*
    |--------------------------------------------------------------------------
    | Maximum Transaction Amount
    |--------------------------------------------------------------------------
    |
    | Maximum amount (in EGP) to be considered a valid transaction.
    | Default is 1,000,000 EGP.
    |
    */
    'max_transaction_amount' => 1000000,

    /*
    |--------------------------------------------------------------------------
    | Enable Gemini AI Extraction
    |--------------------------------------------------------------------------
    |
    | If enabled, the system will use Google Gemini AI to extract transaction
    | information from SMS messages. Falls back to regex patterns if Gemini
    | is unavailable or fails. Requires user to have gemini_api configured.
    |
    */
    'enable_gemini_extraction' => true,

    /*
    |--------------------------------------------------------------------------
    | Spoofing Detection Tolerance
    |--------------------------------------------------------------------------
    |
    | Tolerance amount (in EGP) for balance mismatch detection. Transactions
    | with balance differences within this tolerance will be accepted as valid.
    | This accounts for fees, other transactions between SMS messages, etc.
    | Default is 100.00 EGP.
    |
    */
    'spoofing_tolerance' => 1.00,
];
