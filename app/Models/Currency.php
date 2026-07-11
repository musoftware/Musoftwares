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

    protected $fillable = ['currency', 'symbol', 'string_format'];

    public static function as_array()
    {
        $as_array = [];
        foreach (static::all() as $item) {
            $as_array[$item->id] = $item;
        }

        return $as_array;
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
