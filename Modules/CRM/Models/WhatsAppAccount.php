<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRM\app\Traits\BelongsToWorkspace;

class WhatsAppAccount extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $table = 'crm_whatsapp_accounts';

    protected $fillable = [
        'workspace_id',
        'name',
        'phone_number',
        'provider',
        'provider_config',
        'session_data',
        'status',
        'qr_code',
        'qr_expires_at',
        'last_seen_at',
        'health_status',
        'assigned_to',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'provider_config' => 'encrypted:array',
        'session_data'    => 'encrypted',
        'health_status'   => 'array',
        'metadata'        => 'array',
        'is_default'      => 'boolean',
        'qr_expires_at'   => 'datetime',
        'last_seen_at'    => 'datetime',
    ];

    protected $hidden = [
        'session_data',
        'provider_config',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function assignedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function conversations()
    {
        return $this->hasMany(WhatsAppConversation::class, 'account_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeDisconnected($query)
    {
        return $query->where('status', 'disconnected');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isQrExpired(): bool
    {
        return $this->qr_expires_at && $this->qr_expires_at->isPast();
    }
}
