<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bid extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_listing_id',
        'user_id',
        'name',
        'email',
        'phone',
        'country',
        'price',
        'description',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAnonymous()
    {
        return $this->user_id === null;
    }

    public function getBidderName()
    {
        return $this->user ? $this->user->name : $this->name;
    }

    public function getBidderEmail()
    {
        return $this->user ? $this->user->email : $this->email;
    }

    public function getBidderPhone()
    {
        return $this->user ? ($this->user->phone ?? $this->phone) : $this->phone;
    }

    public function getBidderCountry()
    {
        if ($this->user) {
            // For registered users, you might want to get country from user profile if available
            return $this->country ?? null;
        }
        return $this->country;
    }

    public function getCountryName()
    {
        if (!$this->country) {
            return null;
        }

        $countries = [
            'EG' => __('common.countries.egypt'), 'US' => __('common.countries.united_states'), 'GB' => __('common.countries.united_kingdom'), 'CA' => __('common.countries.canada'),
            'AU' => __('common.countries.australia'), 'DE' => __('common.countries.germany'), 'FR' => __('common.countries.france'), 'IT' => __('common.countries.italy'),
            'ES' => __('common.countries.spain'), 'NL' => __('common.countries.netherlands'), 'BE' => __('common.countries.belgium'), 'CH' => __('common.countries.switzerland'),
            'AT' => __('common.countries.austria'), 'SE' => __('common.countries.sweden'), 'NO' => __('common.countries.norway'), 'DK' => __('common.countries.denmark'),
            'FI' => __('common.countries.finland'), 'PL' => __('common.countries.poland'), 'CZ' => __('common.countries.czech_republic'), 'GR' => __('common.countries.greece'),
            'PT' => __('common.countries.portugal'), 'IE' => __('common.countries.ireland'), 'NZ' => __('common.countries.new_zealand'), 'JP' => __('common.countries.japan'),
            'CN' => __('common.countries.china'), 'IN' => __('common.countries.india'), 'BR' => __('common.countries.brazil'), 'MX' => __('common.countries.mexico'),
            'AR' => __('common.countries.argentina'), 'CL' => __('common.countries.chile'), 'CO' => __('common.countries.colombia'), 'PE' => __('common.countries.peru'),
            'VE' => __('common.countries.venezuela'), 'ZA' => __('common.countries.south_africa'), 'NG' => __('common.countries.nigeria'), 'KE' => __('common.countries.kenya'),
            'GH' => __('common.countries.ghana'), 'MA' => __('common.countries.morocco'), 'TN' => __('common.countries.tunisia'), 'DZ' => __('common.countries.algeria'),
            'SA' => __('common.countries.saudi_arabia'), 'AE' => __('common.countries.united_arab_emirates'), 'KW' => __('common.countries.kuwait'),
            'QA' => __('common.countries.qatar'), 'BH' => __('common.countries.bahrain'), 'OM' => __('common.countries.oman'), 'JO' => __('common.countries.jordan'),
            'LB' => __('common.countries.lebanon'), 'IQ' => __('common.countries.iraq'), 'SY' => __('common.countries.syria'), 'YE' => __('common.countries.yemen'),
            'PK' => __('common.countries.pakistan'), 'BD' => __('common.countries.bangladesh'), 'LK' => __('common.countries.sri_lanka'), 'NP' => __('common.countries.nepal'),
            'AF' => __('common.countries.afghanistan'), 'IR' => __('common.countries.iran'), 'TR' => __('common.countries.turkey'), 'RU' => __('common.countries.russia'),
            'UA' => __('common.countries.ukraine'), 'KZ' => __('common.countries.kazakhstan'), 'UZ' => __('common.countries.uzbekistan'), 'TH' => __('common.countries.thailand'),
            'VN' => __('common.countries.vietnam'), 'PH' => __('common.countries.philippines'), 'MY' => __('common.countries.malaysia'), 'SG' => __('common.countries.singapore'),
            'ID' => __('common.countries.indonesia'), 'KR' => __('common.countries.south_korea'), 'TW' => __('common.countries.taiwan'), 'HK' => __('common.countries.hong_kong'),
        ];

        return $countries[strtoupper($this->country)] ?? strtoupper($this->country);
    }

    public function getWhatsAppNumber()
    {
        $phone = $this->getBidderPhone();
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If phone doesn't start with country code and we have country, try to add it
        if ($this->country && $phone) {
            // Common country codes mapping
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

            $countryCode = $countryCodes[strtoupper($this->country)] ?? null;
            
            // If phone doesn't start with country code, prepend it
            if ($countryCode && !str_starts_with($phone, $countryCode)) {
                // Remove leading zero if present (common in some countries)
                if (str_starts_with($phone, '0')) {
                    $phone = substr($phone, 1);
                }
                $phone = $countryCode . $phone;
            }
        }

        return $phone;
    }
}
