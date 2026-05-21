<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialUserDevice extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'device_id',
        'status',
        'notes',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::observe(\App\Observers\SerialUserDeviceObserver::class);
    }

    /**
     * @return string[]
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }

    /**
     * Get the user that owns the device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
