<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    public $timestamps = false;

    public static function as_array()
    {
        $as_array = array();
        foreach (static::all() as $item) {
            $as_array[$item->id] = $item;
        }
        return $as_array;
    }
}
