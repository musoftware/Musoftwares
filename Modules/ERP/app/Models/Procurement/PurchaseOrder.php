<?php

namespace Modules\ERP\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ERP\Database\Factories\Procurement/PurchaseOrderFactory;

class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): Procurement/PurchaseOrderFactory
    // {
    //     // return Procurement/PurchaseOrderFactory::new();
    // }
}
