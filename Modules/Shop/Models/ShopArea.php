<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A delivery area inside a governorate, copied from Angazny. */
class ShopArea extends Model
{
    use SoftDeletes;

    protected $fillable = ['angazny_id', 'city_id', 'name'];

    protected $casts = ['angazny_id' => 'integer', 'city_id' => 'integer'];
}
