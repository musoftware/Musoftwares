<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoSwimlane extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title'];
}

