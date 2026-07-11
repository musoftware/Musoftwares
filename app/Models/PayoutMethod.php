<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'details',
        'is_default',
        'status',
    ];

    protected $casts = [
        'details' => 'array',
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
