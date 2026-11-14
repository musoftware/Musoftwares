<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One question to the site's AI assistant and the answer it gave. */
class AssistantExchange extends Model
{
    use SoftDeletes;

    protected $fillable = ['visitor', 'user_id', 'ip', 'page', 'question', 'answer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
