<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use SoftDeletes, HasFactory;

    public $timestamps = false;

    protected $fillable = ['currency', 'symbol', 'string_format'];

    public static function as_array()
    {
        $as_array = array();
        foreach (static::all() as $item) {
            $as_array[$item->id] = $item;
        }
        return $as_array;
    }
}
