<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One amount Claude cost us: a build, a failed try, a release or a chat answer. */
class BuilderAiSpending extends Model
{
    use SoftDeletes;

    public const BUILD = 'build';

    public const FAILED_BUILD = 'failed_build';

    public const RELEASE = 'release';

    public const CHAT = 'chat';

    protected $table = 'builder_ai_spendings';

    protected $fillable = ['project_id', 'source', 'usd', 'booked_at', 'cost_transaction_id'];

    protected $casts = [
        'usd' => 'decimal:6',
        'booked_at' => 'datetime',
    ];
}
