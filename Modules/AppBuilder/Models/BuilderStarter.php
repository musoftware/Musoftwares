<?php

namespace Modules\AppBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A ready program learned from a paid delivery, internal only. Delete it (soft) to stop using it. */
class BuilderStarter extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'version' => 'integer',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(BuilderProject::class, 'source_project_id');
    }

    /** The same shape as a card in Resources/starters.json, marked internal, with its known price and source. */
    public function toCard(): array
    {
        return [
            'slug' => $this->slug,
            'type' => $this->type,
            'title_en' => $this->title,
            'summary_en' => $this->summary,
            'features_en' => $this->features,
            'internal' => true,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'source_uuid' => $this->source?->uuid,
        ];
    }
}
