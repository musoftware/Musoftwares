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
            ->with('success', 'Landing page created successfully!');
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
                        'currency_code' => $pricingData['currency_code'] ?? 'USD',
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
            ->with('success', 'Landing page updated successfully!');
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
            ->with('success', "Variant {$nextVariantName} created successfully! You can now edit it.");
    }

    public function submitForm(Request $request, $slug)
    {
        // Find the parent landing page (variants share the same slug)
        $landingPage = ServiceLandingPage::where('slug', $slug)
            ->where('is_active', true)
            ->whereNull('parent_variant_id') // Get the parent, not a variant
            ->with(['questions', 'service.user'])
            ->firstOrFail();

        $formData = [];
        $validationRules = [];

        foreach ($landingPage->questions as $question) {
            $fieldName = 'question_' . $question->id;
            if ($question->is_required) {
                $validationRules[$fieldName] = 'required';
            }
        }

        $request->validate($validationRules);

        foreach ($landingPage->questions as $question) {
            $fieldName = 'question_' . $question->id;
            $formData[$question->question_text] = $request->input($fieldName);
        }

        $leadConfig = $landingPage->lead_routing_config ?? [];
        $saveToDb = $leadConfig['save_to_db'] ?? true;
        
        $submission = null;
        if ($saveToDb) {
            $submission = ServiceLandingFormSubmission::create([
                'landing_page_id' => $landingPage->id,
                'form_data' => $formData,
                'submitted_by_name' => $request->input('name'),
                'submitted_by_email' => $request->input('email'),
                'submitted_by_phone' => $request->input('phone'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Send notification to service owner (Email)
        $emailEnabled = $leadConfig['email_notification'] ?? true;
        
        if ($emailEnabled) {
            $service = $landingPage->service;
            if ($service && $service->user) {
                $serviceOwner = $service->user;
                // If saved to DB, link to submission, otherwise link to leads page
                $viewUrl = ($submission) 
                    ? route('services.landing-page.submissions', $service) . '#submission-' . $submission->id
                    : route('services.landing-page.submissions', $service);
                
                // Determine additional recipients (External emails)
                $recipients = [];
                if (!empty($leadConfig['notification_email'])) {
                    $emails = array_map('trim', explode(',', $leadConfig['notification_email']));
                    foreach ($emails as $email) {
                        // We exclude the owner here because we notify them explicitly below
                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== $serviceOwner->email) {
                            $recipients[] = $email;
                        }
                    }
                }
                
                // Prepare Notification
                $notification = new \App\Notifications\NewFormSubmissionNotification(
                    $submission ?? (object)[
                        'form_data' => $formData, 
                        'submitted_by_name' => $request->input('name'),
                        'submitted_by_email' => $request->input('email'),
                        'landing_page_id' => $landingPage->id,
                        'created_at' => now()
                    ], 
                    $landingPage, 
                    $viewUrl
                );

                // 1. Always Notify Owner (triggers database, fcm, mail based on Notification class)
                try {
                    $serviceOwner->notify($notification);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to notify owner ' . $serviceOwner->email . ': ' . $e->getMessage());
                }

                // 2. Send to additional recipients (Mail only)
                foreach ($recipients as $recipientEmail) {
                    try {
                        \Illuminate\Support\Facades\Notification::route('mail', $recipientEmail)
                            ->notify($notification);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send lead notification to ' . $recipientEmail . ': ' . $e->getMessage());
                    }
                }
            }
        }

        // Webhook Integration
        $webhookEnabled = $leadConfig['webhook_enabled'] ?? false;
        $webhookUrl = $leadConfig['webhook_url'] ?? null;

        if ($webhookEnabled && !empty($webhookUrl) && filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
             try {
                \Illuminate\Support\Facades\Http::post($webhookUrl, [
                    'event' => 'form_submission',
                    'landing_page_id' => $landingPage->id,
                    'landing_page_title' => $landingPage->hero_title,
                    'submitted_at' => now()->toIso8601String(),
                    'data' => [
                        'name' => $request->input('name'),
                        'email' => $request->input('email'),
                        'phone' => $request->input('phone'),
                        'fields' => $formData,
                    ]
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Webhook failed for landing page ' . $landingPage->id . ': ' . $e->getMessage());
            }
        }

        // WhatsApp Redirection
        $whatsappEnabled = $leadConfig['whatsapp_enabled'] ?? false;
        $whatsappNumber = $leadConfig['whatsapp_number'] ?? '';

        if ($whatsappEnabled && !empty($whatsappNumber)) {
            // Clean number (remove spaces, dashes, plus)
            $cleanNumber = preg_replace('/[^0-9]/', '', $whatsappNumber);
            
            // Format message
            $message = "New Inquiry : " . $landingPage->hero_title . "\n";
            $message .= "Name: " . $request->input('name') . "\n";
            $message .= "Email: " . $request->input('email') . "\n";
            if ($request->filled('phone')) {
                $message .= "Phone: " . $request->input('phone') . "\n";
            }
            // Add custom fields summary
            $message .= "\nDetails:\n";
            foreach ($formData as $key => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $message .= "$key: $value\n";
            }
            
            $whatsappUrl = "https://wa.me/{$cleanNumber}?text=" . urlencode($message);
            
            // Redirect to WhatsApp
            // We use standard redirect. The frontend might need to handle this if it expects to stay on page,
            // but for "Redirect to WhatsApp", typically the user is taken there.
            return redirect($whatsappUrl);
        }

        return redirect()->back()->with('success', 'Thank you! Your form has been submitted successfully.');
    }

    public function submissions(Service $service, Request $request)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;

        if (!$landingPage) {
            return redirect()->route('services.mine')
                ->with('error', 'No landing page found for this service.');
        }

        $query = $landingPage->formSubmissions();

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('submitted_by_name', 'like', "%{$search}%")
                  ->orWhere('submitted_by_email', 'like', "%{$search}%")
                  ->orWhere('submitted_by_phone', 'like', "%{$search}%");
            });
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $submissions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return Inertia::render('Marketplace/Seller/LandingPages/Submissions', ['service' => $service, 'landingPage' => $landingPage, 'submissions' => $submissions]);
    }

    public function destroySubmission(Service $service, ServiceLandingFormSubmission $submission)
    {
        $this->authorize('update', $service);

        // Verify the submission belongs to the service's landing page
        $landingPage = $service->landingPage;
        if (!$landingPage || $submission->landing_page_id !== $landingPage->id) {
            return redirect()->route('services.landing-page.submissions', $service)
                ->with('error', 'Submission not found or access denied.');
        }

        $submissionName = $submission->submitted_by_name ?? 'Unknown';
        $submission->delete();

        return redirect()->route('services.landing-page.submissions', $service)
            ->with('success', "Submission from '{$submissionName}' has been deleted successfully.");
    }

    public function exportSubmissions(Service $service, Request $request)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;

        if (!$landingPage) {
            return redirect()->route('services.mine')
                ->with('error', 'No landing page found for this service.');
        }

        $query = $landingPage->formSubmissions();

        // Apply same filters as submissions method
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('submitted_by_name', 'like', "%{$search}%")
                  ->orWhere('submitted_by_email', 'like', "%{$search}%")
                  ->orWhere('submitted_by_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        $filename = 'submissions_' . $service->slug . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return Response::stream(function () use ($submissions) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'ID',
                'Submitted By',
                'Email',
                'Phone',
                'Submitted At',
                'IP Address',
                'User Agent',
                'Form Answers (JSON)'
            ]);

            // Data rows
            foreach ($submissions as $submission) {
                $formDataJson = !empty($submission->form_data)
                    ? json_encode($submission->form_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : '';

                fputcsv($file, [
                    $submission->id,
                    $submission->submitted_by_name ?? '',
                    $submission->submitted_by_email ?? '',
                    $submission->submitted_by_phone ?? '',
                    $submission->created_at->format('Y-m-d H:i:s'),
                    $submission->ip_address ?? '',
                    $submission->user_agent ?? '',
                    $formDataJson
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    public function generateQuestions(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;
        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found. Please create one first.'
            ], 404);
        }

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $request->input('service_title', $service->title);
            $heroTitle = $request->input('hero_title', $landingPage->hero_title);
            $heroDescription = $request->input('hero_description', $landingPage->hero_description);
            $description = $request->input('description', $landingPage->description);

            // Build prompt for generating form questions
            $prompt = "You are an expert in creating effective landing page forms. Based on the following service information, generate 5-8 relevant form questions that would help collect useful information from potential customers.\n\n";
            $prompt .= "Service Title: {$serviceTitle}\n";
            $prompt .= "Hero Title: {$heroTitle}\n";
            if ($heroDescription) {
                $prompt .= "Hero Description: {$heroDescription}\n";
            }
            if ($description) {
                $prompt .= "Service Description: " . strip_tags($description) . "\n";
            }
            $prompt .= "\nGenerate form questions that are:\n";
            $prompt .= "1. Relevant to the service\n";
            $prompt .= "2. Helpful for understanding customer needs\n";
            $prompt .= "3. Appropriate field types (text, email, phone, textarea, select, etc.)\n";
            $prompt .= "4. Include helpful placeholders and help text when appropriate\n\n";
            $prompt .= "Return ONLY a valid JSON array in this exact format:\n";
            $prompt .= "[\n";
            $prompt .= "  {\n";
            $prompt .= "    \"question_text\": \"What is your budget range?\",\n";
            $prompt .= "    \"field_type\": \"select\",\n";
            $prompt .= "    \"is_required\": true,\n";
            $prompt .= "    \"placeholder\": \"Select your budget\",\n";
            $prompt .= "    \"help_text\": \"This helps us provide the best solution for your needs\",\n";
            $prompt .= "    \"field_options\": [\"Under $500\", \"$500-$1000\", \"$1000-$5000\", \"$5000+\"]\n";
            $prompt .= "  }\n";
            $prompt .= "]\n\n";
            $prompt .= "Field types can be: text, textarea, email, phone, number, date, select, radio, checkbox";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $questions = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format questions
            $formattedQuestions = [];
            foreach ($questions as $question) {
                if (isset($question['question_text']) && !empty($question['question_text'])) {
                    $formattedQuestions[] = [
                        'question_text' => $question['question_text'],
                        'field_type' => $question['field_type'] ?? 'text',
                        'is_required' => $question['is_required'] ?? false,
                        'placeholder' => $question['placeholder'] ?? null,
                        'help_text' => $question['help_text'] ?? null,
                        'field_options' => $question['field_options'] ?? null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'questions' => $formattedQuestions,
                'message' => 'Questions generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI Question Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateFAQs(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;
        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found. Please create one first.'
            ], 404);
        }

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $request->input('service_title', $service->title);
            $heroTitle = $request->input('hero_title', $landingPage->hero_title);
            $heroDescription = $request->input('hero_description', $landingPage->hero_description);
            $description = $request->input('description', $landingPage->description);

            // Build prompt for generating FAQs
            $prompt = "You are an expert in creating helpful FAQ sections for landing pages. Based on the following service information, generate 6-10 relevant and helpful FAQs that potential customers would likely ask.\n\n";
            $prompt .= "Service Title: {$serviceTitle}\n";
            $prompt .= "Hero Title: {$heroTitle}\n";
            if ($heroDescription) {
                $prompt .= "Hero Description: {$heroDescription}\n";
            }
            if ($description) {
                $prompt .= "Service Description: " . strip_tags($description) . "\n";
            }
            $prompt .= "\nGenerate FAQs that:\n";
            $prompt .= "1. Address common customer concerns and questions\n";
            $prompt .= "2. Are relevant to the specific service\n";
            $prompt .= "3. Have clear, helpful, and concise answers\n";
            $prompt .= "4. Cover topics like pricing, process, timeline, guarantees, etc.\n\n";
            $prompt .= "Return ONLY a valid JSON array in this exact format:\n";
            $prompt .= "[\n";
            $prompt .= "  {\n";
            $prompt .= "    \"question\": \"How long does it take to complete a project?\",\n";
            $prompt .= "    \"answer\": \"The timeline depends on the project scope and complexity. Typically, projects range from 2-8 weeks. We'll provide a detailed timeline after reviewing your specific requirements.\"\n";
            $prompt .= "  }\n";
            $prompt .= "]\n\n";
            $prompt .= "Make sure answers are informative, professional, and helpful.";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $faqs = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($faqs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format FAQs
            $formattedFAQs = [];
            foreach ($faqs as $faq) {
                if (isset($faq['question']) && !empty($faq['question']) &&
                    isset($faq['answer']) && !empty($faq['answer'])) {
                    $formattedFAQs[] = [
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'faqs' => $formattedFAQs,
                'message' => 'FAQs generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI FAQ Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generatePricingTables(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $landingPage = $service->landingPage;
        if (!$landingPage) {
            return response()->json([
                'success' => false,
                'message' => 'Landing page not found. Please create one first.'
            ], 404);
        }

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $request->input('service_title', $service->title);
            $servicePrice = $service->price ?? 0;
            // Get currency code from Currency model
            $serviceCurrency = null;
            if ($service->currency) {
                $currencyModel = \App\Models\Currency::find($service->currency);
                $serviceCurrency = $currencyModel ? $currencyModel->currency : 'USD';
            }
            $heroTitle = $request->input('hero_title', $landingPage->hero_title);
            $heroDescription = $request->input('hero_description', $landingPage->hero_description);
            $description = $request->input('description', $landingPage->description);

            // Build prompt for generating pricing tables
            $prompt = "You are an expert in creating effective pricing tables for landing pages. Based on the following service information, generate 2-4 pricing plans that are competitive, clear, and conversion-focused.\n\n";
            $prompt .= "Service Information:\n";
            $prompt .= "Service Title: {$serviceTitle}\n";
            if ($heroTitle) {
                $prompt .= "Hero Title: {$heroTitle}\n";
            }
            if ($heroDescription) {
                $prompt .= "Hero Description: {$heroDescription}\n";
            }
            if ($description) {
                $prompt .= "Service Description: " . strip_tags($description) . "\n";
            }
            if ($servicePrice > 0) {
                $prompt .= "Base Price: {$servicePrice}\n";
            }
            if ($serviceCurrency) {
                $prompt .= "Currency: {$serviceCurrency}\n";
            }
            $prompt .= "\nGenerate pricing plans that:\n";
            $prompt .= "1. Are relevant to the service type\n";
            $prompt .= "2. Include clear value propositions\n";
            $prompt .= "3. Have appropriate pricing tiers (starter, professional, enterprise, etc.)\n";
            $prompt .= "4. Include 4-8 features per plan\n";
            $prompt .= "5. One plan should be marked as popular/recommended\n";
            $prompt .= "6. Use appropriate pricing periods (per month, one-time, per year, etc.)\n\n";
            $prompt .= "Return ONLY a valid JSON array in this exact format:\n";
            $prompt .= "[\n";
            $prompt .= "  {\n";
            $prompt .= "    \"plan_name\": \"Starter Plan\",\n";
            $prompt .= "    \"description\": \"Perfect for individuals getting started\",\n";
            $prompt .= "    \"price\": 29.99,\n";
            $prompt .= "    \"currency_code\": \"USD\",\n";
            $prompt .= "    \"period\": \"per month\",\n";
            $prompt .= "    \"features\": [\"Feature 1\", \"Feature 2\", \"Feature 3\"],\n";
            $prompt .= "    \"is_popular\": false,\n";
            $prompt .= "    \"cta_text\": \"Get Started\"\n";
            $prompt .= "  },\n";
            $prompt .= "  {\n";
            $prompt .= "    \"plan_name\": \"Professional Plan\",\n";
            $prompt .= "    \"description\": \"Best for growing businesses\",\n";
            $prompt .= "    \"price\": 99.99,\n";
            $prompt .= "    \"currency_code\": \"USD\",\n";
            $prompt .= "    \"period\": \"per month\",\n";
            $prompt .= "    \"features\": [\"All Starter features\", \"Advanced Feature 1\", \"Advanced Feature 2\"],\n";
            $prompt .= "    \"is_popular\": true,\n";
            $prompt .= "    \"cta_text\": \"Get Started\"\n";
            $prompt .= "  }\n";
            $prompt .= "]\n\n";
            $prompt .= "Make sure pricing is realistic, competitive, and appropriate for the service type. Include clear, valuable features that differentiate each tier.";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $pricingTables = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($pricingTables)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format pricing tables
            $formattedPricing = [];
            foreach ($pricingTables as $pricing) {
                if (isset($pricing['plan_name']) && !empty($pricing['plan_name'])) {
                    $formattedPricing[] = [
                        'plan_name' => $pricing['plan_name'],
                        'description' => $pricing['description'] ?? null,
                        'price' => $pricing['price'] ?? 0,
                        'currency_code' => $pricing['currency_code'] ?? 'USD',
                        'period' => $pricing['period'] ?? null,
                        'features' => $pricing['features'] ?? [],
                        'is_popular' => $pricing['is_popular'] ?? false,
                        'cta_text' => $pricing['cta_text'] ?? 'Get Started',
                        'cta_link' => $pricing['cta_link'] ?? null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'pricing_tables' => $formattedPricing,
                'message' => 'Pricing tables generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI Pricing Table Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    private function callGeminiAPI($apiKey, $model, $prompt)
    {
        $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
        $endpoint = "{$baseUrl}/{$model}:generateContent?key={$apiKey}";

        $contents = [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ];

        return Http::timeout(300)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, ['contents' => $contents]);
    }

    public function generateLandingPageContent(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $modelInfo = $this->getCurrentModelAndKey();
        if (isset($modelInfo['error'])) {
            return response()->json([
                'success' => false,
                'message' => $modelInfo['error']
            ], 400);
        }

        try {
            $serviceTitle = $service->title;
            $serviceDescription = strip_tags($service->description ?? '');
            $serviceImage = $service->image ? asset($service->image) : '';
            $servicePrice = $service->price ?? 0;
            $serviceCategory = $service->category ?? '';

            // Check if this is a specific rewrite request
            if ($request->input('prompt_type') === 'rewrite') {
                $currentText = $request->input('current_text');
                $fieldName = $request->input('field_name');
                
                $prompt = "You are a professional copywriter. Rewrite the following text to be more persuasive, clear, and conversion-focused. Maintain the original meaning but improve the style/tone.";
                if ($fieldName === 'hero_title') {
                    $prompt .= " This is a Hero Title (max 80 chars). Make it punchy and benefit-driven.";
                } elseif ($fieldName === 'hero_description') {
                    $prompt .= " This is a Hero Description (150-200 chars). Focus on value proposition.";
                }
                
                $prompt .= "\n\nOriginal Text:\n\"{$currentText}\"\n\n";
                $prompt .= "Return ONLY the rewritten text (no quotes, no preamble).";

                if ($modelInfo['provider'] === 'openai') {
                    $contentResult = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                    $rewrittenText = $contentResult['content'] ?? '';
                    $error = $contentResult['error'] ?? null;
                } else {
                    $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                    if ($response->failed()) {
                        $error = $response->json()['error']['message'] ?? 'API Error';
                    } else {
                        $rewrittenText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    }
                }

                if (isset($error) || empty($rewrittenText)) {
                    return response()->json([
                        'success' => false,
                        'message' => $error ?? 'Failed to generate text'
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'content' => trim($rewrittenText)
                ]);
            }

            // Build prompt for generating complete landing page content
            $prompt = "You are an expert in creating high-converting landing pages. Based on the following service information, generate optimized landing page content that is compelling, SEO-friendly, and conversion-focused.\n\n";
            $prompt .= "Service Information:\n";
            $prompt .= "Title: {$serviceTitle}\n";
            if ($serviceDescription) {
                $prompt .= "Description: {$serviceDescription}\n";
            }
            if ($servicePrice > 0) {
                $prompt .= "Price: {$servicePrice}\n";
            }
            if ($serviceCategory) {
                $prompt .= "Category: {$serviceCategory}\n";
            }
            $prompt .= "\nGenerate optimized landing page content with the following requirements:\n";
            $prompt .= "1. Slug: Create an SEO-friendly URL slug (lowercase, hyphens instead of spaces, no special characters, 3-8 words max). Make it memorable and descriptive of the service. Example: \"professional-web-design-services\" or \"affordable-seo-consulting\"\n";
            $prompt .= "2. Hero Title: Create a compelling, attention-grabbing headline (max 80 characters) that highlights the main value proposition\n";
            $prompt .= "3. Hero Description: Write a concise, persuasive description (150-200 characters) that explains the key benefits\n";
            $prompt .= "4. Description: Create a detailed, engaging description (HTML format) that explains the service, its benefits, features, and why customers should choose it. Make it professional and conversion-focused.\n";
            $prompt .= "5. Meta Title: SEO-optimized title (50-60 characters) for search engines\n";
            $prompt .= "6. Meta Description: SEO-optimized description (150-160 characters) for search engines\n";
            $prompt .= "7. Meta Keywords: Relevant keywords (comma-separated, 5-10 keywords)\n";
            $prompt .= "8. OG Title: Social media optimized title for Open Graph (max 100 characters)\n";
            $prompt .= "9. OG Description: Social media optimized description for Open Graph (max 300 characters)\n";
            $prompt .= "10. Twitter Title: Twitter-optimized title (max 70 characters)\n";
            $prompt .= "11. Twitter Description: Twitter-optimized description (max 200 characters)\n\n";
            $prompt .= "Return ONLY a valid JSON object in this exact format:\n";
            $prompt .= "{\n";
            $prompt .= "  \"slug\": \"seo-friendly-url-slug\",\n";
            $prompt .= "  \"hero_title\": \"Compelling headline here\",\n";
            $prompt .= "  \"hero_description\": \"Short compelling description\",\n";
            $prompt .= "  \"description\": \"<p>Detailed HTML description with formatting</p>\",\n";
            $prompt .= "  \"meta_title\": \"SEO optimized title\",\n";
            $prompt .= "  \"meta_description\": \"SEO optimized description\",\n";
            $prompt .= "  \"meta_keywords\": \"keyword1, keyword2, keyword3\",\n";
            $prompt .= "  \"og_title\": \"Social media title\",\n";
            $prompt .= "  \"og_description\": \"Social media description\",\n";
            $prompt .= "  \"twitter_title\": \"Twitter title\",\n";
            $prompt .= "  \"twitter_description\": \"Twitter description\"\n";
            $prompt .= "}\n\n";
            $prompt .= "Make sure all content is professional, engaging, and optimized for conversions and SEO. The slug must be URL-friendly (lowercase, hyphens, no spaces or special characters).";

            // Call appropriate API based on user preference
            if ($modelInfo['provider'] === 'openai') {
                $content = $this->callOpenAIAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if (isset($content['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $content['error']
                    ], 500);
                }
                $content = $content['content'] ?? '';
            } else {
                $response = $this->callGeminiAPI($modelInfo['api_key'], $modelInfo['model'], $prompt);
                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMessage = $response->json()['error']['message'] ?? 'Unknown API error';
                    return response()->json([
                        'success' => false,
                        'message' => "AI API Error ($statusCode): $errorMessage"
                    ], 500);
                }
                $aiResponse = $response->json();
                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
            }

            // Clean and parse JSON
            $cleanContent = preg_replace('/```json\s*/', '', $content);
            $cleanContent = preg_replace('/```\s*/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $landingPageData = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($landingPageData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response. Please try again.'
                ], 500);
            }

            // Validate and format the data
            // Clean and sanitize the AI-generated slug
            $aiSlug = $landingPageData['slug'] ?? '';
            if (!empty($aiSlug)) {
                // Ensure slug is URL-friendly: lowercase, replace spaces/special chars with hyphens
                $aiSlug = Str::slug($aiSlug);
            }
            // Fallback to service title slug if AI didn't generate one or it's invalid
            if (empty($aiSlug)) {
                $aiSlug = Str::slug($serviceTitle);
            }

            $formattedData = [
                'slug' => $aiSlug,
                'hero_title' => $landingPageData['hero_title'] ?? $serviceTitle,
                'hero_description' => $landingPageData['hero_description'] ?? '',
                'hero_cta_text' => 'Get Started',
                'description' => $landingPageData['description'] ?? $service->description ?? '',
                'meta_title' => $landingPageData['meta_title'] ?? '',
                'meta_description' => $landingPageData['meta_description'] ?? '',
                'meta_keywords' => $landingPageData['meta_keywords'] ?? '',
                'og_title' => $landingPageData['og_title'] ?? '',
                'og_description' => $landingPageData['og_description'] ?? '',
                'og_image' => $serviceImage,
                'twitter_title' => $landingPageData['twitter_title'] ?? '',
                'twitter_description' => $landingPageData['twitter_description'] ?? '',
                'twitter_image' => $serviceImage,
                'twitter_card_type' => 'summary_large_image',
                'robots' => 'index, follow',
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'message' => 'Landing page content generated successfully using ' . strtoupper($modelInfo['model']) . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI Landing Page Content Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current AI model and API key based on user preference
     */
    private function getCurrentModelAndKey(): array
    {
        $user = Auth::user();
        $defaultProvider = $user->default_ai_model ?? 'gemini';

        if ($defaultProvider === 'openai') {
            $apiKey = $user->openai_api_key ?? null;
            if (empty($apiKey)) {
                return [
                    'error' => 'OpenAI API key is required. Please set it in your profile settings first.'
                ];
            }
            $model = $user->openai_model ?? 'gpt-4o-mini';
            return ['provider' => 'openai', 'model' => $model, 'api_key' => $apiKey];
        } else {
            $apiKey = $user->gemini_api ?? null;
            if (empty($apiKey)) {
                return [
                    'error' => 'Gemini API key is required. Please set it in your profile settings first.'
                ];
            }
            $model = $user->gemini_model ?? 'gemini-2.0-flash';
            return ['provider' => 'gemini', 'model' => $model, 'api_key' => $apiKey];
        }
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAIAPI(string $apiKey, string $model, string $prompt): array
    {
        $systemPrompt = "You are an expert in creating high-converting landing pages. Your task is to generate professional, detailed landing page content based on service information.";
        $userMessage = $prompt;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ];

        try {
            $response = Http::timeout(300)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 4000,
                ]);
        } catch (\Throwable $e) {
            return ['error' => 'Network Error: ' . $e->getMessage()];
        }

        if ($response->failed()) {
            $statusCode = $response->status();
            $json = $response->json();
            $errorMessage = $json['error']['message'] ?? 'Unknown API error';

            if ($statusCode === 401 || $statusCode === 403) {
                $errorMessage .= ' (Check API key / model access)';
            } elseif ($statusCode === 429) {
                $errorMessage .= ' (Rate limited: try again)';
            }

            return ['error' => "API Error ($statusCode): $errorMessage"];
        }

        $aiResponse = $response->json();
        $content = $aiResponse['choices'][0]['message']['content'] ?? 'No AI response';

        return ['content' => trim($content)];
    }

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
    public function show($slug)
    {
        // Find all landing pages with this slug (parent + variants share the same slug)
        $landingPages = ServiceLandingPage::where('slug', $slug)
            ->where('is_active', true)
            ->with(['service.images', 'questions', 'faqs', 'pricingTables', 'ctaVariants', 'variants', 'parentVariant'])
            ->get();

        if ($landingPages->isEmpty()) {
            abort(404, 'Landing page not found');
        }

        // Find the parent page (the one without parent_variant_id)
        $parentPage = $landingPages->firstWhere('parent_variant_id', null);

        if (!$parentPage) {
            abort(404, 'Landing page not found');
        }

        // Select which variant to show (if A/B testing is enabled)
        $selectedVariant = $parentPage->selectVariant();

        // Generate or get session ID for tracking
        $sessionId = session()->getId();
        if (!session()->has('landing_page_session_' . $parentPage->id)) {
            session()->put('landing_page_session_' . $parentPage->id, $sessionId);
        }

        // Track the view
        $this->trackPageView($selectedVariant, $sessionId);

        // Check for auto-winner selection
        if ($parentPage->shouldAutoSelectWinner()) {
            $parentPage->determineWinner();
        }

        // Get active CTAs for this variant
        $activeCtaVariants = $selectedVariant->ctaVariants()
            ->active()
            ->ordered()
            ->get()
            ->filter(function ($cta) {
                return $cta->shouldShowToVisitor($this->getVisitorData());
            });

        return view('services.landing-page.show', [
            'landingPage' => $selectedVariant,
            'service' => $selectedVariant->service,
            'activeCtaVariants' => $activeCtaVariants,
        ]);
    }

    /**
     * Track a page view for metrics
     */
    protected function trackPageView($landingPage, $sessionId)
    {
        $visitorData = $this->getVisitorData();

        \App\Models\ServiceLandingPageAbMetric::trackView(
            $landingPage->id,
            $sessionId,
            $visitorData
        );
    }

    /**
     * Get visitor data for tracking
     */
    protected function getVisitorData()
    {
        $userAgent = request()->userAgent();
        $isMobile = preg_match('/(android|mobile|tablet|ipad|ipod)/i', $userAgent);
        $isTablet = preg_match('/(tablet|ipad)/i', $userAgent);

        return [
            'visitor_ip' => request()->ip(),
            'user_agent' => $userAgent,
            'is_mobile' => $isMobile,
            'is_tablet' => $isTablet,
            'device_type' => $isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop'),
            'referrer_url' => request()->header('referer'),
            'utm_source' => request()->get('utm_source'),
            'utm_medium' => request()->get('utm_medium'),
            'utm_campaign' => request()->get('utm_campaign'),
            'utm_term' => request()->get('utm_term'),
            'utm_content' => request()->get('utm_content'),
            'language' => request()->getPreferredLanguage(),
        ];
    }

    /**
     * Track CTA click (AJAX endpoint)
     */
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

    /**
     * Analytics dashboard for A/B testing results
     */
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
    public function previewTemplate($template)
    {
        // validate template
        $allowedTemplates = ['modern', 'creative', 'business', 'product', 'minimal', 'dashboard', 'glassmorphism', 'neumorphism', 'skeuomorphism', 'flat-design', 'material-design', 'fluent-design', 'ecommerce', 'cyberpunk', 'gaming', 'dark-mode', 'brutalism', 'retro', 'pastel'];
        
        if (!in_array($template, $allowedTemplates)) {
           $template = 'modern';
        }

        // Create a dummy service
        $service = new Service([
            'title' => 'Sample Service Name',
            'description' => 'This is a sample service description for preview purposes.',
             // Use a placeholder if no image
            'image' => null, 
        ]);
        // Mock the user relation for the service if viewed in 'show'
        $service->setRelation('user', Auth::user() ?? new \App\Models\User(['name' => 'Demo User']));
        $service->setRelation('images', collect([]));
        $service->setRelation('approvedReviews', collect([]));

        // Create a dummy landing page
        $landingPage = new ServiceLandingPage([
            'hero_title' => 'Your Awesome Headline Here',
            'hero_description' => 'This is your compelling subheadline. It explains the value proposition clearly and concisely to your visitors.',
            'hero_cta_text' => 'Get Started Now',
            'description' => '<h2>Why Choose Us?</h2><p>This is where you explain your service in detail. Highlight the key benefits and features that make your offering unique.</p><ul><li>Benefit 1: Speed and Efficiency</li><li>Benefit 2: Quality Assurance</li><li>Benefit 3: Customer Satisfaction</li></ul>',
            'template' => $template,
            'slug' => 'preview-template',
            'is_active' => true,
            'meta_title' => 'Preview Page',
            'meta_description' => 'This is a preview of the landing page template.',
        ]);
        
        $landingPage->setRelation('service', $service);
        $landingPage->id = 0; // Fake ID

        // Dummy pricing tables
        $pricingTables = collect([
            new ServiceLandingPricingTable([
                'plan_name' => 'Starter',
                'description' => 'For individuals',
                'price' => 29,
                'currency_code' => 'USD',
                'features' => ['Basic Feature 1', 'Basic Feature 2', 'Community Support'],
                'cta_text' => 'Start Free',
                'sort_order' => 0
            ]),
            new ServiceLandingPricingTable([
                'plan_name' => 'Professional',
                'description' => 'For growing teams',
                'price' => 79,
                'currency_code' => 'USD',
                'period' => '/month',
                'features' => ['All Starter Features', 'Advanced Analytics', 'Priority Support', 'Custom Branding'],
                'cta_text' => 'Get Pro',
                'is_popular' => true,
                'sort_order' => 1
            ]),
            new ServiceLandingPricingTable([
                'plan_name' => 'Enterprise',
                'description' => 'For large organizations',
                'price' => 199,
                'currency_code' => 'USD',
                'period' => '/month',
                'features' => ['All Pro Features', 'Dedicated Account Manager', 'SSO Integration', 'SLA Agreement'],
                'cta_text' => 'Contact Sales',
                'sort_order' => 2
            ])
        ]);
        $landingPage->setRelation('pricingTables', $pricingTables);
        
        // Dummy FAQs
         $faqs = collect([
            new ServiceLandingFaq([
                'question' => 'How does the free trial work?',
                'answer' => 'You can try our service for free for 14 days. No credit card required.',
                'sort_order' => 0
            ]),
            new ServiceLandingFaq([
                'question' => 'Can I cancel anytime?',
                'answer' => 'Yes, you can cancel your subscription at any time from your account settings.',
                'sort_order' => 1
            ]),
            new ServiceLandingFaq([
                'question' => 'Do you offer refunds?',
                'answer' => 'We offer a 30-day money-back guarantee if you are not satisfied with our service.',
                'sort_order' => 2
            ])
        ]);
        $landingPage->setRelation('faqs', $faqs);
        
        // Dummy Questions (for form)
         $questions = collect([
            new ServiceLandingQuestion([
                'question_text' => 'What is your budget?',
                'field_type' => 'select',
                'field_options' => ['$0-$100', '$100-$500', '$500+'],
                'is_required' => true,
                'sort_order' => 0
            ])
        ]);
        $landingPage->setRelation('questions', $questions);


        // Dummy text for other relations to avoid errors
        $landingPage->setRelation('variants', collect([]));
        $landingPage->setRelation('ctaVariants', collect([]));

        // Active variants for view
        $activeCtaVariants = collect([]);

        // Pass 'isPreview' => true to handle any specific view logic for previews
        return view('services.landing-page.show', [
            'landingPage' => $landingPage,
            'service' => $service,
            'activeCtaVariants' => $activeCtaVariants,
            'isPreview' => true
        ]);
    }

    /**
     * Track Scroll Depth (AJAX endpoint)
     */
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
}

