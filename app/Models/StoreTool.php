<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StoreTool extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'store_tools';

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'version',
        'download_url',
        'category',
        'price',
        'currency',
        'requires_payment',
        'whatsapp_number',
        'payment_instructions',
        'features',
        'serial_software_id',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'requires_payment' => 'boolean',
        'is_published'     => 'boolean',
        'price'            => 'decimal:2',
        'features'         => 'array',
        'sort_order'       => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (StoreTool $tool) {
            if (empty($tool->slug) && ! empty($tool->name)) {
                $tool->slug = Str::slug($tool->name);
            }
        });
    }

    /**
     * Scope only published tools.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Optional link to SerialSoftware.
     */
    public function serialSoftware(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }
}
