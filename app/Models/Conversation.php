<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }
}
