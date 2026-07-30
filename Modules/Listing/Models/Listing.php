<?php

namespace Modules\Listing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use SoftDeletes;

    protected $table = 'listings';

    protected $fillable = [
        'user_id',
        'waseet_id',
        'title',
        'description',
        'price',
        'currency',
        'phone',
        'email',
        'original_url',
        'images',
        'city',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Get the user that owns the listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
