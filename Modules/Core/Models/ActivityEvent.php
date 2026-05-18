<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * ActivityEvent — the heartbeat of the iSAAS ecosystem.
 *
 * Immutable record of every meaningful system action.
 * Drives timelines, dashboards, audit trails, and future workflow automations.
 */
class ActivityEvent extends Model
{
    // Immutable — no updated_at
    public const UPDATED_AT = null;

    protected $table = 'activity_events';

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'event',
        'description',
        'properties',
        'workspace',
    ];

    protected $casts = [
        'properties'  => 'array',
        'created_at'  => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject()
    {
        if (!$this->subject_type || !$this->subject_id) return null;
        $class = $this->subject_type;
        return $class::find($this->subject_id);
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeForWorkspace(Builder $query, string $workspace): Builder
    {
        return $query->where('workspace', $workspace);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSubject(Builder $query, string $type, int $id): Builder
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOfEvent(Builder $query, string|array $event): Builder
    {
        return is_array($event)
            ? $query->whereIn('event', $event)
            : $query->where('event', $event);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /** Icon hint for the frontend (maps event key → icon name) */
    public function getIconAttribute(): string
    {
        return match(true) {
            str_starts_with($this->event, 'invoice.')     => 'receipt',
            str_starts_with($this->event, 'wallet.')      => 'wallet',
            str_starts_with($this->event, 'order.')       => 'shopping-bag',
            str_starts_with($this->event, 'proposal.')    => 'file-text',
            str_starts_with($this->event, 'task.')        => 'check-square',
            str_starts_with($this->event, 'booking.')     => 'calendar',
            str_starts_with($this->event, 'withdrawal.')  => 'arrow-up-right',
            str_starts_with($this->event, 'referral.')    => 'users',
            str_starts_with($this->event, 'service.')     => 'package',
            str_starts_with($this->event, 'subscription.')=> 'zap',
            default                                        => 'activity',
        };
    }

    /** Color hint for the frontend */
    public function getColorAttribute(): string
    {
        return match(true) {
            str_starts_with($this->event, 'invoice.')     => 'emerald',
            str_starts_with($this->event, 'wallet.')      => 'blue',
            str_starts_with($this->event, 'order.')       => 'indigo',
            str_starts_with($this->event, 'proposal.')    => 'violet',
            str_starts_with($this->event, 'task.')        => 'green',
            str_starts_with($this->event, 'booking.')     => 'cyan',
            str_starts_with($this->event, 'withdrawal.')  => 'amber',
            str_starts_with($this->event, 'referral.')    => 'pink',
            str_starts_with($this->event, 'service.')     => 'orange',
            str_starts_with($this->event, 'subscription.')=> 'purple',
            default                                        => 'slate',
        };
    }

    protected $appends = ['icon', 'color'];
}
