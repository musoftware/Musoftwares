<?php

namespace Modules\AutoSms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoSmsWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'webhook_url',
        'webhook_secret',
        'is_active',
        'success_count',
        'failure_count',
        'last_triggered_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the user that owns this webhook
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
