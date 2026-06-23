<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ERP\Database\Factories\Procurement/PurchaseOrderItemFactory;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): Procurement/PurchaseOrderItemFactory
    // {
    //     // return Procurement/PurchaseOrderItemFactory::new();
    // }
}
