<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['currency', 'symbol', 'string_format', 'country_codes', 'is_default'];

    protected $casts = [
        'country_codes' => 'array',
        'is_default' => 'boolean',
    ];

    public static function as_array()
    {
        $as_array = [];
        foreach (static::all() as $item) {
            $as_array[$item->id] = $item;
        }

        return $as_array;
    }

    public static function getDefault(): ?Currency
    {
        return static::where('is_default', true)->first()
            ?? static::where('currency', 'USD')->first()
            ?? static::first();
    }

    public static function getForCountryCode(?string $countryCode): ?Currency
    {
        if (!$countryCode) {
            return static::getDefault();
        }

        $code = strtoupper(trim($countryCode));

        $currencies = static::all();
        foreach ($currencies as $currency) {
            $codes = $currency->country_codes ?? [];
            if (is_array($codes) && in_array($code, array_map('strtoupper', $codes))) {
                return $currency;
            }
        }

        return static::getDefault();
    }

    public function exchangesFrom(): HasMany
    {
        return $this->hasMany(CurrenciesExchange::class, 'currency1');
    }

    public function exchangesTo(): HasMany
    {
        return $this->hasMany(CurrenciesExchange::class, 'currency2');
    }
}
