<?php

namespace Modules\Tools\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

class WaCampaign extends Model
{
    use SoftDeletes;

    protected $table = 'wa_campaigns';

    protected $fillable = [
        'user_id', 'name', 'status', 'runtime_campaign_id',
        'account_ids', 'message_template', 'media_url',
        'humanize_preset', 'max_block_rate', 'total_contacts',
        'sent', 'failed', 'blocked', 'skipped', 'block_rate',
        'health_score_after', 'runtime_task_id', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'account_ids'  => 'array',
        'max_block_rate' => 'float',
        'block_rate'   => 'float',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(WaContact::class, 'wa_campaign_contacts', 'campaign_id', 'contact_id')
            ->withPivot('status', 'account_id', 'sent_at')
            ->withTimestamps();
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WaMessage::class, 'campaign_id');
    }

    public function getBlockRatePercentAttribute(): float
    {
        return $this->sent > 0 ? round(($this->blocked / $this->sent) * 100, 2) : 0;
    }

    public function isRunning(): bool { return $this->status === 'running'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
}
