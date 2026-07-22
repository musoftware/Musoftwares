<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function activity()
    {
        return $this->morphTo('activity')->with('user');
    }

    public function thread()
    {
        return $this->morphTo('thread')->with('user');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function heard_by()
    // {
    //     return $this->hasMany(TicketActivityRead::class, 'ticket_activity_id')->with('user');
    // }

}
