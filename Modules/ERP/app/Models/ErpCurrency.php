<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ERP\Database\Factories\ErpCurrencyFactory;

class ErpCurrency extends Model
{
    use SoftDeletes;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): ErpCurrencyFactory
    // {
    //     // return ErpCurrencyFactory::new();
    // }
}
