<?php

namespace App\Http\Controllers;

use App\Models\WebsiteService;
use App\Services\IpGeolocationService;
use App\Services\PricingService;
use App\Services\ProjectEstimatorDataService;
use App\Traits\ConvertsCurrency;
use App\Models\Project;
use App\Services\PortfolioData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Support\PageSeo;
use Inertia\Inertia;

class HomeController extends Controller
{
    use ConvertsCurrency;

    /** Client projects on the homepage: a website, a store, a factory site, two systems and a platform. */
    private const HOME_WORK = ['acelbay', 'umkhalid-mixing', 'alsawan-foods', 'food-lab', 'airtech', 'kbdny'];

    public function index(Request $request, \Modules\SoftwareStore\Services\StoreHighlights $store)
    {
        $isAr = app()->getLocale() === 'ar';

        return view('public.home', [
            'featuredProducts' => $store->featured(),
            'heroShot' => $store->heroShots(1)->first(),
            'clientWork' => PortfolioData::only(self::HOME_WORK),
            'title' => $isAr
                ? 'موسوفت | بنعملك البرنامج أو الموقع أو التطبيق اللي شغلك محتاجه'
                : 'Musoftware | We build the program, website or app your business needs',
            'description' => $isAr
                ? 'شركة برمجة مصرية في السويس. بنعملك البرنامج أو الموقع أو التطبيق اللي شغلك محتاجه، خطوة بخطوة معاك، وبسعر تعرفه قبل ما نبدأ.'
                : 'An Egyptian software company in Suez. We build the program, website or app your business needs, step by step with you, at a price you know before we start.',
        ]);
    }

    public function portfolio()
    {
        $projects = PortfolioData::all();
        $locale = app()->getLocale();

        return view('public.portfolio', [
            'projects' => $projects,
            'locale' => $locale,
        ] + PageSeo::for('portfolio'));
    }

    public function portfolioShow($slug)
    {
        $project = PortfolioData::find($slug);

        if (!$project) {
            // Also check by slugified title if needed
            $all = PortfolioData::all();
            foreach ($all as $item) {
                if (Str::slug($item['title_en']) === $slug || Str::slug($item['slug']) === $slug) {
                    $project = $item;
                    break;
                }
            }
        }

        $project ??= self::caseStudyPage($slug);

        // An unknown address is a 404, not a made-up project page that search engines would index.
        abort_if(! $project, 404);

        $locale = app()->getLocale();
        $title = ($project['title_' . $locale] ?? $project['title_en']).__('seo.suffix');
        $description = Str::limit($project['desc_' . $locale] ?? $project['desc_en'], 160);

        $viewName = view()->exists("public.portfolio.{$slug}") 
            ? "public.portfolio.{$slug}" 
            : 'public.portfolio.layout';

        return view($viewName, [
            'project' => $project,
            'slug' => $slug,
            'locale' => $locale,
            'title' => $title,
            'description' => $description,
        ]);
    }

    /**
     * Projects that have their own written case study view but are not on the portfolio list.
     *
     * @return array<string, mixed>|null
     */
    private static function caseStudyPage(string $slug): ?array
    {
        $pages = [
            'am-sms-gateway' => ['AM SMS Gateway', 'بوابة رسائل AM SMS', 'Send OTP and alert SMS from your own system through Android phones with local SIM cards, with delivery reports.', 'ابعت رسائل التحقق والتنبيهات من نظامك عن طريق موبايلات أندرويد بشرايح محلية، مع تقرير بوصول كل رسالة.', '/images/portfolio/am-sms.jpg'],
            'email-sender' => ['Email Sender', 'منصة إرسال الإيميلات', 'A platform that sends newsletters and alerts from several SMTP servers in turn, cleans bounced addresses and reports opens and clicks.', 'منصة بتبعت النشرات والتنبيهات من أكتر من سيرفر SMTP بالتبادل، وبتشيل العناوين اللي بترجع، وبتقولك مين فتح ومين ضغط.', '/images/portfolio/emailsender.png'],
            'revflow' => ['RevFlow', 'ريف فلو', 'Accounting and stock for traders: every sale, purchase, payment and stock move writes its own balanced journal entry, and closed periods cannot be changed.', 'حسابات ومخازن للتجار: كل بيع وشراء وسداد وحركة مخزن بتعمل قيدها لوحدها، والفترات المقفولة مايتعدلش فيها.', '/images/portfolio/revFlow.png'],
        ];

        if (! isset($pages[$slug]) || ! view()->exists("public.portfolio.{$slug}")) {
            return null;
        }
        [$titleEn, $titleAr, $descEn, $descAr, $img] = $pages[$slug];

        return [
            'slug' => $slug, 'title_en' => $titleEn, 'title_ar' => $titleAr, 'desc_en' => $descEn, 'desc_ar' => $descAr,
            'category' => 'Systems', 'category_ar' => 'أنظمة', 'img' => $img, 'techs' => [], 'live_url' => null,
            'metrics' => [], 'highlights_en' => [], 'highlights_ar' => [],
        ];
    }

    public function services(Request $request)
    {
        return redirect()->route('marketplace.services.index', $request->query());
    }

    public function websiteServiceShow($slug)
    {
        $service = WebsiteService::where('slug', $slug)->firstOrFail();

        $lang = request('lang', app()->getLocale());

        $title = $lang === 'ar' ? ($service->seo_title_ar ?: $service->title_ar) : ($service->seo_title_en ?: $service->title_en);
        $description = $lang === 'ar' ? ($service->seo_description_ar ?: $service->subtitle_ar) : ($service->seo_description_en ?: $service->subtitle_en);

        if (empty($description)) {
            $description = $lang === 'ar' ? strip_tags($service->description_ar) : strip_tags($service->description_en);
        }
        $description = Str::limit($description, 160);

        $imagePath = $lang === 'ar' ? $service->primary_image_ar : $service->primary_image_en;

        return Inertia::render('Public/WebsiteServiceShow', [
            'service' => $service,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => $title.' | Musoftware',
                'description' => $description,
                'image' => $imagePath ? asset($imagePath) : asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    /** Custom systems built around the business, the two ways to work with us, prices and support. */
    public function howWeWork()
    {
        return view('public.how-we-work', PageSeo::for('how_we_work'));
    }






    public function company()
    {
        return redirect()->route('company.about');
    }

    public function companyAbout()
    {
        return view('public.about', PageSeo::for('about'));
    }

    public function leadershipBio()
    {
        return view('public.bio', PageSeo::for('bio'));
    }

    public function startProject()
    {
        return Inertia::render('Public/Wizard/SystemBuilder', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                ...PageSeo::for('start_project'),
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function companyCareers()
    {
        return view('public.careers', PageSeo::for('careers'));
    }

    public function companyContact()
    {
        return view('public.contact', PageSeo::for('contact'));
    }

    public function privacyPolicy()
    {
        return view('public.legal.privacy', array_merge(PageSeo::for('privacy'), [
            'legalInfo' => \App\Support\PrivacySettings::legalInfo(),
        ]));
    }

    public function termsOfService()
    {
        return view('public.legal.terms', PageSeo::for('terms'));
    }

    public function cookiePolicy()
    {
        return view('public.legal.cookies', PageSeo::for('cookies'));
    }

    public function compare($slug = 'laravel-vs-nodejs')
    {
        // There is one comparison; other names would be copies of it under new addresses.
        if ($slug !== 'laravel-vs-nodejs') {
            return redirect()->route('compare.laravel-vs-nodejs', request()->query(), 301);
        }

        return view('public.compare', PageSeo::for('compare'));
    }

    public function pricing()
    {
        return redirect()->route('estimator', [], 301);
    }

    public function estimator(Request $request)
    {
        $estimator = new ProjectEstimatorDataService();
        $rate = $estimator->usdToEgpRate();
        $estimatorData = $estimator->getEstimatorData($rate);

        if ($request->query('format') === 'json' || $request->wantsJson()) {
            return response()->json($estimatorData, 200, ['Content-Type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return Inertia::render('Public/Estimator', array_merge($estimatorData, [
            'exchangeRate' => (float)$rate,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]))->withViewData([
            'meta' => [
                'title' => 'Project Cost & Budget Estimator | Musoftware',
                'description' => 'Calculate your website, mobile app, or desktop software development budget instantly with transparent Khamsat-aligned pricing.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    /**
     * Generate an official quotation reference and save payload in cache.
     */
    public function generateQuotation(Request $request)
    {
        $validated = $request->validate([
            'platform_items' => 'required|array',
            'itemized_addons' => 'nullable|array',
            'subtotal_usd' => 'required|numeric',
            'is_bundle_discount' => 'nullable|boolean',
            'discount_usd' => 'nullable|numeric',
            'total_usd' => 'required|numeric',
            'total_egp' => 'required|numeric',
            'exchange_rate' => 'nullable|numeric',
            'is_usd' => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'client_business' => 'nullable|string|max:255',
            'client_mobile' => 'nullable|string|max:50',
            'client_email' => 'nullable|string|max:255',
            'platforms_summary' => 'nullable|string|max:255',
        ]);

        $code = 'QT-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        $nowCairo = now()->timezone('Africa/Cairo');

        $validated['code'] = $code;
        $validated['cairo_date'] = $nowCairo->format('M d, Y - h:i A') . ' (Cairo Time)';
        $validated['valid_until'] = $nowCairo->addDays(30)->format('M d, Y');
        $validated['exchange_rate'] = $validated['exchange_rate'] ?? 50.0;
        $validated['is_usd'] = $validated['is_usd'] ?? true;

        // Store in cache for 30 days
        Cache::put("quotation:{$code}", $validated, now()->addDays(30));

        return response()->json([
            'success' => true,
            'code' => $code,
            'url' => route('public.quotation.show', ['code' => $code]),
        ]);
    }

    /**
     * Display the official printable corporate quotation Blade view.
     */
    public function showQuotation($code)
    {
        $quote = Cache::get("quotation:{$code}");

        if (!$quote) {
            $filePath = storage_path("app/quotations/{$code}.json");
            if (file_exists($filePath)) {
                $quote = json_decode(file_get_contents($filePath), true);
            }
        }

        if (!$quote && in_array($code, ['demo', 'preview'])) {
            $quote = $this->getDemoQuotationPayload();
        }

        if (!$quote) {
            // Fallback sample quote or 404
            abort(404, 'Quotation not found or has expired.');
        }

        // Formatter closure
        $quote['formatter'] = function($usdVal) use ($quote) {
            if (!($quote['is_usd'] ?? true)) {
                $egp = round($usdVal * ($quote['exchange_rate'] ?? 50.0));
                return number_format($egp) . ' EGP';
            }
            return '$' . number_format($usdVal);
        };

        // WhatsApp direct link
        $phoneNumber = '201015218548';
        $totalDisplay = ($quote['is_usd'] ?? true) 
            ? '$' . number_format($quote['total_usd']) 
            : number_format($quote['total_egp']) . ' EGP';

        $summary = $quote['platforms_summary'] ?? 'Custom Software Infrastructure';
        $whatsappMsg = "Hello Mahmoud, I am reviewing official quotation #{$code} for ({$summary}) totaling {$totalDisplay}. Let's discuss proceeding with this scope!";
        $whatsappUrl = 'https://wa.me/' . $phoneNumber . '?text=' . urlencode($whatsappMsg);

        return view('quotations.show', compact('quote', 'whatsappUrl'));
    }

    /**
     * Download or stream the quotation as an executive PDF document.
     */
    public function downloadQuotationPdf($code)
    {
        $quote = Cache::get("quotation:{$code}");

        if (!$quote) {
            $filePath = storage_path("app/quotations/{$code}.json");
            if (file_exists($filePath)) {
                $quote = json_decode(file_get_contents($filePath), true);
            }
        }

        if (!$quote && in_array($code, ['demo', 'preview'])) {
            $quote = $this->getDemoQuotationPayload();
        }

        if (!$quote) {
            abort(404, 'Quotation not found or has expired.');
        }

        $quote['formatter'] = function($usdVal) use ($quote) {
            if (!($quote['is_usd'] ?? true)) {
                $egp = round($usdVal * ($quote['exchange_rate'] ?? 50.0));
                return number_format($egp) . ' EGP';
            }
            return '$' . number_format($usdVal);
        };

        $whatsappUrl = null;

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('quotations.show', compact('quote', 'whatsappUrl'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'sans-serif',
                ]);

            return $pdf->download("{$code}.pdf");
        }

        return view('quotations.show', compact('quote', 'whatsappUrl'));
    }

    /**
     * Sample executive quotation payload for previews.
     */
    private function getDemoQuotationPayload(): array
    {
        $nowCairo = now()->timezone('Africa/Cairo');

        return [
            'code' => 'QT-DEMO-EXECUTIVE',
            'client_name' => 'Ahmed Rehab',
            'client_business' => 'Hot Sauce Global Enterprise',
            'client_mobile' => '+201015218548',
            'client_email' => 'ahmed@hotsauce.com',
            'platforms_summary' => 'Pepper Sauce Store & Digital Distribution Architecture',
            'platform_items' => [
                [
                    'title' => 'E-Commerce Storefront & Catalog Architecture',
                    'count' => 6,
                    'unit' => 'Page',
                    'rate' => 10,
                    'cost' => 60,
                    'total_usd' => 60,
                ],
                [
                    'title' => 'Mobile Ordering App (iOS & Android)',
                    'count' => 8,
                    'unit' => 'Screen',
                    'rate' => 15,
                    'cost' => 120,
                    'total_usd' => 120,
                ],
                [
                    'title' => 'Desktop POS & Warehouse Inventory Software',
                    'count' => 4,
                    'unit' => 'Screen',
                    'rate' => 25,
                    'cost' => 100,
                    'total_usd' => 100,
                ],
            ],
            'itemized_addons' => [
                [
                    'title' => 'Multi-Currency Gateways (Stripe, PayPal, Fawry)',
                    'cost' => 60,
                    'price_usd' => 60,
                    'desc' => 'Dual-currency ledger architecture with automated real-time exchange rates.',
                ],
                [
                    'title' => 'WhatsApp Order & Dispatch Notification Engine',
                    'cost' => 40,
                    'price_usd' => 40,
                    'desc' => 'Automated invoice and tracking delivery via WhatsApp business API.',
                ],
            ],
            'subtotal_usd' => 380,
            'discount_usd' => 30,
            'total_usd' => 350,
            'total_egp' => 17500,
            'exchange_rate' => 50.0,
            'is_usd' => true,
            'executive_summary' => 'This comprehensive architectural specification details the multi-channel digital retail infrastructure for Pepper Sauce Global Enterprise. Designed to scale seamlessly across international and local markets, the ecosystem couples a high-speed web catalog with cross-platform native mobile applications and an offline-resilient desktop POS warehouse engine.',
            'architectural_approach' => 'The system is organized into decoupled layers: a reactive customer-facing presentation layer, a resilient central API core, and isolated payment and warehouse micro-services. All financial transactions leverage atomic double-entry ledgers with automated exchange rate normalization and secure webhook verification.',
            'tech_stack' => [
                'Laravel 12 (Core API Engine)',
                'React 18 & Inertia.js (Web Frontend)',
                'Tailwind CSS v4 (Design System)',
                'Flutter / Native Bridge (iOS & Android)',
                'Electron / SQLite (Offline POS)',
                'Stripe, PayPal & Local Wallets',
            ],
            'milestones' => [
                [
                    'phase' => 'Phase 01',
                    'title' => 'Architecture, Data Schemas & UI/UX Blueprints',
                    'duration' => 'Weeks 1 - 2',
                    'deliverables' => 'Full relational database modeling, Figma-aligned component library, interactive wireframes, and API contract specifications.',
                ],
                [
                    'phase' => 'Phase 02',
                    'title' => 'Core Platform Engineering & Multi-Gateway Integration',
                    'duration' => 'Weeks 3 - 4',
                    'deliverables' => 'Storefront checkout flows, mobile catalog sync, desktop POS COM-port scanner integration, and automated WhatsApp triggers.',
                ],
                [
                    'phase' => 'Phase 03',
                    'title' => 'Security Audit, Performance Optimization & Cloud Deployment',
                    'duration' => 'Week 5',
                    'deliverables' => 'End-to-end automated test suites, database indexing under high concurrency, SSL/CDN configuration, and production handover.',
                ],
            ],
            'cairo_date' => $nowCairo->format('M d, Y - h:i A') . ' (Cairo Time)',
            'valid_until' => $nowCairo->copy()->addDays(30)->format('M d, Y'),
        ];
    }


    public function customSolutions()
    {
        return Inertia::render('Public/CustomSolutions', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Custom Architecture & Solutions | Musoftware',
                'description' => 'Bespoke software architecture, scalable database engineering, and direct developer communication for complex requirements.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }
}

