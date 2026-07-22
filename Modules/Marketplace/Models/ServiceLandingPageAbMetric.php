<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLandingPageAbMetric extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_mobile' => 'boolean',
        'is_tablet' => 'boolean',
        'converted' => 'boolean',
        'converted_at' => 'datetime',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'conversion_value' => 'decimal:2',
    ];

    /**
     * Get the landing page this metric belongs to
     */
    public function landingPage()
    {
        return $this->belongsTo(ServiceLandingPage::class, 'landing_page_id');
    }

    /**
     * Calculate conversion rate
     */
    public function getConversionRateAttribute()
    {
        if ($this->unique_views == 0) {
            return 0;
        }

        return ($this->form_submissions / $this->unique_views) * 100;
    }

    /**
     * Calculate click-through rate
     */
    public function getClickThroughRateAttribute()
    {
        if ($this->unique_views == 0) {
            return 0;
        }

        return ($this->cta_clicks / $this->unique_views) * 100;
    }

    /**
     * Track a page view
     */
    public static function trackView($landingPageId, $sessionId, $requestData = [])
    {
        $metric = self::firstOrCreate(
            [
                'landing_page_id' => $landingPageId,
                'session_id' => $sessionId,
            ],
            array_merge([
                'visitor_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'is_mobile' => request()->header('User-Agent') ? str_contains(strtolower(request()->header('User-Agent')), 'mobile') : false,
                'referrer_url' => request()->header('referer'),
                'first_viewed_at' => now(),
            ], $requestData)
        );

        $metric->increment('page_views');
        $metric->update(['last_viewed_at' => now()]);

        return $metric;
    }

    /**
     * Track a CTA click
     */
    public function trackClick()
    {
        $this->increment('cta_clicks');
    }

    /**
     * Track a form submission
     */
    public function trackSubmission($conversionValue = null)
    {
        $this->increment('form_submissions');
        $this->update([
            'converted' => true,
            'converted_at' => now(),
            'conversion_value' => $conversionValue,
        ]);
    }
}
