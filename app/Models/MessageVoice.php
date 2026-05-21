<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageVoice extends Model
{
    use HasFactory;
    public function thread()
    {
        return $this->morphTo('thread')->with('user');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
