<?php

namespace Modules\AffiliatePos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingCompany extends Model
{
    use SoftDeletes;
    
    protected $table = 'affiliate_pos_shipping_companies';
    protected $guarded = [];
}
