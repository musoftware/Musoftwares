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

class ServiceLandingPagePublicController extends Controller
{

    public function show($slug)
    {
        // Find all landing pages with this slug (parent + variants share the same slug)
        $landingPages = ServiceLandingPage::where('slug', $slug)
            ->where('is_active', true)
            ->with(['service.images', 'questions', 'faqs', 'pricingTables', 'ctaVariants', 'variants', 'parentVariant'])
            ->get();

        if ($landingPages->isEmpty()) {
            abort(404, __('general.landing_page_not_found'));
        }

        // Find the parent page (the one without parent_variant_id)
        $parentPage = $landingPages->firstWhere('parent_variant_id', null);

        if (!$parentPage) {
            abort(404, __('general.landing_page_not_found'));
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
                'currency_id' => 1,
                'features' => ['Basic Feature 1', 'Basic Feature 2', 'Community Support'],
                'cta_text' => 'Start Free',
                'sort_order' => 0
            ]),
            new ServiceLandingPricingTable([
                'plan_name' => 'Professional',
                'description' => 'For growing teams',
                'price' => 79,
                'currency_id' => 1,
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
                'currency_id' => 1,
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
                'field_options' => [(optional($landingPage->service?->currency)->symbol ?: '$') . '0-' . (optional($landingPage->service?->currency)->symbol ?: '$') . '100', (optional($landingPage->service?->currency)->symbol ?: '$') . '100-' . (optional($landingPage->service?->currency)->symbol ?: '$') . '500', (optional($landingPage->service?->currency)->symbol ?: '$') . '500+'],
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


    protected function trackPageView($landingPage, $sessionId)
    {
        $visitorData = $this->getVisitorData();

        \App\Models\ServiceLandingPageAbMetric::trackView(
            $landingPage->id,
            $sessionId,
            $visitorData
        );
    }


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

}
