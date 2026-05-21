<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptCorrectHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'original_prompt', 'corrected_prompt'];

    public function inlines()
    {
        return $this->hasMany(PromptCorrectHistoryInline::class, 'prompt_correct_history_id');
    }
}

