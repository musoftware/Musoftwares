<?php

namespace Modules\Marketplace\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
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

class ServiceLandingPageController extends Controller
{
    /**
     * Display a listing of all landing pages for the current user's services.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get all services owned by the user
        $servicesWithLandingPages = Service::where('seller_id', $user->id)
            ->whereHas('landingPage')
            ->with(['landingPage' => function($query) {
                $query->with(['formSubmissions', 'variants']);
            }])
            ->paginate(15);

        return Inertia::render('Marketplace/Seller/LandingPages/Index', ['servicesWithLandingPages' => $servicesWithLandingPages]);
    }

    public function create(Service $service)
    {
        $this->authorize('update', $service);

        return Inertia::render('Marketplace/Seller/LandingPages/Create', ['service' => $service]);
    }

    public function store(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $request->validate([
            'hero_title' => 'required|string|max:255',
            'hero_description' => 'nullable|string',
            'hero_cta_text' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'slug' => 'required|string|max:100|unique:service_landing_pages,slug',
            'template' => 'required|string|in:modern,creative,business,product,minimal,dashboard,glassmorphism,neumorphism,skeuomorphism,flat-design,material-design,fluent-design,ecommerce,cyberpunk,gaming,dark-mode,brutalism,retro,pastel',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|url|max:500',
            'twitter_card_type' => 'nullable|in:summary,summary_large_image,app,player',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|url|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
            'description_alignment' => 'nullable|string|in:left,center,right',
            'facebook_pixel_id' => 'nullable|string|max:100',
            'tiktok_pixel_id' => 'nullable|string|max:100',
            'snapchat_pixel_id' => 'nullable|string|max:100',
            'google_analytics_id' => 'nullable|string|max:100',
            // A/B Testing
            'ab_testing_enabled' => 'boolean',
            'auto_winner_visits' => 'nullable|integer|min:0',
            // Sticky CTA
            'sticky_cta_enabled' => 'boolean',
            'sticky_cta_text' => 'nullable|string|max:100',
            'sticky_cta_position' => 'nullable|string|in:top,bottom',
            'sticky_cta_mobile_only' => 'boolean',
            // Exit Intent
            'exit_intent_enabled' => 'boolean',
            'exit_intent_title' => 'nullable|string|max:255',
            'exit_intent_message' => 'nullable|string',
            'exit_intent_cta_text' => 'nullable|string|max:100',
            'exit_intent_desktop_only' => 'boolean',
            // Time-based Popup
            'time_based_popup_enabled' => 'boolean',
            'time_based_popup_delay' => 'nullable|integer|min:1',
            'time_based_popup_title' => 'nullable|string|max:255',
            'time_based_popup_message' => 'nullable|string',
            'time_based_popup_cta_text' => 'nullable|string|max:100',
        ]);

        $landingPage = ServiceLandingPage::create([
            'service_id' => $service->id,
            'slug' => Str::slug($request->slug),
            'hero_title' => $request->hero_title,
            'hero_description' => $request->hero_description,
            'hero_cta_text' => $request->hero_cta_text ?? 'Get Started',
            'description' => $request->description,
            'description_alignment' => $request->description_alignment ?? 'left',
            'template' => $request->template ?? 'modern',
            'is_active' => true,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'og_title' => $request->og_title,
            'og_description' => $request->og_description,
            'og_image' => $request->og_image,
            'twitter_card_type' => $request->twitter_card_type ?? 'summary_large_image',
            'twitter_title' => $request->twitter_title,
            'twitter_description' => $request->twitter_description,
            'twitter_image' => $request->twitter_image,
            'canonical_url' => $request->canonical_url,
            'robots' => $request->robots ?? 'index, follow',
            'facebook_pixel_id' => $request->facebook_pixel_id,
            'tiktok_pixel_id' => $request->tiktok_pixel_id,
            'snapchat_pixel_id' => $request->snapchat_pixel_id,
            'google_analytics_id' => $request->google_analytics_id,
            // A/B Testing
            'ab_testing_enabled' => $request->has('ab_testing_enabled'),
            'auto_winner_visits' => $request->auto_winner_visits,
            // Sticky CTA
            'sticky_cta_enabled' => $request->has('sticky_cta_enabled'),
            'sticky_cta_text' => $request->sticky_cta_text,
            'sticky_cta_position' => $request->sticky_cta_position ?? 'bottom',
            'sticky_cta_mobile_only' => $request->has('sticky_cta_mobile_only'),
            // Exit Intent
            'exit_intent_enabled' => $request->has('exit_intent_enabled'),
            'exit_intent_title' => $request->exit_intent_title,
            'exit_intent_message' => $request->exit_intent_message,
            'exit_intent_cta_text' => $request->exit_intent_cta_text,
            'exit_intent_desktop_only' => $request->has('exit_intent_desktop_only'),
            // Time-based Popup
            'time_based_popup_enabled' => $request->has('time_based_popup_enabled'),
            'time_based_popup_delay' => $request->time_based_popup_delay ?? 30,
            'time_based_popup_title' => $request->time_based_popup_title,
            'time_based_popup_message' => $request->time_based_popup_message,
            'time_based_popup_cta_text' => $request->time_based_popup_cta_text,
        ]);

        return redirect()->route('services.landing-page.edit', $service)
            ->with('success', __('general.landing_page_created_successfully'));
    }

    public function edit(Service $service, ?ServiceLandingPage $landingPage = null)
    {
        $this->authorize('update', $service);

        // If landingPage is provided, use it; otherwise get from service
        if (!$landingPage) {
            $landingPage = $service->landingPage;
        }

        if (!$landingPage) {
            return redirect()->route('services.landing-page.create', $service);
        }

        // Ensure the landing page belongs to this service
        if ($landingPage->service_id !== $service->id) {
            abort(403);
        }

        $landingPage->load(['questions', 'faqs', 'pricingTables', 'ctaVariants', 'variants', 'parentVariant']);

        return Inertia::render('Marketplace/Seller/LandingPages/Edit', ['service' => $service, 'landingPage' => $landingPage]);
    }

    public function update(Request $request, Service $service, ?ServiceLandingPage $landingPage = null)
    {
        $this->authorize('update', $service);

        // If landingPage is provided, use it; otherwise get from service
        if (!$landingPage) {
            $landingPage = $service->landingPage;
        }

        if (!$landingPage) {
            return redirect()->route('services.landing-page.create', $service);
        }

        // Ensure the landing page belongs to this service
        if ($landingPage->service_id !== $service->id) {
            abort(403);
        }

        // For A/B testing variants, allow duplicate slugs (they share the parent's slug)
        // Only enforce unique slugs for parent pages (those without parent_variant_id)
        $slugValidation = 'required|string|max:100';
        if (!$landingPage->parent_variant_id) {
            // This is a parent page, enforce unique slug
            $slugValidation .= '|unique:service_landing_pages,slug,' . $landingPage->id;
        }

        $request->validate([
            'hero_title' => 'required|string|max:255',
            'hero_description' => 'nullable|string',
            'hero_cta_text' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'slug' => $slugValidation,
            'template' => 'required|string|in:modern,creative,business,product,minimal,dashboard,glassmorphism,neumorphism,skeuomorphism,flat-design,material-design,fluent-design,ecommerce,cyberpunk,gaming,dark-mode,brutalism,retro,pastel',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|url|max:500',
            'twitter_card_type' => 'nullable|in:summary,summary_large_image,app,player',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|url|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
            'description_alignment' => 'nullable|string|in:left,center,right',
            'questions' => 'nullable|array',
            'questions.*.question_text' => 'required_with:questions|string',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required_with:faqs|string',
            'faqs.*.answer' => 'required_with:faqs|string',
            'pricing_tables' => 'nullable|array',
            'pricing_tables.*.plan_name' => 'required_with:pricing_tables|string',
            'facebook_pixel_id' => 'nullable|string|max:100',
            'tiktok_pixel_id' => 'nullable|string|max:100',
            'snapchat_pixel_id' => 'nullable|string|max:100',
            'google_analytics_id' => 'nullable|string|max:100',
            // A/B Testing
            'ab_testing_enabled' => 'boolean',
            'auto_winner_visits' => 'nullable|integer|min:0',
            'traffic_split_percentage' => 'nullable|integer|min:0|max:100',
            'is_winner' => 'boolean',
            // Sticky CTA
            'sticky_cta_enabled' => 'boolean',
            'sticky_cta_text' => 'nullable|string|max:100',
            'sticky_cta_position' => 'nullable|string|in:top,bottom',
            'sticky_cta_mobile_only' => 'boolean',
            // Exit Intent
            'exit_intent_enabled' => 'boolean',
            'exit_intent_title' => 'nullable|string|max:255',
            'exit_intent_message' => 'nullable|string',
            'exit_intent_cta_text' => 'nullable|string|max:100',
            'exit_intent_desktop_only' => 'boolean',
            // Time-based Popup
            'time_based_popup_enabled' => 'boolean',
            'time_based_popup_delay' => 'nullable|integer|min:1',
            'time_based_popup_title' => 'nullable|string|max:255',
            'time_based_popup_cta_text' => 'nullable|string|max:100',
            // Builder Features
            'layout_config' => 'nullable|string', // JSON string
            'style_config' => 'nullable|string', // JSON string
            'form_config' => 'nullable|string', // JSON string
            'lead_routing_config' => 'nullable|string', // JSON string
            'published_at' => 'nullable|date',
            'scheduled_at' => 'nullable|date',
            'ai_seo_score' => 'nullable|integer|min:0|max:100',
            'ai_persona' => 'nullable|string|max:100',
            // CTA Variants
            'cta_variants' => 'nullable|array',
            'cta_variants.*.cta_text' => 'required_with:cta_variants|string|max:255',
            'cta_variants.*.cta_link' => 'nullable|url|max:500',
            'cta_variants.*.cta_style' => 'nullable|string|in:primary,secondary,success,info',
            'cta_variants.*.position' => 'nullable|string|in:hero,sticky,exit_intent,time_based',
        ]);

        // Decode JSON fields if they are passed as strings
        $layoutConfig = $request->input('layout_config') ? json_decode($request->input('layout_config'), true) : null;
        $styleConfig = $request->input('style_config') ? json_decode($request->input('style_config'), true) : null;
        $formConfig = $request->input('form_config') ? json_decode($request->input('form_config'), true) : null;
        $leadRoutingConfig = $request->input('lead_routing_config') ? json_decode($request->input('lead_routing_config'), true) : null;

        // Prepare update data
        $updateData = [
            'hero_title' => $request->hero_title,
            'hero_description' => $request->hero_description,
            'hero_cta_text' => $request->hero_cta_text ?? 'Get Started',
            'description' => $request->description,
            'description_alignment' => $request->description_alignment ?? 'left',
            'template' => $request->template ?? 'modern',
            'is_active' => $request->has('is_active'),
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'og_title' => $request->og_title,
            'og_description' => $request->og_description,
            'og_image' => $request->og_image,
            'twitter_card_type' => $request->twitter_card_type ?? 'summary_large_image',
            'twitter_title' => $request->twitter_title,
            'twitter_description' => $request->twitter_description,
            'twitter_image' => $request->twitter_image,
            'canonical_url' => $request->canonical_url,
            'robots' => $request->robots ?? 'index, follow',
            'facebook_pixel_id' => $request->facebook_pixel_id,
            'tiktok_pixel_id' => $request->tiktok_pixel_id,
            'snapchat_pixel_id' => $request->snapchat_pixel_id,
            'google_analytics_id' => $request->google_analytics_id,
            // A/B Testing
            'ab_testing_enabled' => $request->has('ab_testing_enabled'),
            'auto_winner_visits' => $request->auto_winner_visits,
            'traffic_split_percentage' => $request->traffic_split_percentage ?? 50,
            'is_winner' => $request->has('is_winner'),
            // Sticky CTA
            'sticky_cta_enabled' => $request->has('sticky_cta_enabled'),
            'sticky_cta_text' => $request->sticky_cta_text,
            'sticky_cta_position' => $request->sticky_cta_position ?? 'bottom',
            'sticky_cta_mobile_only' => $request->has('sticky_cta_mobile_only'),
            // Exit Intent
            'exit_intent_enabled' => $request->has('exit_intent_enabled'),
            'exit_intent_title' => $request->exit_intent_title,
            'exit_intent_message' => $request->exit_intent_message,
            'exit_intent_cta_text' => $request->exit_intent_cta_text,
            'exit_intent_desktop_only' => $request->has('exit_intent_desktop_only'),
            // Time-based Popup
            'time_based_popup_enabled' => $request->has('time_based_popup_enabled'),
            'time_based_popup_delay' => $request->time_based_popup_delay ?? 30,
            'time_based_popup_title' => $request->time_based_popup_title,
            'time_based_popup_message' => $request->time_based_popup_message,
            'time_based_popup_cta_text' => $request->time_based_popup_cta_text,
            // Builder Features
            'layout_config' => $layoutConfig,
            'style_config' => $styleConfig,
            'form_config' => $formConfig,
            'lead_routing_config' => $leadRoutingConfig,
            'published_at' => $request->published_at,
            'scheduled_at' => $request->scheduled_at,
            'ai_seo_score' => $request->ai_seo_score,
            'ai_persona' => $request->ai_persona,
        ];

        // Only update slug for parent pages (not variants)
        // Variants must keep the same slug as their parent for A/B testing
        if (!$landingPage->parent_variant_id) {
            $updateData['slug'] = Str::slug($request->slug);
        }

        $landingPage->update($updateData);

        // Update Questions
        if ($request->has('questions')) {
            $landingPage->questions()->delete();
            foreach ($request->questions as $index => $questionData) {
                if (!empty($questionData['question_text'])) {
                    $fieldOptions = null;
                    if (isset($questionData['field_options']) && !empty($questionData['field_options'])) {
                        if (is_string($questionData['field_options'])) {
                            // Split by newlines and filter empty values
                            $options = array_filter(array_map('trim', explode("\n", $questionData['field_options'])));
                            $fieldOptions = !empty($options) ? $options : null;
                        } else {
                            $fieldOptions = $questionData['field_options'];
                        }
                    }

                    ServiceLandingQuestion::create([
                        'landing_page_id' => $landingPage->id,
                        'question_text' => $questionData['question_text'],
                        'field_type' => $questionData['field_type'] ?? 'text',
                        'field_options' => $fieldOptions,
                        'is_required' => isset($questionData['is_required']),
                        'sort_order' => $index,
                        'placeholder' => $questionData['placeholder'] ?? null,
                        'help_text' => $questionData['help_text'] ?? null,
                    ]);
                }
            }
        }

        // Update FAQs
        if ($request->has('faqs')) {
            $landingPage->faqs()->delete();
            foreach ($request->faqs as $index => $faqData) {
                if (!empty($faqData['question']) && !empty($faqData['answer'])) {
                    ServiceLandingFaq::create([
                        'landing_page_id' => $landingPage->id,
                        'question' => $faqData['question'],
                        'answer' => $faqData['answer'],
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        // Update Pricing Tables
        if ($request->has('pricing_tables')) {
            $landingPage->pricingTables()->delete();
            foreach ($request->pricing_tables as $index => $pricingData) {
                if (!empty($pricingData['plan_name'])) {
                    ServiceLandingPricingTable::create([
                        'landing_page_id' => $landingPage->id,
                        'plan_name' => $pricingData['plan_name'],
                        'description' => $pricingData['description'] ?? null,
                        'price' => $pricingData['price'] ?? 0,
                        'currency_id' => $pricingData['currency_id'] ?? 1,
                        'period' => $pricingData['period'] ?? null,
                        'features' => $this->parseFeatures($pricingData['features'] ?? null),
                        'is_popular' => isset($pricingData['is_popular']),
                        'cta_text' => $pricingData['cta_text'] ?? 'Get Started',
                        'cta_link' => $pricingData['cta_link'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        // Update CTA Variants
        if ($request->has('cta_variants')) {
            $landingPage->ctaVariants()->delete();
            foreach ($request->cta_variants as $index => $ctaData) {
                if (!empty($ctaData['cta_text'])) {
                    \App\Models\ServiceLandingPageCtaVariant::create([
                        'landing_page_id' => $landingPage->id,
                        'cta_text' => $ctaData['cta_text'],
                        'cta_link' => $ctaData['cta_link'] ?? null,
                        'cta_style' => $ctaData['cta_style'] ?? 'primary',
                        'position' => $ctaData['position'] ?? 'hero',
                        'is_active' => isset($ctaData['is_active']),
                        'show_on_first_visit' => isset($ctaData['show_on_first_visit']),
                        'show_on_returning_visit' => isset($ctaData['show_on_returning_visit']),
                        'show_on_mobile' => isset($ctaData['show_on_mobile']),
                        'show_on_desktop' => isset($ctaData['show_on_desktop']),
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        return redirect()->route('services.landing-page.edit', [
                'service' => $service,
                'landingPage' => $landingPage->id
            ])
            ->with('success', __('general.landing_page_updated_successfully'));
    }

    public function duplicate(ServiceLandingPage $landingPage)
    {
        $this->authorize('update', $landingPage->service);

        // Get the parent (original) page
        $parentPage = $landingPage->parent_variant_id ? $landingPage->parentVariant : $landingPage;

        // Count existing variants to determine next variant name
        $variantCount = $parentPage->variants()->count();
        $variantNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $nextVariantName = $variantNames[$variantCount] ?? chr(65 + $variantCount); // A, B, C, etc.

        // Create duplicate
        $newVariant = $landingPage->replicate();
        // Variants share the same slug as the parent for A/B testing
        $newVariant->slug = $parentPage->slug;
        $newVariant->parent_variant_id = $parentPage->id;
        $newVariant->variant_name = $nextVariantName;
        $newVariant->traffic_split_percentage = 50;
        $newVariant->is_active = false; // Start inactive
        $newVariant->ab_testing_enabled = true;
        $newVariant->save();

        // Duplicate questions
        foreach ($landingPage->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->landing_page_id = $newVariant->id;
            $newQuestion->save();
        }

        // Duplicate FAQs
        foreach ($landingPage->faqs as $faq) {
            $newFaq = $faq->replicate();
            $newFaq->landing_page_id = $newVariant->id;
            $newFaq->save();
        }

        // Duplicate pricing tables
        foreach ($landingPage->pricingTables as $pricing) {
            $newPricing = $pricing->replicate();
            $newPricing->landing_page_id = $newVariant->id;
            $newPricing->save();
        }

        // Duplicate CTA variants
        foreach ($landingPage->ctaVariants as $cta) {
            $newCta = $cta->replicate();
            $newCta->landing_page_id = $newVariant->id;
            $newCta->save();
        }

        return redirect()->route('services.landing-page.edit', ['service' => $landingPage->service, 'landingPage' => $newVariant])
            ->with('success', __('general.variant_nextvariantname_created_successfully_you_can_now_edit_it'));
    }

    /**
     * Get current AI model and API key based on user preference
     */

    /**
     * Call OpenAI API
     */

    private function parseFeatures($features)
    {
        if (empty($features)) {
            return null;
        }

        if (is_string($features)) {
            // Split by newlines and filter empty values
            $featuresArray = array_filter(array_map('trim', explode("\n", $features)));
            return !empty($featuresArray) ? $featuresArray : null;
        }

        return is_array($features) ? $features : null;
    }

    /**
     * Display a landing page to public visitors (with A/B testing)
     */

    /**
     * Track a page view for metrics
     */

    /**
     * Get visitor data for tracking
     */

    /**
     * Track CTA click (AJAX endpoint)
     */

    /**
     * Analytics dashboard for A/B testing results
     */

    /**
     * Track Scroll Depth (AJAX endpoint)
     */
}


