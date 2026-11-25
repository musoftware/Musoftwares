<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockedIp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
    ];

    /**
     * Blocks an IP until a time. An IP already blocked keeps its block. An unblocked IP keeps its soft-deleted row
     * (ip_address is unique), so the new block brings that row back instead of failing on a duplicate.
     */
    public static function block(string $ip, string $reason, \DateTimeInterface $until): void
    {
        $block = static::withTrashed()->firstOrNew(['ip_address' => $ip]);
        if ($block->exists && ! $block->trashed()) {
            return;
        }
        $block->fill(['reason' => $reason, 'blocked_until' => $until]);
        $block->deleted_at = null;
        $block->save();
    }
}
