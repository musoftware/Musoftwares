<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSequence extends Model
{
    use HasFactory;

    protected $table = 'platform_sequences';

    protected $fillable = [
        'name',
        'trigger_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(PlatformSequenceStep::class, 'sequence_id')->orderBy('order');
    }

    public function states()
    {
        return $this->hasMany(PlatformSequenceState::class, 'sequence_id');
    }
}
