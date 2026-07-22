<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function thread()
    {
        return $this->morphTo('thread')->with('user');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
