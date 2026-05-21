<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptGenerationIteration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_generation_id',
        'iteration_input',
        'iteration_output',
        'iteration_number',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'parent_generation_id' => 'integer',
        'iteration_number' => 'integer',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parentGeneration()
    {
        return $this->belongsTo(PromptGeneration::class, 'parent_generation_id');
    }
}
