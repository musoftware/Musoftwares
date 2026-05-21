<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use HasFactory;

    protected $fillable = ['action_name', 'description', 'coins_reward', 'status', 'user_id'];


}

