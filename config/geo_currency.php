<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GeoIP Country to Currency Mapping
    |--------------------------------------------------------------------------
    |
    | Maps a country name detected by GeoIP2 to a 3-letter currency code.
    | If the country is not mapped, or if the currency is not available
    | in the database, the system will fall back to USD.
    |
    */

    'mapping' => [
        'Egypt'                => 'EGP',
        'United States'        => 'USD',
        'Saudi Arabia'         => 'SAR',
        'United Arab Emirates' => 'AED',
        'United Kingdom'       => 'GBP',
        'Germany'              => 'EUR',
        'France'               => 'EUR',
        'Italy'                => 'EUR',
        'Spain'                => 'EUR',
        'Kuwait'               => 'KWD',
        'Qatar'                => 'QAR',
        'Oman'                 => 'OMR',
        'Bahrain'              => 'BHD',
        'Canada'               => 'CAD',
        'Australia'            => 'AUD',
    ],
];
