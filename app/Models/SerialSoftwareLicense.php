<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialSoftwareLicense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'serial_software_licenses';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'serial_software_id',
        'status',
        'expires_at',
        'max_devices',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_devices' => 'integer',
    ];

    /**
     * Get the user that owns the license.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the software associated with this license.
     */
    public function software(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    /**
     * Determine if license is currently active and not expired.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        return now()->setTimezone('Africa/Cairo')->lessThanOrEqualTo(
            Carbon::parse($this->expires_at)->setTimezone('Africa/Cairo')
        );
    }

    /**
     * Scope for active licenses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now()->setTimezone('Africa/Cairo'));
            });
    }
}
