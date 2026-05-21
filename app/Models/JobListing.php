<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\TranslationService;
use App\Models\JobListingTranslation;

class JobListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'tech_required',
        'status',
        'created_by',
        'awarded_to',
        'deadline',
        'budget_min',
        'budget_max',
        'bids_count',
    ];

    protected $casts = [
        'tech_required' => 'array',
        'deadline' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    /**
     * Get translations for this job listing
     */
    public function translations()
    {
        return $this->hasMany(JobListingTranslation::class);
    }

    /**
     * Get translation for a specific field and locale
     */
    public function getTranslation(string $field, string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        // If requesting the original locale, return original
        $originalLocale = app(TranslationService::class)->detectLanguage($this->attributes[$field] ?? '');
        if ($locale === $originalLocale) {
            return $this->attributes[$field] ?? null;
        }

        // Check database for existing translation
        $translation = $this->translations()
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        if ($translation) {
            return $translation->value;
        }

        // Auto-translate and cache
        $translationService = app(TranslationService::class);
        $translated = $translationService->translate(
            $this->attributes[$field] ?? '',
            $locale,
            $originalLocale
        );

        if ($translated) {
            JobListingTranslation::updateOrCreate(
                [
                    'job_listing_id' => $this->id,
                    'locale' => $locale,
                    'field' => $field,
                ],
                [
                    'value' => $translated,
                ]
            );
        }

        return $translated ?? ($this->attributes[$field] ?? null);
    }

    /**
     * Get title attribute with automatic translation
     */
    public function getTitleAttribute($value)
    {
        if (!$value) return $value;
        
        $locale = app()->getLocale();
        $originalLocale = app(TranslationService::class)->detectLanguage($value);
        
        if ($locale === $originalLocale) {
            return $value;
        }
        
        return $this->getTranslation('title', $locale) ?? $value;
    }

    /**
     * Get description attribute with automatic translation
     */
    public function getDescriptionAttribute($value)
    {
        if (!$value) return $value;
        
        $locale = app()->getLocale();
        $originalLocale = app(TranslationService::class)->detectLanguage($value);
        
        if ($locale === $originalLocale) {
            return $value;
        }
        
        return $this->getTranslation('description', $locale) ?? $value;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function awardedUser()
    {
        return $this->belongsTo(User::class, 'awarded_to');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function acceptedBid()
    {
        return $this->hasOne(Bid::class)->where('status', 'accepted');
    }

    public function isOpen()
    {
        return $this->status === 'open';
    }

    public function incrementBidsCount()
    {
        $this->increment('bids_count');
    }

    public function decrementBidsCount()
    {
        $this->decrement('bids_count');
    }
}
