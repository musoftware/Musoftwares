<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SupportTicket extends Model
{
    protected $fillable = ['client_id', 'subject', 'status', 'priority'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function conversation(): MorphOne
    {
        return $this->morphOne(Conversation::class, 'conversable');
    }
}
