<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingPageCtaVariant extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'landing_page_id',
        'cta_text',
        'cta_link',
        'cta_description',
        'cta_icon',
        'cta_style',
        'cta_size',
        'cta_animation',
        'position',
        'is_active',
        'priority',
        'show_on_first_visit',
        'show_on_returning_visit',
        'show_on_mobile',
        'show_on_tablet',
        'show_on_desktop',
        'show_in_countries',
        'show_in_languages',
        'hide_in_countries',
        'hide_in_languages',
        'show_for_utm_sources',
        'show_after_seconds',
        'show_after_scroll_percentage',
        'show_on_exit_intent',
        'traffic_percentage',
        'impressions',
        'clicks',
        'click_through_rate',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_first_visit' => 'boolean',
        'show_on_returning_visit' => 'boolean',
        'show_on_mobile' => 'boolean',
        'show_on_tablet' => 'boolean',
        'show_on_desktop' => 'boolean',
        'show_on_exit_intent' => 'boolean',
        'show_in_countries' => 'array',
        'show_in_languages' => 'array',
        'hide_in_countries' => 'array',
        'hide_in_languages' => 'array',
        'show_for_utm_sources' => 'array',
        'click_through_rate' => 'decimal:2',
    ];

    /**
     * Get the landing page this CTA belongs to
     */
    public function landingPage()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'landing_page_id');
    }

    /**
     * Check if this CTA should be shown to the current visitor
     */
    public function shouldShowToVisitor($visitorData = [])
    {
        if (!$this->is_active) {
            return false;
        }

        // Check device type
        $isMobile = $visitorData['is_mobile'] ?? false;
        $isTablet = $visitorData['is_tablet'] ?? false;
        $isDesktop = !$isMobile && !$isTablet;

        if ($isMobile && !$this->show_on_mobile) return false;
        if ($isTablet && !$this->show_on_tablet) return false;
        if ($isDesktop && !$this->show_on_desktop) return false;

        // Check country targeting
        $country = $visitorData['country'] ?? null;
        if ($country) {
            if ($this->show_in_countries && !in_array($country, $this->show_in_countries)) {
                return false;
            }
            if ($this->hide_in_countries && in_array($country, $this->hide_in_countries)) {
                return false;
            }
        }

        // Check language targeting
        $language = $visitorData['language'] ?? null;
        if ($language) {
            if ($this->show_in_languages && !in_array($language, $this->show_in_languages)) {
                return false;
            }
            if ($this->hide_in_languages && in_array($language, $this->hide_in_languages)) {
                return false;
            }
        }

        // Check UTM source targeting
        $utmSource = $visitorData['utm_source'] ?? null;
        if ($utmSource && $this->show_for_utm_sources && !in_array($utmSource, $this->show_for_utm_sources)) {
            return false;
        }

        // Check traffic percentage (A/B testing)
        if ($this->traffic_percentage < 100) {
            $random = rand(1, 100);
            if ($random > $this->traffic_percentage) {
                return false;
            }
        }

        return true;
    }

    /**
     * Track an impression
     */
    public function trackImpression()
    {
        $this->increment('impressions');
        $this->updateClickThroughRate();
    }

    /**
     * Track a click
     */
    public function trackClick()
    {
        $this->increment('clicks');
        $this->updateClickThroughRate();
    }

    /**
     * Update the click-through rate
     */
    protected function updateClickThroughRate()
    {
        if ($this->impressions > 0) {
            $this->update([
                'click_through_rate' => ($this->clicks / $this->impressions) * 100
            ]);
        }
    }

    /**
     * Scope for active CTAs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific position
     */
    public function scopePosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('sort_order', 'asc');
    }
}
