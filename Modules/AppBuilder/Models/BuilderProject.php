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

    /** Undo one delivered change in the project's git (the owner's choice), then release like a release job. */
    public const JOB_ROLLBACK = 'rollback';

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
        'release_asked_at' => 'datetime',
        'deploy_enabled' => 'boolean',
        'customer_project' => 'boolean',
        'inspection_paid' => 'decimal:2',
        'released_version' => 'integer',
        'release_after_changes' => 'integer',
        'release_quiet_minutes' => 'integer',
        'job_cost_usd' => 'decimal:4',
        'budget_usd' => 'decimal:4',
        'build_cost_usd' => 'decimal:4',
        'paid_at' => 'datetime',
        'owner_approved_at' => 'datetime',
        'progress_phases' => 'array',
        'built_at' => 'datetime',
        'warranty_until' => 'datetime',
        'lease_expires_at' => 'datetime',
        'invited_at' => 'datetime',
        'free_fix' => 'boolean',
        'free_build' => 'boolean',
        'quote_version' => 'integer',
        'quote_invoice_id' => 'integer',
        'quote_invoice_version' => 'integer',
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

    /** What the chat asked the programmer, and the answers it got. */
    public function engineerQuestions(): HasMany
    {
        return $this->hasMany(BuilderEngineerQuestion::class, 'project_id');
    }

    /** The walks the worker made through a site the customer wants copied. */
    public function siteTours(): HasMany
    {
        return $this->hasMany(BuilderSiteTour::class, 'project_id');
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

    /** The changes the customer asked for after the project started, oldest first. */
    public function requests(): HasMany
    {
        return $this->hasMany(BuilderRequest::class, 'project_id')->orderBy('id');
    }

    /**
     * A running build that asked a question opens the chat for the answer. A project that started and was paid stays
     * open like a messaging app: what the customer writes during a build joins their list of requests.
     */
    public function isOpenForChat(): bool
    {
        return in_array($this->status, self::OPEN_FOR_CHAT, true) || $this->isWaitingForAnswer() || $this->takesRequests();
    }

    /** Started and paid (or brought in by the owner): each new change is a request in the list beside the chat. */
    public function takesRequests(): bool
    {
        $started = $this->hasDelivery() || in_array($this->status, [self::QUEUED, self::BUILDING, self::DESIGNING], true);

        return $started
            && $this->job_kind !== self::JOB_IMPORT
            && $this->status !== self::FAILED
            && ((float) $this->paid_total > 0 || $this->isImported());
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

    /** The owner brought it in for a customer: its changes go to its own GitHub and server, never into a download. */
    public function isOwnerImport(): bool
    {
        return $this->isImported() && ! $this->customer_project;
    }

    /** Delivered changes not yet pushed to GitHub and deployed. */
    public function changesWaiting(): int
    {
        return $this->isOwnerImport() ? max(0, (int) $this->version - (int) $this->released_version) : 0;
    }

    /** What the customer paid for the feasibility study comes off the price of the first build, never a later one. */
    /**
     * The study opens only once paid: on its own, or (studies from before it was sold apart) with its fee inside a
     * price the customer paid. Studies written for free back then wait for payment like any other.
     */
    public function studyIsPaid(): bool
    {
        return (float) $this->study_paid > 0 || ((float) $this->study_fee > 0 && (float) $this->quote_paid > 0) || $this->hasDelivery()
            || $this->study_payment_reference === \Modules\AppBuilder\Services\Study\FeasibilityStudy::FREE_REFERENCE;
    }

    /** A signed-in customer reads the study for free; a guest pays for it. */
    public function studyOpensFor(?\App\Models\User $viewer): bool
    {
        return $this->studyIsPaid() || $viewer !== null;
    }

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
        $recent = $this->messages()->where('role', BuilderMessage::USER)->where('content', '!=', '')->reorder('id', 'desc')->limit(10)->pluck('content');
        foreach ($recent as $message) {
            // a link, a number or a file name says nothing about the language: the message before it does
            $words = (string) preg_replace('~https?://\S+|\S+\.\w{2,5}\b~u', ' ', (string) $message);
            if (preg_match('/\p{Arabic}/u', $words)) {
                return 'ar';
            }
            if (preg_match('/\p{Latin}{2,}/u', $words)) {
                return 'en';
            }
        }

        return $this->locale === 'en' ? 'en' : 'ar';
    }

    /** The Arabic the chat writes for this customer: their own dialect once they showed one, otherwise their country's. */
    public function spokenArabic(): string
    {
        $words = $this->messages()->where('role', BuilderMessage::USER)->where('content', '!=', '')->reorder('id')->limit(40)->pluck('content');

        return \Modules\AppBuilder\Services\Chat\Dialect::arabicFor($this->country, $words);
    }

    /** What talking with the customer cost on the chat model, in USD. */
    public function chatCostUsd(): float
    {
        $prices = config('builder.chat_prices');

        return round(($this->chat_input_tokens * $prices['input_usd_per_mtok'] + $this->chat_output_tokens * $prices['output_usd_per_mtok']) / 1_000_000, 4);
    }

    /** Paid for its first build, which waits for the owner to read and approve the brief. */
    public function waitsForOwner(): bool
    {
        return config('builder.owner_approval') && $this->owner_approved_at === null && ! $this->hasDelivery()
            && in_array($this->status, [self::QUEUED, self::DESIGNING], true) && ($this->job_kind ?: self::JOB_BUILD) === self::JOB_BUILD;
    }

    public function hasDelivery(): bool
    {
        return $this->version > 0;
    }

    /**
     * Every fix is free, however many, until the warranty ends (one month after the first delivery or the import).
     * Only for a program that was paid (or the owner's own import): having an account makes nothing free.
     */
    public function isUnderWarranty(): bool
    {
        return $this->hasDelivery() && $this->wasPaid() && ! $this->hasBalanceDue() && (bool) $this->warranty_until?->isFuture();
    }

    /** Money really came in for this program, or it is the owner's own project. */
    public function wasPaid(): bool
    {
        return (float) $this->paid_total > 0 || $this->isOwnerImport();
    }

    /** The last day any change within the program's main idea is free (the start of the warranty plus the free days). */
    public function freeChangesUntil(): ?\Illuminate\Support\Carbon
    {
        return $this->warranty_until?->copy()->subDays((int) config('builder.warranty_days'))->addDays((int) config('builder.free_changes_days'));
    }

    /** How many free pieces of new work are left; a request the customer took back gives its place back. */
    public function freeChangesLeft(): int
    {
        $used = $this->requests()->where('free', true)->where('kind', BuilderRequest::BUILD)->count();

        return max(0, (int) config('builder.free_changes') * (1 + $this->paidChangePacks()->count()) - $used);
    }

    /** The payments that each opened another set of free changes. */
    private function paidChangePacks(): HasMany
    {
        return $this->requests()->where('kind', BuilderRequest::PACK)->where('status', BuilderRequest::DONE);
    }

    /**
     * What opens another set of free changes: the rest up to the top of the estimate the customer saw with the price.
     * 0 when the project has no such room (it was imported, or that rest is already paid).
     */
    public function changesPackPrice(): float
    {
        return max(0.0, round((float) $this->price_max - (float) $this->amount - (float) $this->paidChangePacks()->sum('amount'), 2));
    }

    /** The free changes are used, their days are not over, and paying the rest of the estimate opens more. */
    public function needsChangesPack(): bool
    {
        return $this->isUnderWarranty() && (bool) $this->freeChangesUntil()?->isFuture()
            && $this->freeChangesLeft() === 0 && $this->changesPackPrice() > 0;
    }

    /** New work within the main idea can still be free: the free days are not over and some free changes are left. */
    public function hasFreeChanges(): bool
    {
        return $this->isUnderWarranty() && (bool) $this->freeChangesUntil()?->isFuture() && $this->freeChangesLeft() > 0;
    }

    /**
     * Whether a change costs nothing: a fix until the warranty ends, however many; new work within the main idea
     * while free changes are left; another program never.
     */
    public function changeIsFree(bool $isFix, bool $isNewProject): bool
    {
        if ($isNewProject || ! $this->isUnderWarranty()) {
            return false;
        }

        return $isFix || $this->hasFreeChanges();
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
            // a new project's first build waits for the owner's approval (its changes are approved one by one)
            ->when(config('builder.owner_approval'), fn (Builder $approved) => $approved->where(fn (Builder $either) => $either
                ->whereNotNull('owner_approved_at')->orWhere('version', '>', 0)->orWhereNotIn('job_kind', [self::JOB_BUILD])))
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
