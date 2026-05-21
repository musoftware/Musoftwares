<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoWorker extends Model
{
    use HasFactory;

    protected $table = 'co_workers';

    protected $fillable = [
        'person_name',
        'email',
        'mobile',
        'facebook',
        'linked_in',
        'whatsapp',
        'time_from',
        'time_to',
    ];

    public function techTags()
    {
        return $this->belongsToMany(CoTechTag::class, 'co_tech_tags_workers', 'co_worker_id', 'co_tech_tag_id');
    }

    /**
     * Get flag image path based on mobile number country code
     * Returns local flag if available, otherwise uses CDN
     */
    public function getFlagPath()
    {
        if (!$this->mobile) {
            return null;
        }

        // Extract country code from mobile number
        $mobile = preg_replace('/[^0-9+]/', '', $this->mobile);
        
        // Get phone country code to ISO 3166-1 alpha-2 country code mapping
        $phoneCodeToIso = $this->getPhoneCodeMapping();

        // Extract country code from phone number
        $countryCode = $this->extractCountryCode($mobile);
        
        if (!$countryCode || !isset($phoneCodeToIso[$countryCode])) {
            return null;
        }

        $countryData = $phoneCodeToIso[$countryCode];
        $isoCode = $countryData['iso'];
        $localFlag = $countryData['local'] ?? null;

        // First, try local flag if available
        if ($localFlag) {
            $localPath = "v1/images/flags/{$localFlag}.png";
            if (file_exists(public_path($localPath))) {
                return asset($localPath);
            }
        }

        // Fallback to CDN (flagcdn.com)
        // Using w40 for 40px width flag, you can change to w20, w60, w80, w120, etc.
        return "https://flagcdn.com/w40/{$isoCode}.png";
    }

    /**
     * Extract country code from phone number
     * Returns the country code if found in our mapping
     */
    private function extractCountryCode($mobile)
    {
        if (empty($mobile)) {
            return null;
        }

        // Remove + if present
        $mobile = ltrim($mobile, '+');

        // Get all valid country codes from the mapping
        $phoneCodeToIso = $this->getPhoneCodeMapping();
        $validCodes = array_keys($phoneCodeToIso);

        // Try to match country codes (check 3-digit, 2-digit, then 1-digit)
        // Priority order: 3-digit codes first, then 2-digit, then 1-digit
        foreach ([3, 2, 1] as $length) {
            if (strlen($mobile) >= $length) {
                $code = substr($mobile, 0, $length);
                if (in_array($code, $validCodes)) {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * Get phone code to ISO country code mapping
     */
    private function getPhoneCodeMapping()
    {
        return [
            // North America
            '1' => ['iso' => 'us', 'local' => 'canada'],      // US/Canada (defaulting to US, local uses canada)
            '52' => ['iso' => 'mx', 'local' => 'mexico'],     // Mexico
            '53' => ['iso' => 'cu', 'local' => null],         // Cuba
            '54' => ['iso' => 'ar', 'local' => 'arg'],        // Argentina
            '55' => ['iso' => 'br', 'local' => null],         // Brazil
            '56' => ['iso' => 'cl', 'local' => null],         // Chile
            '57' => ['iso' => 'co', 'local' => null],         // Colombia
            '58' => ['iso' => 've', 'local' => null],         // Venezuela
            '51' => ['iso' => 'pe', 'local' => null],         // Peru
            
            // Europe
            '44' => ['iso' => 'gb', 'local' => 'uk'],         // UK
            '33' => ['iso' => 'fr', 'local' => 'french'],     // France
            '49' => ['iso' => 'de', 'local' => 'germany'],   // Germany
            '39' => ['iso' => 'it', 'local' => 'italy'],      // Italy
            '34' => ['iso' => 'es', 'local' => 'spain'],      // Spain
            '31' => ['iso' => 'nl', 'local' => null],         // Netherlands
            '32' => ['iso' => 'be', 'local' => null],         // Belgium
            '41' => ['iso' => 'ch', 'local' => 'switzerland'], // Switzerland
            '43' => ['iso' => 'at', 'local' => null],         // Austria
            '45' => ['iso' => 'dk', 'local' => null],         // Denmark
            '46' => ['iso' => 'se', 'local' => null],         // Sweden
            '47' => ['iso' => 'no', 'local' => null],         // Norway
            '48' => ['iso' => 'pl', 'local' => null],         // Poland
            '351' => ['iso' => 'pt', 'local' => 'portugal'],  // Portugal
            '353' => ['iso' => 'ie', 'local' => null],        // Ireland
            '358' => ['iso' => 'fi', 'local' => null],        // Finland
            '420' => ['iso' => 'cz', 'local' => null],        // Czech Republic
            '421' => ['iso' => 'sk', 'local' => null],        // Slovakia
            '30' => ['iso' => 'gr', 'local' => null],         // Greece
            '40' => ['iso' => 'ro', 'local' => null],         // Romania
            '36' => ['iso' => 'hu', 'local' => null],        // Hungary
            '7' => ['iso' => 'ru', 'local' => null],          // Russia/Kazakhstan
            
            // Asia
            '86' => ['iso' => 'cn', 'local' => 'china'],      // China
            '81' => ['iso' => 'jp', 'local' => null],         // Japan
            '82' => ['iso' => 'kr', 'local' => null],         // South Korea
            '91' => ['iso' => 'in', 'local' => null],         // India
            '92' => ['iso' => 'pk', 'local' => null],         // Pakistan
            '880' => ['iso' => 'bd', 'local' => 'bangladesh'], // Bangladesh
            '93' => ['iso' => 'af', 'local' => null],         // Afghanistan
            '94' => ['iso' => 'lk', 'local' => null],         // Sri Lanka
            '95' => ['iso' => 'mm', 'local' => null],         // Myanmar
            '977' => ['iso' => 'np', 'local' => null],        // Nepal
            '98' => ['iso' => 'ir', 'local' => 'iran'],      // Iran
            '90' => ['iso' => 'tr', 'local' => 'turkey'],     // Turkey
            '961' => ['iso' => 'lb', 'local' => null],       // Lebanon
            '962' => ['iso' => 'jo', 'local' => null],        // Jordan
            '963' => ['iso' => 'sy', 'local' => null],        // Syria
            '964' => ['iso' => 'iq', 'local' => null],        // Iraq
            '965' => ['iso' => 'kw', 'local' => null],        // Kuwait
            '966' => ['iso' => 'sa', 'local' => null],        // Saudi Arabia
            '967' => ['iso' => 'ye', 'local' => null],        // Yemen
            '968' => ['iso' => 'om', 'local' => null],        // Oman
            '971' => ['iso' => 'ae', 'local' => null],        // UAE
            '972' => ['iso' => 'il', 'local' => null],        // Israel
            '973' => ['iso' => 'bh', 'local' => null],        // Bahrain
            '974' => ['iso' => 'qa', 'local' => null],        // Qatar
            '60' => ['iso' => 'my', 'local' => null],         // Malaysia
            '62' => ['iso' => 'id', 'local' => null],         // Indonesia
            '63' => ['iso' => 'ph', 'local' => 'philipine'],  // Philippines
            '65' => ['iso' => 'sg', 'local' => null],        // Singapore
            '66' => ['iso' => 'th', 'local' => null],        // Thailand
            '84' => ['iso' => 'vn', 'local' => null],        // Vietnam
            '852' => ['iso' => 'hk', 'local' => null],        // Hong Kong
            '853' => ['iso' => 'mo', 'local' => null],       // Macau
            '886' => ['iso' => 'tw', 'local' => null],       // Taiwan
            
            // Oceania
            '61' => ['iso' => 'au', 'local' => 'aus'],       // Australia
            '64' => ['iso' => 'nz', 'local' => null],        // New Zealand
            
            // Africa
            '27' => ['iso' => 'za', 'local' => 's-africa'],  // South Africa
            '20' => ['iso' => 'eg', 'local' => null],        // Egypt
            '212' => ['iso' => 'ma', 'local' => null],       // Morocco
            '213' => ['iso' => 'dz', 'local' => null],       // Algeria
            '216' => ['iso' => 'tn', 'local' => null],       // Tunisia
            '218' => ['iso' => 'ly', 'local' => null],       // Libya
            '234' => ['iso' => 'ng', 'local' => null],       // Nigeria
            '233' => ['iso' => 'gh', 'local' => null],       // Ghana
            '254' => ['iso' => 'ke', 'local' => null],       // Kenya
            '255' => ['iso' => 'tz', 'local' => null],       // Tanzania
            '256' => ['iso' => 'ug', 'local' => null],       // Uganda
            '257' => ['iso' => 'bi', 'local' => null],       // Burundi
            '250' => ['iso' => 'rw', 'local' => null],       // Rwanda
            '251' => ['iso' => 'et', 'local' => null],       // Ethiopia
            '252' => ['iso' => 'so', 'local' => null],       // Somalia
            '260' => ['iso' => 'zm', 'local' => null],       // Zambia
            '263' => ['iso' => 'zw', 'local' => null],       // Zimbabwe
        ];
    }
}
