<?php

namespace Modules\Intelligence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Intelligence\Database\Factories\IntelligenceSwipeCollectionFactory;

class IntelligenceSwipeCollection extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): IntelligenceSwipeCollectionFactory
    // {
    //     // return IntelligenceSwipeCollectionFactory::new();
    // }
}
