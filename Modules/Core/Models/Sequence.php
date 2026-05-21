<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sequence extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
        'trigger_type',
        'whatsapp_channel_id',
    ];

    public function steps()
    {
        return $this->hasMany(SequenceStep::class)->orderBy('order');
    }

    public function states()
    {
        return $this->hasMany(SequenceState::class);
    }
    
    public function channel()
    {
        return $this->belongsTo(WhatsAppChannel::class, 'whatsapp_channel_id');
    }
}
