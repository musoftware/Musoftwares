<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoworkerMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admin_id',
        'worker_id',
        'phone_number',
        'message',
        'channel_id',
        'status',
        'error_message',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function coworker()
    {
        return $this->belongsTo(CoWorker::class, 'worker_id');
    }

    public function channel()
    {
        return $this->belongsTo(WhatsAppChannel::class, 'channel_id');
    }
}
