<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_ARCHIVED = 'archived';

    public const ACTION_RESTORED = 'restored';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_BULK_ARCHIVED = 'bulk_archived';

    public const ACTION_BULK_RESTORED = 'bulk_restored';

    public const ACTION_BULK_DELETED = 'bulk_deleted';

    protected $table = 'project_audit_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
