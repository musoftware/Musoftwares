<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'slug',
        'hero_title',
        'hero_description',
        'hero_cta_text',
        'description',
        'is_active',
        'template',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'twitter_card_type',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
        'description_alignment',
        'facebook_pixel_id',
        'tiktok_pixel_id',
        'snapchat_pixel_id',
        'google_analytics_id',
        // Builder Features
        'layout_config',
        'style_config',
        'form_config',
        'lead_routing_config',
        'published_at',
        'scheduled_at',
        'ai_seo_score',
        'ai_persona',
        // A/B Testing
        'ab_testing_enabled',
        'parent_variant_id',
        'variant_name',
        'traffic_split_percentage',
        'auto_winner_visits',
        'is_winner',
        // Sticky CTA
        'sticky_cta_enabled',
        'sticky_cta_text',
        'sticky_cta_position',
        'sticky_cta_mobile_only',
        // Exit Intent
        'exit_intent_enabled',
        'exit_intent_title',
        'exit_intent_message',
        'exit_intent_cta_text',
        'exit_intent_desktop_only',
        // Time-based Popup
        'time_based_popup_enabled',
        'time_based_popup_delay',
        'time_based_popup_title',
        'time_based_popup_message',
        'time_based_popup_cta_text',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ab_testing_enabled' => 'boolean',
        'is_winner' => 'boolean',
        'sticky_cta_enabled' => 'boolean',
        'sticky_cta_mobile_only' => 'boolean',
        'exit_intent_enabled' => 'boolean',
        'exit_intent_desktop_only' => 'boolean',
        'time_based_popup_enabled' => 'boolean',
        'layout_config' => 'array',
        'style_config' => 'array',
        'form_config' => 'array',
        'lead_routing_config' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function questions()
    {
        return $this->hasMany(ServiceLandingQuestion::class, 'landing_page_id')->orderBy('sort_order');
    }

    public function faqs()
    {
        return $this->hasMany(ServiceLandingFaq::class, 'landing_page_id')->orderBy('sort_order');
    }

    public function pricingTables()
    {
        return $this->hasMany(ServiceLandingPricingTable::class, 'landing_page_id')->orderBy('sort_order');
    }

    public function formSubmissions()
    {
        return $this->hasMany(ServiceLandingFormSubmission::class, 'landing_page_id');
    }

    // A/B Testing Relationships
    public function parentVariant()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'parent_variant_id');
    }

    public function variants()
    {
        return $this->hasMany(ServiceLandingPage::class, 'parent_variant_id');
    }

    public function abMetrics()
    {
        return $this->hasMany(ServiceLandingPageAbMetric::class, 'landing_page_id');
    }

    // CTA Variants
    public function ctaVariants()
    {
        return $this->hasMany(ServiceLandingPageCtaVariant::class, 'landing_page_id')->orderBy('sort_order');
    }

    /**
     * Get active variants for A/B testing
     */
    public function getActiveVariantsAttribute()
    {
        if (!$this->ab_testing_enabled) {
            return collect([]);
        }

        return $this->variants()->where('is_active', true)->get();
    }

    /**
     * Select a variant for the current visitor using weighted random selection
     */
    public function selectVariant()
    {
        if (!$this->ab_testing_enabled || $this->parent_variant_id) {
            return $this;
        }

        // Get all active variants including parent
        $activeVariants = $this->variants()->where('is_active', true)->get();
        
        if ($activeVariants->isEmpty()) {
            return $this; // No variants, show parent
        }

        // Add parent to the pool
        $pool = collect([$this])->merge($activeVariants);

        // Calculate total traffic percentage
        $totalPercentage = $pool->sum('traffic_split_percentage');
        
        if ($totalPercentage <= 0) {
            return $this; // Fallback to parent
        }

        // Weighted random selection
        $random = rand(1, $totalPercentage);
        $currentSum = 0;

        foreach ($pool as $variant) {
            $currentSum += $variant->traffic_split_percentage;
            if ($random <= $currentSum) {
                return $variant;
            }
        }

        return $this; // Fallback to parent
    }

    /**
     * Get conversion rate for this landing page
     */
    public function getConversionRate()
    {
        $totalViews = $this->abMetrics()->sum('unique_views');
        $totalConversions = $this->abMetrics()->where('converted', true)->count();

        if ($totalViews == 0) {
            return 0;
        }

        return ($totalConversions / $totalViews) * 100;
    }

    /**
     * Get total visits
     */
    public function getTotalVisits()
    {
        return $this->abMetrics()->sum('unique_views');
    }

    /**
     * Check if this variant should be automatically selected as winner
     */
    public function shouldAutoSelectWinner()
    {
        if (!$this->auto_winner_visits || $this->is_winner) {
            return false;
        }

        $totalVisits = $this->getTotalVisits();
        
        return $totalVisits >= $this->auto_winner_visits;
    }

    /**
     * Determine the winning variant based on conversion rate
     */
    public function determineWinner()
    {
        if ($this->parent_variant_id) {
            return $this->parentVariant->determineWinner();
        }

        $variants = collect([$this])->merge($this->variants()->where('is_active', true)->get());
        
        $winner = $variants->sortByDesc(function ($variant) {
            return $variant->getConversionRate();
        })->first();

        if ($winner) {
            // Mark winner
            $winner->update(['is_winner' => true]);
            
            // Deactivate other variants
            $variants->where('id', '!=', $winner->id)->each(function ($variant) {
                $variant->update(['is_active' => false, 'is_winner' => false]);
            });
        }

        return $winner;
    }
}
