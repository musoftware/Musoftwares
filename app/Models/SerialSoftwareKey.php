<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerialSoftwareKey extends Model
{
    use HasFactory;

    protected $table = 'serial_software_keys';

    protected $fillable = [
        'serial_software_id',
        'key',
        'default_value',
        'description',
    ];

    public function software(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    public function deviceKeys(): HasMany
    {
        return $this->hasMany(SerialDeviceKey::class, 'serial_software_key_id');
    }
}
