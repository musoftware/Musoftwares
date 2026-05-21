<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceShareUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'platform',
        'shared_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
