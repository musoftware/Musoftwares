<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReverseOtpVerification extends Model
{
    use HasFactory;

    protected $table = 'reverse_otp_verifications';

    protected $fillable = [
        'channel_id',
        'user_id',
        'sender_phone_number',
        'otp_code',
        'verification_id',
        'status',
        'verified_at',
        'expires_at',
        'callback_url',
        'metadata'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot method to generate verification ID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($verification) {
            if (empty($verification->verification_id)) {
                $verification->verification_id = 'ROTP_' . Str::random(16);
            }
        });
    }

    /**
     * Get the channel that owns this verification
     */
    public function channel()
    {
        return $this->belongsTo(WhatsAppChannel::class, 'channel_id');
    }

    /**
     * Get the user that owns this verification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the callbacks for this verification
     */
    public function callbacks()
    {
        return $this->hasMany(ReverseOtpCallback::class, 'verification_id');
    }

    /**
     * Check if verification is expired
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if verification is valid (not expired and pending)
     */
    public function isValid()
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Mark verification as verified
     */
    public function markAsVerified()
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => now()
        ]);
    }

    /**
     * Mark verification as expired
     */
    public function markAsExpired()
    {
        $this->update([
            'status' => 'expired'
        ]);
    }

    /**
     * Mark verification as invalid
     */
    public function markAsInvalid()
    {
        $this->update([
            'status' => 'invalid'
        ]);
    }

    /**
     * Scope to get pending verifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get verified verifications
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope to get expired verifications
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope to get valid verifications (not expired)
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get verifications by channel
     */
    public function scopeByChannel($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope to get verifications by sender phone
     */
    public function scopeBySenderPhone($query, $phoneNumber)
    {
        return $query->where('sender_phone_number', $phoneNumber);
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'verified' => '<span class="badge bg-success">Verified</span>',
            'expired' => '<span class="badge bg-secondary">Expired</span>',
            'invalid' => '<span class="badge bg-danger">Invalid</span>',
            default => '<span class="badge bg-secondary">Unknown</span>'
        };
    }

    /**
     * Get time remaining until expiration
     */
    public function getTimeRemainingAttribute()
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        $diff = now()->diff($this->expires_at);

        if ($diff->days > 0) {
            return $diff->days . 'd ' . $diff->h . 'h ' . $diff->i . 'm';
        } elseif ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm';
        } else {
            return $diff->i . 'm ' . $diff->s . 's';
        }
    }
}
