<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One change the customer asked for in a project that already started: free (a fix to what it has) or priced (new).
 * It waits for payment, waits for the engineer, is being built, or is done.
 */
class BuilderRequest extends Model
{
    use SoftDeletes;

    public const UNPAID = 'unpaid';

    public const READY = 'ready';

    public const BUILDING = 'building';

    public const DONE = 'done';

    /** A fix to what the program has or promises (free in the warranty), or something new. */
    public const FIX = 'fix';

    public const BUILD = 'build';

    protected $guarded = ['id'];

    protected $casts = [
        'free' => 'boolean',
        'complex' => 'boolean',
        'amount' => 'decimal:2',
        'referral_commission' => 'decimal:2',
        'budget_usd' => 'decimal:4',
        'paid_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'project_id');
    }
}
