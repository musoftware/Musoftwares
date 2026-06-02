<?php

namespace Modules\Fbmb\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class FbmbLookupResult extends Model
{
    protected $table = 'fbmb_lookup_results';

    protected $fillable = [
        'user_id',
        'download_token',
        'total_ids',
        'found_count',
        'credits_used',
        'remaining_balance',
        'result_path',
        'expires_at',
        'status',
        'input_path',
        'error_message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function fileExists(): bool
    {
        return $this->result_path && file_exists($this->result_path);
    }
}
