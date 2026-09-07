<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['currency', 'symbol', 'string_format', 'country_codes', 'is_default'];

    protected $casts = [
        'country_codes' => 'array',
        'is_default' => 'boolean',
    ];

    public static function cachedAll()
    {
        return once(fn () => static::all());
    }

    public static function findCached($id)
    {
        if ($id instanceof Currency) {
            return $id;
        }

        if ($id === null || $id === '') {
            return null;
        }

        return static::cachedAll()->firstWhere('id', (int) $id);
    }

    /**
     * Cleanly resolves a Currency model from an ID, code, or model instance.
     */
    public static function resolve(mixed $currency): ?self
    {
        if ($currency instanceof self) {
            return $currency;
        }

        if (is_numeric($currency)) {
            return self::findCached((int) $currency);
        }

        if (is_string($currency) && trim($currency) !== '') {
            return self::cachedAll()->firstWhere('currency', strtoupper(trim($currency)));
        }

        if (is_object($currency)) {
            if (isset($currency->id) && is_numeric($currency->id)) {
                return self::findCached((int) $currency->id);
            }
            if (isset($currency->currency) && is_string($currency->currency)) {
                return self::cachedAll()->firstWhere('currency', strtoupper(trim($currency->currency)));
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return (string) ($this->id ?? $this->currency ?? '');
    }

    public static function as_array()
    {
        $as_array = [];
        foreach (static::cachedAll() as $item) {
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
