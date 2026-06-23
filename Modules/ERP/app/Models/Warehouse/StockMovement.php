<?php

namespace Modules\ERP\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ERP\Database\Factories\Warehouse/StockMovementFactory;

class StockMovement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): Warehouse/StockMovementFactory
    // {
    //     // return Warehouse/StockMovementFactory::new();
    // }
}
