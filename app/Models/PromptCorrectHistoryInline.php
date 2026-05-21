<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptCorrectHistoryInline extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'prompt_correct_history_id', 'original_prompt', 'corrected_prompt'];

}

