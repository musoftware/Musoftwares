<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppCampaignAudience extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $table = 'crm_wa_campaign_audiences';

    protected $fillable = [
        'workspace_id', 'name', 'description', 'filters',
        'source_type', 'estimated_size', 'resolved_size',
        'last_resolved_at', 'suppression_rules',
        'is_dynamic', 'created_by',
    ];

    protected $casts = [
        'filters'           => 'array',
        'suppression_rules' => 'array',
        'is_dynamic'        => 'boolean',
        'last_resolved_at'  => 'datetime',
    ];

    public function members()
    {
        return $this->hasMany(WhatsAppCampaignAudienceMember::class, 'audience_id');
    }

    public function activeMembers()
    {
        return $this->members()->where('is_opted_out', false)->where('is_suppressed', false);
    }

    public function campaigns()
    {
        return $this->hasMany(WhatsAppCampaign::class, 'audience_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function needsResolving(): bool
    {
        return $this->is_dynamic && (
            !$this->last_resolved_at ||
            $this->last_resolved_at->lt(now()->subHours(1))
        );
    }
}
