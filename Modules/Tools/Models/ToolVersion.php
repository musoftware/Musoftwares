<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolVersion extends Model
{
    protected $fillable = [
        'tool_id', 'version', 'changelog', 'file_path', 'file_name',
        'file_size', 'checksum', 'min_plan', 'is_latest', 'is_beta', 'released_at',
    ];

    protected $casts = [
        'is_latest'   => 'boolean',
        'is_beta'     => 'boolean',
        'file_size'   => 'integer',
        'released_at' => 'datetime',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ToolDownload::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) return 'N/A';
        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = $this->file_size;
        $i     = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }

    public function markAsLatest(): void
    {
        ToolVersion::where('tool_id', $this->tool_id)->update(['is_latest' => false]);
        $this->update(['is_latest' => true]);
        $this->tool->update(['current_version' => $this->version]);
    }
}
