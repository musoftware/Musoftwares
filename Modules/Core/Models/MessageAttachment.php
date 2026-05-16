<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = ['message_id', 'type', 'path', 'mime_type', 'size_bytes', 'original_name'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
