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

    /** What the worker does with a queued project: build a paid change, import the owner's project, or release changes. */
    public const JOB_BUILD = 'build';

    public const JOB_IMPORT = 'import';

    public const JOB_RELEASE = 'release';

    /** When an imported project's changes go to GitHub and the server: after each change, in batches, or by the owner. */
    public const RELEASE_EACH = 'each';

    public const RELEASE_BATCH = 'batch';

    public const RELEASE_MANUAL = 'manual';

    /** The customer can type only while no build is waiting or running. */
    private const OPEN_FOR_CHAT = [self::CHATTING, self::QUOTED, self::READY];

    protected $guarded = ['id'];

    protected $attributes = ['status' => self::CHATTING, 'version' => 0, 'quote_version' => 0, 'attempts' => 0];

    protected $casts = [
        'amount' => 'decimal:2',
        'price_max' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'referral_commission' => 'decimal:2',
        'quote_paid' => 'decimal:2',
        'payment_due' => 'decimal:2',
        'design_fee' => 'decimal:2',
        'leave_discount_usd' => 'decimal:2',
        'leave_discount_at' => 'datetime',
        'left_price_at' => 'datetime',
        'originals_due_at' => 'datetime',
        'leave_reminder_at' => 'datetime',
        'awaiting_look' => 'boolean',
        'look_started_at' => 'datetime',
        'study_fee' => 'decimal:2',
        'study_paid' => 'decimal:2',
        'imported_at' => 'datetime',
        'released_at' => 'datetime',
        'deploy_enabled' => 'boolean',
        'released_version' => 'integer',
        'release_after_changes' => 'integer',
        'release_quiet_minutes' => 'integer',
        'job_cost_usd' => 'decimal:4',
        'budget_usd' => 'decimal:4',
        'build_cost_usd' => 'decimal:4',
        'paid_at' => 'datetime',
        'built_at' => 'datetime',
        'lease_expires_at' => 'datetime',
        'free_fix' => 'boolean',
        'free_build' => 'boolean',
        'quote_version' => 'integer',
        'price_settled_version' => 'integer',
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

    /** Whoever shared the link the customer came from. */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'referrer_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BuilderMessage::class, 'project_id')->orderBy('id');
    }

    /** The customer's newest line. (messages() is ordered oldest first, so latest() on it would not reverse it.) */
    public function lastCustomerMessage(): ?BuilderMessage
    {
        return $this->messages()->reorder('id', 'desc')->where('role', BuilderMessage::USER)->first();
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

    /** The owner brought this project in (a Drive zip, maybe its GitHub repo) for a customer to keep changing. */
    public function isImported(): bool
    {
        return $this->import_url !== null;
    }

    /** Delivered changes not yet pushed to GitHub and deployed. */
    public function changesWaiting(): int
    {
        return $this->isImported() ? max(0, (int) $this->version - (int) $this->released_version) : 0;
    }

    /** What the customer paid for the feasibility study comes off the price of the first build, never a later one. */
    public function studyCredit(): float
    {
        return $this->hasDelivery() ? 0.0 : (float) $this->study_paid;
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
        // an imported project has no customer lines yet: the owner chose its language
        if ($written === '') {
            return $this->locale === 'ar' ? 'ar' : 'en';
        }

        return preg_match('/\p{Arabic}/u', $written) ? 'ar' : 'en';
    }

    /** The language of the customer's last written line: the chat answers in it, even if they switched. */
    public function lastMessageLanguage(): string
    {
        $last = (string) $this->messages()->where('role', BuilderMessage::USER)->where('content', '!=', '')->reorder('id', 'desc')->value('content');

        return preg_match('/\p{Arabic}/u', $last) ? 'ar' : 'en';
    }

    /** What talking with the customer cost on the chat model, in USD. */
    public function chatCostUsd(): float
    {
        $prices = config('builder.chat_prices');

        return round(($this->chat_input_tokens * $prices['input_usd_per_mtok'] + $this->chat_output_tokens * $prices['output_usd_per_mtok']) / 1_000_000, 4);
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
        return $query->where('attempts', '<', $maxAttempts)
            // a paid build may wait a while for the full-quality originals of the design pictures
            ->where(fn (Builder $ready) => $ready->whereNull('originals_due_at')->orWhere('originals_due_at', '<=', now()))
            ->where(function (Builder $inner): void {
            $inner->where('status', self::QUEUED)
                ->orWhere(fn (Builder $stale) => $stale->where('status', self::BUILDING)->where('lease_expires_at', '<', now()));
        });
    }

    public function scopeStartedTodayFrom(Builder $query, string $ip): Builder
    {
        return $query->where('ip', $ip)->where('created_at', '>=', now()->subDay());
    }
}
