<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CleansLeadData;
use Modules\CRM\app\Traits\BelongsToWorkspace;
use Modules\CRM\Infrastructure\Authorization\HasEnterpriseVisibility;

class Lead extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes, CleansLeadData, BelongsToWorkspace, HasEnterpriseVisibility;

    protected $fillable = [
        'workspace_id',
        'branch_id',
        'campaign_id',
        'source',
        'name',
        'email',
        'company',
        'message',
        'status',
        'locale',
        'phone',
        'ip_address',
        'user_agent',
        'custom_data',
        'assigned_to',
    ];

    protected $casts = [
        'custom_data' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::deleted(function ($lead) {
            // Stop/Delete any sequence states associated with this lead when it's deleted
            SequenceState::where('assignable_type', Lead::class)
                ->where('assignable_id', $lead->id)
                ->delete();
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\CRM\Database\Factories\LeadFactory::new();
    }

    /**
     * Get the campaigns for this lead
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_lead')
            ->withTimestamps()
            ->withPivot('status', 'sent_at', 'failed_reason');
    }

    /**
     * Get the lead sets this lead belongs to
     */
    public function leadSets()
    {
        return $this->belongsToMany(LeadSet::class, 'lead_set_memberships')
            ->withTimestamps()
            ->withPivot('id');
    }

    /**
     * Get the notes for this lead
     */
    public function notes()
    {
        return $this->hasMany(LeadNote::class)->orderBy('is_pinned', 'desc')->latest();
    }

    /**
     * Get the tags for this lead
     */
    public function tags()
    {
        return $this->belongsToMany(LeadTag::class, 'lead_tag', 'lead_id', 'tag_id');
    }

    /**
     * Get the user this lead is assigned to
     */
    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Get the branch this lead belongs to
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
