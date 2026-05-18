<?php

namespace Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Intelligence\Database\Factories\IntelligenceSwipeItemFactory;

class IntelligenceSwipeItem extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): IntelligenceSwipeItemFactory
    // {
    //     // return IntelligenceSwipeItemFactory::new();
    // }
}
