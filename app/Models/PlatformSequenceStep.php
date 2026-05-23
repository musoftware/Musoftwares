<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSequenceStep extends Model
{
    use HasFactory;

    protected $table = 'platform_sequence_steps';

    protected $fillable = [
        'sequence_id',
        'order',
        'delay',
        'unit',
        'send_email',
        'send_whatsapp',
        'email_subject',
        'email_content',
        'whatsapp_content',
    ];

    protected $casts = [
        'send_email' => 'boolean',
        'send_whatsapp' => 'boolean',
        'email_subject' => 'array',
        'email_content' => 'array',
        'whatsapp_content' => 'array',
    ];

    public function sequence()
    {
        return $this->belongsTo(PlatformSequence::class, 'sequence_id');
    }
}
