<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingWebhook extends Model
{
    use SoftDeletes, HasFactory;

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
