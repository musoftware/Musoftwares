<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptGenerationSession extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'session_data'];

    protected $casts = [
        'session_data' => 'array',
    ];
}
