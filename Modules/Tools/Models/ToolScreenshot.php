<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolScreenshot extends Model
{
    protected $fillable = ['tool_id', 'path', 'caption', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
