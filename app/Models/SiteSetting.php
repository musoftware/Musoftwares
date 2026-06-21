<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model {
    use SoftDeletes;

    protected $fillable = ['key', 'value', 'group'];
}
