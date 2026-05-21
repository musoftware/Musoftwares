<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'generation_id',
        'custom_name',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'generation_id' => 'integer',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generation()
    {
        return $this->belongsTo(PromptGeneration::class, 'generation_id');
    }
}
