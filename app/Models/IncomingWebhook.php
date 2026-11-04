<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingWebhook extends Model
{
    use HasFactory, ScopesToLegalWindow, SoftDeletes;

    protected $fillable = [
        'source',
        'event_type',
        'payload',
        'headers',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'processed_at' => 'datetime',
    ];
}
