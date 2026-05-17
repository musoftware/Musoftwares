<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PayoutMethod extends Model
{
    protected $fillable = ['user_id', 'type', 'is_default', 'details', 'status'];

    protected $casts = [
        'is_default' => 'boolean',
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
