<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientVaultAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['human_size'];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'download_count' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * @return Attribute<string, never>
     */
    protected function humanSize(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $bytes = (float) $this->file_size_bytes;
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                $i = 0;
                while ($bytes >= 1024 && $i < count($units) - 1) {
                    $bytes /= 1024;
                    $i++;
                }

                return round($bytes, 1).' '.$units[$i];
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
