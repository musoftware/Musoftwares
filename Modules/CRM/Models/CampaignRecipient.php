<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'status',
        'sent_at',
        'error_message',
        'language_used',
        'ip_address',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the campaign that owns this recipient
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user for this recipient
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark this recipient as sent
     */
    public function markAsSent($language)
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'language_used' => $language,
            'error_message' => null,
        ]);
    }

    /**
     * Mark this recipient as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark this recipient as skipped
     */
    public function markAsSkipped($reason)
    {
        $this->update([
            'status' => 'skipped',
            'error_message' => $reason,
        ]);
    }

    /**
     * Scope for pending recipients
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent recipients
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed recipients
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
