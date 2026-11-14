<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One change the customer asked for in a project that already started: free (a fix to what it has) or priced (new).
 * It waits for payment, waits for the engineer, is being built, or is done. The owner approves it before it starts.
 */
class BuilderRequest extends Model
{
    use SoftDeletes;

    public const UNPAID = 'unpaid';

    public const READY = 'ready';

    /** New work asked for after the free changes were used: it waits until the customer opens more of them. */
    public const LOCKED = 'locked';

    public const BUILDING = 'building';

    public const DONE = 'done';

    /** A fix to what the program has or promises (free in the warranty), or something new. */
    public const FIX = 'fix';

    public const BUILD = 'build';

    /** Really another program, outside the project's main idea: priced even in the free first period. */
    public const NEW_PROJECT = 'new_project';

    /** Not work: the customer's payment that opens another set of free changes. */
    public const PACK = 'pack';

    protected $guarded = ['id'];

    protected $casts = [
        'free' => 'boolean',
        'complex' => 'boolean',
        'amount' => 'decimal:2',
        'referral_commission' => 'decimal:2',
        'budget_usd' => 'decimal:4',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'not_made_at' => 'datetime',
        'rollback_asked_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    /** Delivered with its commits known, and not undone or being undone. */
    public function canRollBack(): bool
    {
        return $this->status === self::DONE && $this->git_from && $this->git_to && $this->rolled_back_at === null && $this->rollback_asked_at === null;
    }

    /** The owner read what goes to the engineer and agreed (or the site does not ask the owner). */
    public function isApproved(): bool
    {
        return $this->approved_at !== null || ! config('builder.owner_approval');
    }

    /** Paid or free, and only the owner's yes is missing. */
    public function waitsForOwner(): bool
    {
        return $this->status === self::READY && (! $this->isApproved() || $this->not_made_at !== null);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }
}
