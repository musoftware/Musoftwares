<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Action extends Model
{
    use HasFactory, ScopesToLegalWindow, SoftDeletes;

    protected $fillable = ['action_name', 'description', 'coins_reward', 'status', 'user_id'];
}
