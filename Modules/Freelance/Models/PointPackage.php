<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;

class PointPackage extends Model
{
    protected $table = 'point_packages';
    protected $fillable = ['name', 'points', 'price', 'currency_code'];
}
