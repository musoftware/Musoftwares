<?php

namespace Modules\ERP\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Workspace Note — tenant-scoped scratchpad note for internal memos.
 * Used by the ERP dashboard Notes section.
 *
 * Categories: Internal | Client | Project
 */
class TenantNote extends TenantModel
{
    protected $table = 'erp_tenant_notes';

    protected $fillable = [
        'tenant_id',
        'created_by',
        'title',
        'content',
        'category',
        'pinned',
    ];

    protected $casts = [
        'pinned' => 'boolean',
    ];

    public const CATEGORIES = ['Internal', 'Client', 'Project'];

    // ── Relationships ────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
