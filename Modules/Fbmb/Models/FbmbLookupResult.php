<?php

namespace Modules\fbmb\Models;

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
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

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
