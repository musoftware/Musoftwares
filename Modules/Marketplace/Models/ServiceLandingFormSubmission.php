<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingFormSubmission extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'landing_page_id',
        'form_data',
        'submitted_by_name',
        'submitted_by_email',
        'submitted_by_phone',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'form_data' => 'array',
    ];

    public function landingPage()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'landing_page_id');
    }

    /**
     * Get WhatsApp link for the phone number
     * Formats phone number for WhatsApp (wa.me format)
     *
     * @return string|null WhatsApp link or null if no phone number
     */
    public function getWhatsAppLink()
    {
        if (!$this->submitted_by_phone) {
            return null;
        }

        $phone = $this->formatPhoneForWhatsApp();

        if (!$phone) {
            return null;
        }

        return 'https://wa.me/' . $phone;
    }

    /**
     * Format phone number for WhatsApp
     * WhatsApp requires digits only (no +, spaces, dashes)
     *
     * @return string|null Formatted phone number or null if invalid
     */
    public function formatPhoneForWhatsApp()
    {
        if (!$this->submitted_by_phone) {
            return null;
        }

        $phone = $this->submitted_by_phone;

        // Try using libphonenumber if available
        if (class_exists('\libphonenumber\PhoneNumberUtil')) {
            try {
                $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

                // Try to detect country from IP if available
                $defaultCountry = 'EG'; // Default to Egypt
                if ($this->ip_address) {
                    try {
                        if (function_exists('geoip')) {
                            $ipData = geoip()->getLocation($this->ip_address);
                            $defaultCountry = strtoupper($ipData->iso_code ?? $defaultCountry);
                        }
                    } catch (\Exception $e) {
                        // Ignore geoip errors, use default
                    }
                }

                // Try to parse the phone number
                $parsedNumber = $phoneUtil->parse($phone, $defaultCountry);

                if ($phoneUtil->isValidNumber($parsedNumber)) {
                    // Format as E164 and remove the + sign
                    $formatted = $phoneUtil->format($parsedNumber, \libphonenumber\PhoneNumberFormat::E164);
                    return ltrim($formatted, '+');
                }
            } catch (\Exception $e) {
                // If parsing fails, fall back to simple formatting
            }
        }

        // Fallback: Simple formatting - remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If phone doesn't start with country code, try to detect/add it
        if (strlen($phone) < 10) {
            return null; // Too short to be valid
        }

        // Check if it already looks like an international number (starts with country code)
        // Most country codes are 1-3 digits, and international numbers are usually 10-15 digits
        if (strlen($phone) >= 10) {
            // Try to detect if it needs a country code
            $countryCode = $this->detectCountryCodeFromIp();

            if ($countryCode && !$this->startsWithCountryCode($phone, $countryCode)) {
                // Remove leading zero if present (common in some countries)
                if (str_starts_with($phone, '0')) {
                    $phone = substr($phone, 1);
                }
                $phone = $countryCode . $phone;
            }
        }

        return $phone;
    }

    /**
     * Detect country code from IP address
     *
     * @return string|null Country code (digits only) or null
     */
    private function detectCountryCodeFromIp()
    {
        if (!$this->ip_address) {
            return null;
        }

        try {
            if (function_exists('geoip')) {
                $ipData = geoip()->getLocation($this->ip_address);
                $countryIso = strtoupper($ipData->iso_code ?? null);

                if ($countryIso) {
                    return $this->getCountryCode($countryIso);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return null;
    }

    /**
     * Get country code (digits) from ISO country code
     *
     * @param string $isoCode ISO country code (e.g., 'EG', 'US')
     * @return string|null Country code digits or null
     */
    private function getCountryCode($isoCode)
    {
        $countryCodes = [
            'EG' => '20', 'US' => '1', 'GB' => '44', 'CA' => '1', 'AU' => '61',
            'DE' => '49', 'FR' => '33', 'IT' => '39', 'ES' => '34', 'NL' => '31',
            'BE' => '32', 'CH' => '41', 'AT' => '43', 'SE' => '46', 'NO' => '47',
            'DK' => '45', 'FI' => '358', 'PL' => '48', 'CZ' => '420', 'GR' => '30',
            'PT' => '351', 'IE' => '353', 'NZ' => '64', 'JP' => '81', 'CN' => '86',
            'IN' => '91', 'BR' => '55', 'MX' => '52', 'AR' => '54', 'CL' => '56',
            'CO' => '57', 'PE' => '51', 'VE' => '58', 'ZA' => '27', 'NG' => '234',
            'KE' => '254', 'GH' => '233', 'MA' => '212', 'TN' => '216', 'DZ' => '213',
            'SA' => '966', 'AE' => '971', 'KW' => '965', 'QA' => '974', 'BH' => '973',
            'OM' => '968', 'JO' => '962', 'LB' => '961', 'IQ' => '964', 'SY' => '963',
            'YE' => '967', 'PK' => '92', 'BD' => '880', 'LK' => '94', 'NP' => '977',
            'AF' => '93', 'IR' => '98', 'TR' => '90', 'RU' => '7', 'UA' => '380',
            'KZ' => '7', 'UZ' => '998', 'TH' => '66', 'VN' => '84', 'PH' => '63',
            'MY' => '60', 'SG' => '65', 'ID' => '62', 'KR' => '82', 'TW' => '886',
            'HK' => '852',
        ];

        return $countryCodes[strtoupper($isoCode)] ?? null;
    }

    /**
     * Check if phone number starts with a country code
     *
     * @param string $phone Phone number (digits only)
     * @param string $countryCode Country code (digits only)
     * @return bool
     */
    private function startsWithCountryCode($phone, $countryCode)
    {
        return str_starts_with($phone, $countryCode);
    }
}
