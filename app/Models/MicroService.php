<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MicroService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'price',
        'currency_id',
        'delivery_days',
        'is_active',
        'order_index',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'delivery_days' => 'integer',
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MicroServiceOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order_index')->orderBy('id');
    }
}
