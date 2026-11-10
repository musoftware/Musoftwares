<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A file the customer sent with a chat message. It lives on the private local disk, never under public/. */
class BuilderAttachment extends Model
{
    use SoftDeletes;

    public const PDF = 'application/pdf';

    public const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    protected $guarded = ['id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(BuilderMessage::class, 'message_id');
    }

    public function isImage(): bool
    {
        return in_array($this->mime, self::IMAGE_TYPES, true);
    }
}
