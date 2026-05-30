<?php

namespace Modules\Marketplace\Http\Controllers\Seller;

use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceLandingPage;
use Modules\Marketplace\Models\ServiceLandingQuestion;
use Modules\Marketplace\Models\ServiceLandingFaq;
use Modules\Marketplace\Models\ServiceLandingPricingTable;
use Modules\Marketplace\Models\ServiceLandingFormSubmission;
use Modules\Marketplace\Models\ServiceLandingPageCtaVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class ServiceLandingPageAnalyticsController extends Controller
{

    public function trackCtaClick(Request $request, ServiceLandingPage $landingPage)
    {
        $sessionId = session()->getId();

        // Find the metric record for this session
        $metric = \App\Models\ServiceLandingPageAbMetric::where('landing_page_id', $landingPage->id)
            ->where('session_id', $sessionId)
            ->first();

        if ($metric) {
            $metric->trackClick();
        }

        // Track CTA variant click if provided
        if ($request->has('cta_variant_id')) {
            $ctaVariant = \App\Models\ServiceLandingPageCtaVariant::find($request->cta_variant_id);
            if ($ctaVariant) {
                $ctaVariant->trackClick();
            }
        }

        return response()->json(['success' => true]);
    }


    public function trackScroll(Request $request, ServiceLandingPage $landingPage)
    {
        $depth = $request->input('depth');
        $sessionId = session()->getId();

        // Find the metric record
        $metric = \App\Models\ServiceLandingPageAbMetric::where('landing_page_id', $landingPage->id)
            ->where('session_id', $sessionId)
            ->latest()
            ->first();

        if ($metric) {
            // Only update if the new depth is greater than the existing depth
            if ($depth > $metric->scroll_depth_percentage) {
                 $metric->update(['scroll_depth_percentage' => $depth]);
            }
        }

        return response()->json(['success' => true]);
    }


    public function analytics(Service $service)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;

        if (!$landingPage) {
            return redirect()->route('services.landing-page.create', $service);
        }

        // Get metrics for parent and all variants
        $variants = collect([$landingPage])->merge($landingPage->variants);

        $analytics = $variants->map(function ($variant) {
            $metrics = $variant->abMetrics;

            return [
                'id' => $variant->id,
                'name' => $variant->variant_name ?? 'Original',
                'is_active' => $variant->is_active,
                'is_winner' => $variant->is_winner,
                'total_views' => $metrics->sum('unique_views'),
                'total_clicks' => $metrics->sum('cta_clicks'),
                'total_submissions' => $metrics->sum('form_submissions'),
                'conversion_rate' => $variant->getConversionRate(),
                'ctr' => $metrics->sum('unique_views') > 0
                    ? ($metrics->sum('cta_clicks') / $metrics->sum('unique_views')) * 100
                    : 0,
                'avg_time_on_page' => $metrics->avg('time_on_page_seconds'),
                'avg_scroll_depth' => $metrics->avg('scroll_depth_percentage'),
            ];
        });

        return Inertia::render('Marketplace/Seller/LandingPages/Analytics', [
            'service' => $service,
            'landingPage' => $landingPage,
            'analytics' => $analytics,
        ]);
    }

}
