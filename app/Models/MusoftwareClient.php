<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MusoftwareClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'website',
        'status',
        'webhook_secret',
        'allowed_ips',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
    ];

    public function payments()
    {
        return $this->hasMany(MusoftwarePayment::class, 'client_id');
    }
}
