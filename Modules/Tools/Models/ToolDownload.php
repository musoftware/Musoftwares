<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolDownload extends Model
{
    protected $fillable = [
        'user_id', 'tool_id', 'tool_version_id',
        'ip_address', 'user_agent', 'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ToolVersion::class, 'tool_version_id');
    }
}
