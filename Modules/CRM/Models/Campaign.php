<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class Campaign extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected static function newFactory()
    {
        return \Modules\CRM\Database\Factories\CampaignFactory::new();
    }

    protected $fillable = [
        'workspace_id',
        'embed_token',
        'form_title',
        'form_description',
        'button_text',
        'name',
        'description',
        'type',
        'status',
        'scheduled_at',
        'target_audience',
        'filter_criteria',
        'created_by',
        'sent_count',
        'failed_count',
        'total_recipients',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'filter_criteria' => 'array',
    ];

    /**
     * Get the contents for this campaign
     */
    public function contents()
    {
        return $this->hasMany(CampaignContent::class);
    }

    /**
     * Get the recipients for this campaign
     */
    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    /**
     * Get the admin who created this campaign
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the leads for this campaign
     */
    public function leads()
    {
        return $this->belongsToMany(Lead::class, 'campaign_lead')
            ->withTimestamps()
            ->withPivot('status', 'sent_at', 'failed_reason');
    }

    /**
     * Scope for active campaigns
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'sending']);
    }

    /**
     * Scope for completed campaigns
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for scheduled campaigns
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                     ->whereNotNull('scheduled_at');
    }

    /**
     * Get content for a specific language
     */
    public function getContentForLanguage($language)
    {
        return $this->contents()->where('language', $language)->first();
    }

    /**
     * Check if campaign can be sent
     */
    public function canBeSent()
    {
        return in_array($this->status, ['draft', 'scheduled', 'paused']);
    }

    /**
     * Increment sent count
     */
    public function incrementSentCount()
    {
        $this->increment('sent_count');
    }

    /**
     * Increment failed count
     */
    public function incrementFailedCount()
    {
        $this->increment('failed_count');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->total_recipients == 0) {
            return 0;
        }
        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    /**
     * Check if campaign is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Mark campaign as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
