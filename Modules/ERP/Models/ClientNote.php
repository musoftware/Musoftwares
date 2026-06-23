<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * ERP Client Note — admin notes on a TenantClient.
 * Recovered from old project: UserCredential model.
 * Parallel to platform-level UserNote (admin notes on platform users).
 *
 * Categories: password | anydesk | notes | archived
 * Archive workflow: saves original_category before setting 'archived'.
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientNote extends TenantAwareModel
{
    use SoftDeletes;

    protected $table = 'erp_client_notes';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'created_by',
        'category',
        'original_category',
        'title',
        'content',
    ];

    public const CATEGORIES = ['password', 'anydesk', 'notes'];

    // ── Relationships ────────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\ERP\Models\TeamMember::class, 'created_by');
    }

    // ── Business Logic ───────────────────────────────────────────────

    /**
     * Archive this note — stores original category for restoration.
     * Recovered from old project: UserNotesController::archiveNote()
     */
    public function archive(): void
    {
        if ($this->category === 'archived') {
            return;
        }
        $this->update([
            'original_category' => $this->category,
            'category'          => 'archived',
        ]);
    }

    /**
     * Restore note to its original category.
     * Recovered from old project: UserNotesController::unarchiveNote()
     */
    public function unarchive(): void
    {
        if ($this->category !== 'archived') {
            return;
        }
        $this->update([
            'category'          => $this->original_category ?: 'notes',
            'original_category' => null,
        ]);
    }
}
