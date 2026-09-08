<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialDeviceKey extends Model
{
    use HasFactory;

    protected $table = 'serial_device_keys';

    protected $fillable = [
        'serial_device_id',
        'serial_software_key_id',
        'value',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(SerialDevice::class, 'serial_device_id');
    }

    public function softwareKey(): BelongsTo
    {
        return $this->belongsTo(SerialSoftwareKey::class, 'serial_software_key_id');
    }
}
