<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobListingTranslation extends Model
{
    protected $fillable = [
        'job_listing_id',
        'locale',
        'field',
        'value',
    ];

    /**
     * Get the job listing that owns the translation
     */
    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }
}
