<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCredential extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected $fillable = [
        'user_id',
        'category',
        'original_category',
        'title',
        'note',
    ];
}
