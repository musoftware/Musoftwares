<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReverseOtpCallback extends Model
{
    use HasFactory;

    protected $table = 'reverse_otp_callbacks';

    protected $fillable = [
        'verification_id',
        'callback_url',
        'sent_data',
        'response_status',
        'response_body',
        'error_message',
        'response_time_ms',
        'status',
        'sent_at',
        'responded_at'
    ];

    protected $casts = [
        'sent_data' => 'array',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the verification that owns this callback
     */
    public function verification()
    {
        return $this->belongsTo(ReverseOtpVerification::class, 'verification_id');
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'success' => '<span class="badge bg-success">Success</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
            default => '<span class="badge bg-secondary">Unknown</span>'
        };
    }

    /**
     * Get response status badge
     */
    public function getResponseStatusBadgeAttribute()
    {
        if (!$this->response_status) {
            return '<span class="badge bg-secondary">No Response</span>';
        }

        if ($this->response_status >= 200 && $this->response_status < 300) {
            return '<span class="badge bg-success">' . $this->response_status . '</span>';
        } elseif ($this->response_status >= 400 && $this->response_status < 500) {
            return '<span class="badge bg-warning">' . $this->response_status . '</span>';
        } elseif ($this->response_status >= 500) {
            return '<span class="badge bg-danger">' . $this->response_status . '</span>';
        } else {
            return '<span class="badge bg-info">' . $this->response_status . '</span>';
        }
    }

    /**
     * Scope to get successful callbacks
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to get failed callbacks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get pending callbacks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get response time in readable format
     */
    public function getResponseTimeReadableAttribute()
    {
        if (!$this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms < 1000) {
            return $this->response_time_ms . 'ms';
        } else {
            return round($this->response_time_ms / 1000, 2) . 's';
        }
    }
}
