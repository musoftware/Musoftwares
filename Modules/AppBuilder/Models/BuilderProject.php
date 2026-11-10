<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** One customer conversation and the program it leads to. Paying moves it to the worker's queue. */
class BuilderProject extends Model
{
    use SoftDeletes;

    public const CHATTING = 'chatting';

    public const QUOTED = 'quoted';

    public const QUEUED = 'queued';

    public const BUILDING = 'building';

    /** Paid, waiting for the Musoftware designer; the build starts when the team adds the design link. */
    public const DESIGNING = 'designing';

    public const READY = 'ready';

    public const FAILED = 'failed';

    /** The customer can type only while no build is waiting or running. */
    private const OPEN_FOR_CHAT = [self::CHATTING, self::QUOTED, self::READY];

    protected $guarded = ['id'];

    protected $attributes = ['status' => self::CHATTING, 'version' => 0, 'quote_version' => 0, 'attempts' => 0];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'referral_commission' => 'decimal:2',
        'quote_paid' => 'decimal:2',
        'payment_due' => 'decimal:2',
        'design_fee' => 'decimal:2',
        'study_fee' => 'decimal:2',
        'budget_usd' => 'decimal:4',
        'build_cost_usd' => 'decimal:4',
        'paid_at' => 'datetime',
        'built_at' => 'datetime',
        'lease_expires_at' => 'datetime',
        'free_fix' => 'boolean',
        'awaiting_answer' => 'boolean',
        'demo_password' => 'encrypted',
        'demo_requested_at' => 'datetime',
        'demo_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $project): void {
            $project->uuid ??= (string) Str::uuid();
        });
    }

    /** The signed-in customer it belongs to; a guest's project has none until they open it signed in. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BuilderMessage::class, 'project_id')->orderBy('id');
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(BuilderSecret::class, 'project_id')->orderBy('name');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BuilderAttachment::class, 'project_id')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** A running build that asked a question opens the chat for the answer. */
    public function isOpenForChat(): bool
    {
        return in_array($this->status, self::OPEN_FOR_CHAT, true) || $this->isWaitingForAnswer();
    }

    /** What is still owed on the current quote after a deposit; 0 when nothing was paid yet or all is paid. */
    public function balanceDue(): float
    {
        return (float) $this->quote_paid > 0 ? max(0.0, round((float) $this->amount - (float) $this->quote_paid, 2)) : 0.0;
    }

    public function hasBalanceDue(): bool
    {
        return $this->balanceDue() > 0;
    }

    public function needsDesign(): bool
    {
        return (float) $this->design_fee > 0 && ! $this->design_url;
    }

    public function isWaitingForAnswer(): bool
    {
        return $this->status === self::BUILDING && $this->awaiting_answer;
    }

    /** The language the customer writes in, which can differ from the site's language setting. */
    public function customerLanguage(): string
    {
        $written = $this->messages()->where('role', BuilderMessage::USER)->pluck('content')->implode(' ');

        return preg_match('/\p{Arabic}/u', $written) ? 'ar' : 'en';
    }

    public function hasDelivery(): bool
    {
        return $this->version > 0;
    }

    /** A bug in what was delivered is fixed for free, a few times, soon after delivery, if the fix is small. */
    public function canGetFreeFix(int $estimatedTokens): bool
    {
        $rules = config('builder.free_fixes');

        return $this->hasDelivery()
            && ! $this->hasBalanceDue()
            && $this->free_fixes_used < (int) $rules['per_project']
            && $estimatedTokens <= (int) $rules['max_tokens']
            && (bool) $this->built_at?->greaterThanOrEqualTo(now()->subDays((int) $rules['days']));
    }

    /** Folder on the local disk that holds every delivered version. */
    public function storageFolder(): string
    {
        return 'builder/'.$this->uuid;
    }

    public function versionFolder(?int $version = null): string
    {
        return $this->storageFolder().'/v'.($version ?? $this->version);
    }

    public function archivePath(?int $version = null): string
    {
        return $this->storageFolder().'/v'.($version ?? $this->version).'.zip';
    }

    /** Jobs the worker may take: paid and waiting, or started by a worker that stopped answering. */
    public function scopeClaimable(Builder $query, int $maxAttempts): Builder
    {
        return $query->where('attempts', '<', $maxAttempts)->where(function (Builder $inner): void {
            $inner->where('status', self::QUEUED)
                ->orWhere(fn (Builder $stale) => $stale->where('status', self::BUILDING)->where('lease_expires_at', '<', now()));
        });
    }

    public function scopeStartedTodayFrom(Builder $query, string $ip): Builder
    {
        return $query->where('ip', $ip)->where('created_at', '>=', now()->subDay());
    }
}
