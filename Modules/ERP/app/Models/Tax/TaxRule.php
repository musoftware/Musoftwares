<?php

namespace Modules\ERP\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ERP\Database\Factories\Tax/TaxRuleFactory;

class TaxRule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): Tax/TaxRuleFactory
    // {
    //     // return Tax/TaxRuleFactory::new();
    // }
}
