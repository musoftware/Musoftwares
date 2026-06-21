<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['action_name', 'description', 'coins_reward', 'status', 'user_id'];


}

