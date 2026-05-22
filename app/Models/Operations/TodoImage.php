<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'default',
    ];
}
