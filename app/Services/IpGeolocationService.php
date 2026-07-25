<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class IpGeolocationService extends BaseService
{
    /**
     * List of Arab country codes
     */
    protected const ARAB_COUNTRIES = [
        'SA', // Saudi Arabia
        'EG', // Egypt
        'AE', // United Arab Emirates
        'IQ', // Iraq
        'JO', // Jordan
        'LB', // Lebanon
        'LY', // Libya
        'MA', // Morocco
        'OM', // Oman
        'PS', // Palestine
        'QA', // Qatar
        'SD', // Sudan
        'SY', // Syria
        'TN', // Tunisia
        'YE', // Yemen
        'DZ', // Algeria
        'BH', // Bahrain
        'KW', // Kuwait
        'MR', // Mauritania
        'SO', // Somalia
        'DJ', // Djibouti
        'KM', // Comoros
    ];

    /**
     * Map of Country Codes to Currency Codes
     */
    protected const COUNTRY_TO_CURRENCY_MAP = [
        // Arab Countries
        'SA' => 'SAR', // Saudi Arabia
        'EG' => 'EGP', // Egypt
        'AE' => 'AED', // United Arab Emirates
        'IQ' => 'IQD', // Iraq
        'JO' => 'JOD', // Jordan
        'LB' => 'LBP', // Lebanon
        'LY' => 'LYD', // Libya
        'MA' => 'MAD', // Morocco
        'OM' => 'OMR', // Oman
        'PS' => 'ILS', // Palestine (Using ILS as it's common for online tx, or JOD)
        'QA' => 'QAR', // Qatar
        'SD' => 'SDG', // Sudan
        'SY' => 'SYP', // Syria
        'TN' => 'TND', // Tunisia
        'YE' => 'YER', // Yemen
        'DZ' => 'DZD', // Algeria
        'BH' => 'BHD', // Bahrain
        'KW' => 'KWD', // Kuwait
        'MR' => 'MRU', // Mauritania
        'SO' => 'SOS', // Somalia
        'DJ' => 'DJF', // Djibouti
        'KM' => 'KMF', // Comoros

        // Major Economies
        'US' => 'USD', // USA
        'GB' => 'GBP', // UK
        'EU' => 'EUR', // Europe (Generic)
        'DE' => 'EUR', // Germany
        'FR' => 'EUR', // France
        'IT' => 'EUR', // Italy
        'ES' => 'EUR', // Spain
        'NL' => 'EUR', // Netherlands
        'BE' => 'EUR', // Belgium
        'AT' => 'EUR', // Austria
        'CA' => 'CAD', // Canada
        'AU' => 'AUD', // Australia
        'JP' => 'JPY', // Japan
        'IN' => 'INR', // India
        'TR' => 'TRY', // Turkey
        'RU' => 'RUB', // Russia
        'BR' => 'BRL', // Brazil
        'CN' => 'CNY', // China
    ];

    /**
     * Get country code from IP address
     *
     * @param  string  $ip
     * @return string|null
     */
    public function getCountryFromIp($ip)
    {
        try {
            // Skip local/private IPs
            if ($this->isLocalIp($ip)) {
                Log::info("IpGeolocationService: Local IP detected ({$ip}), defaulting to null");

                return null;
            }

            // Try using torann/geoip package if available
            if (function_exists('geoip')) {
                try {
                    $location = geoip()->getLocation($ip);
                    // Check if location object has iso_code (standard for torann/geoip) or country_code
                    $code = $location->iso_code ?? $location->country_code ?? null;

                    if ($code) {
                        Log::info("IpGeolocationService: Detected country {$code} for IP {$ip} (torann/geoip)");

                        return strtoupper($code);
                    }
                } catch (\Exception $e) {
                    Log::warning("IpGeolocationService: Error using geoip() for IP {$ip}: ".$e->getMessage());
                    // Create empty catch block to allow falling back to next method
                }
            }

            // Try using stevebauman/location package if available
            if (class_exists(Location::class)) {
                $location = Location::get($ip);

                if ($location && $location->countryCode) {
                    Log::info("IpGeolocationService: Detected country {$location->countryCode} for IP {$ip}");

                    return strtoupper($location->countryCode);
                }
            }

            // Fallback: Try using PHP's geoip extension if available
            if (function_exists('geoip_country_code_by_name')) {
                $countryCode = geoip_country_code_by_name($ip);
                if ($countryCode) {
                    Log::info("IpGeolocationService: Detected country {$countryCode} for IP {$ip} (geoip)");

                    return strtoupper($countryCode);
                }
            }

            Log::warning("IpGeolocationService: Could not detect country for IP {$ip}");

            return null;

        } catch (\Exception $e) {
            Log::error("IpGeolocationService: Error detecting country for IP {$ip}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Check if country code is an Arab country
     *
     * @param  string|null  $countryCode
     * @return bool
     */
    public function isArabCountry($countryCode)
    {
        if (! $countryCode) {
            return false;
        }

        return in_array(strtoupper($countryCode), self::ARAB_COUNTRIES);
    }

    /**
     * Get language for IP address
     * Returns 'ar' for Arab countries, 'en' otherwise
     *
     * @param  string  $ip
     * @return string
     */
    public function getLanguageForIp($ip)
    {
        $countryCode = $this->getCountryFromIp($ip);

        if ($this->isArabCountry($countryCode)) {
            Log::info("IpGeolocationService: Arabic language selected for IP {$ip} (country: {$countryCode})");

            return 'ar';
        }

        Log::info("IpGeolocationService: English language selected for IP {$ip} (country: ".($countryCode ?? 'unknown').')');

        return 'en';
    }

    /**
     * Get language for user
     * Priority: User preference > IP detection > Default (en)
     *
     * @param  User  $user
     * @param  string|null  $ip
     * @return string
     */
    public function getLanguageForUser($user, $ip = null)
    {
        // Check if user has a language preference set
        if (isset($user->language) && in_array($user->language, array_keys(config('languages.supported', [])))) {
            Log::info("IpGeolocationService: Using user preference language: {$user->language} for user {$user->id}");

            return $user->language;
        }

        // Use IP detection if IP provided
        if ($ip) {
            return $this->getLanguageForIp($ip);
        }

        // Default to English
        Log::info("IpGeolocationService: Defaulting to English for user {$user->id}");

        return 'en';
    }

    /**
     * Check if IP is local/private
     *
     * @param  string  $ip
     * @return bool
     */
    protected function isLocalIp($ip)
    {
        // Check for localhost
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }

        // Check for private IP ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    /**
     * Get currency model for country
     *
     * @param  string|null  $countryCode
     * @return Currency|null
     */
    public function getCurrencyForCountry($countryCode)
    {
        return Currency::getForCountryCode($countryCode);
    }

    /**
     * Get currency code for country
     *
     * @param  string|null  $countryCode
     * @return string|null
     */
    public function getCurrencyCodeForCountry($countryCode)
    {
        $currency = $this->getCurrencyForCountry($countryCode);
        if ($currency) {
            return $currency->currency;
        }

        if (! $countryCode) {
            $default = Currency::getDefault();
            return $default ? $default->currency : 'USD';
        }

        return self::COUNTRY_TO_CURRENCY_MAP[strtoupper($countryCode)] ?? (Currency::getDefault()?->currency ?? 'USD');
    }

    /**
     * Get currency code for IP
     *
     * @param  string  $ip
     * @return string|null
     */
    public function getCurrencyCodeForIp($ip)
    {
        $countryCode = $this->getCountryFromIp($ip);

        return $this->getCurrencyCodeForCountry($countryCode);
    }

    /**
     * Get currency model for IP
     *
     * @param  string  $ip
     * @return Currency
     */
    public function getCurrencyForIp($ip)
    {
        $countryCode = $this->getCountryFromIp($ip);

        return Currency::getForCountryCode($countryCode);
    }
}
