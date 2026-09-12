<?php

namespace App\Http\Controllers;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
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
use Inertia\Inertia;

class HomeController extends Controller
{
    use ConvertsCurrency;

    public function index(Request $request, \App\Services\NewsAggregatorService $newsService)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $newsFeed = $newsService->getFeaturedNewsFeed();

        $dbProjects = Project::landingPortfolio()->get()->map(function($project) {
            return [
                'slug' => Str::slug($project->portfolio_title ?? $project->project_name),
                'img' => $project->portfolio_image ? asset($project->portfolio_image) : null,
                'img_original' => is_array($project->portfolio_gallery) && count($project->portfolio_gallery) > 0 
                    ? asset($project->portfolio_gallery[0]) 
                    : ($project->portfolio_image ? asset($project->portfolio_image) : null),
                'title' => $project->portfolio_title ?? $project->project_name,
                'desc' => $project->portfolio_description ?? $project->description,
                'cat' => $project->portfolio_category ?? 'Platform',
                'live_url' => $project->portfolio_live_url,
                'github_url' => $project->portfolio_github_url,
                'techs' => $project->portfolio_tech ?? [],
                'is_db' => true,
            ];
        })->toArray();

        return view('public.home', [
            'dbProjects' => $dbProjects,
            'newsFeed' => $newsFeed,
            'title' => app()->getLocale() === 'ar' 
                ? 'ميوسوفت ويرز | أنظمة سحابية وبرمجة برامج الشركات ERP ومواقع سريعة' 
                : 'Musoftwares | Enterprise Cloud Systems, ERP & Web Platforms',
            'description' => app()->getLocale() === 'ar'
                ? 'استوديو برمجيات متخصص في بناء أنظمة الـ ERP والحسابات، المتاجر والمواقع السريعة، وأتمتة الواتساب. مقرنا بالسويس ونشحن لعملائنا عالمياً.'
                : 'Boutique software engineering studio crafting enterprise ERP engines, high-speed web platforms, and WhatsApp Cloud APIs in Suez, Egypt.',
        ]);
    }

    public function portfolio()
    {
        $projects = PortfolioData::all();
        $locale = app()->getLocale();

        return view('public.portfolio', [
            'projects' => $projects,
            'locale' => $locale,
            'title' => 'Case Studies & Production Platforms | Musoftwares',
            'description' => 'Explore our portfolio of over 30 production platforms, enterprise ERP engines, and custom SaaS systems.',
        ]);
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

        if (!$project) {
            $project = [
                'slug' => $slug,
                'title_en' => ucwords(str_replace('-', ' ', $slug)) . ' System',
                'title_ar' => 'نظام ' . ucwords(str_replace('-', ' ', $slug)),
                'category' => 'SaaS & Cloud',
                'category_ar' => 'منصات سحابية SaaS',
                'desc_en' => 'Custom enterprise software engineered and deployed by Musoftwares.',
                'desc_ar' => 'نظام برمجي مخصص تم تصميمه وتطويره بواسطة استوديو Musoftwares.',
                'img' => '/images/portfolio/musoftwares.png',
                'techs' => ['Laravel', 'PostgreSQL', 'TailwindCSS'],
                'live_url' => null,
                'metrics' => [
                    'Architecture' => 'Custom Production Platform',
                    'Latency' => '< 10ms',
                    'Ownership' => '100% Full Source Code',
                ],
                'highlights_en' => [
                    'Bespoke software architecture tailored to operational workflows.',
                    'Zero lock-in with direct client source code deployment.',
                ],
                'highlights_ar' => [
                    'بنية برمجية مخصصة بالكامل لمتطلبات ودورة عمل العميل.',
                    'ملكية كاملة للكود المصدري دون أي قيود.',
                ],
            ];
        }

        $locale = app()->getLocale();
        $title = ($project['title_' . $locale] ?? $project['title_en']) . ' | Musoftwares';
        $description = $project['desc_' . $locale] ?? $project['desc_en'];

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

    public function platforms()
    {
        return view('public.platforms', [
            'title' => 'Digital Architecture & Business Platforms | Musoftwares',
            'description' => 'Scalable digital platforms engineered for longevity, mission-critical ERP engines, and high-performance business infrastructure in Suez, Egypt.',
        ]);
    }

    public function platformCrm()
    {
        return view('public.platforms.crm', [
            'title' => 'WhatsApp Cloud API & CRM Platform | Musoftwares',
            'description' => 'Direct Meta WhatsApp Cloud API integration, transactional OTP notifications, multi-agent unified inbox, and automated bots.',
        ]);
    }

    public function platformErp()
    {
        return view('public.platforms.erp', [
            'title' => 'Enterprise ERP & Double-Entry Accounting | Musoftwares',
            'description' => 'Custom double-entry accounting ledgers, multi-warehouse stock management, and ETA / ZATCA e-invoicing compliance.',
        ]);
    }

    public function platformCloud()
    {
        return redirect()->route('platforms');
    }

    public function solutions()
    {
        return view('public.solutions', [
            'title' => 'Tailored Software Solutions | Musoftwares',
            'description' => 'Bespoke software engines tailored for healthcare, e-commerce, real estate, and finance workflows.',
        ]);
    }

    public function solutionHealthcare()
    {
        return view('public.solutions', [
            'title' => 'Healthcare & Clinic Solutions | Musoftwares',
            'description' => 'Advanced digital health and clinic management software solutions.',
        ]);
    }

    public function solutionEducation()
    {
        return view('public.solutions', [
            'title' => 'Education & Academy Platforms | Musoftwares',
            'description' => 'Innovative e-learning platforms and academy management systems.',
        ]);
    }

    public function solutionEcommerce()
    {
        return view('public.solutions', [
            'title' => 'E-commerce & Multi-Vendor Platforms | Musoftwares',
            'description' => 'Scalable online stores and multi-vendor marketplace solutions.',
        ]);
    }

    public function solutionRealEstate()
    {
        return view('public.solutions', [
            'title' => 'Real Estate Platforms | Musoftwares',
            'description' => 'Property management and real estate listing platforms.',
        ]);
    }

    public function solutionFinance()
    {
        return view('public.solutions', [
            'title' => 'Financial Software & Ledgers | Musoftwares',
            'description' => 'Secure financial software, accounting tools, and fintech solutions.',
        ]);
    }

    public function company()
    {
        return redirect()->route('company.about');
    }

    public function companyAbout()
    {
        return view('public.about', [
            'title' => 'About Us — Boutique Software Engineering Studio | Musoftwares',
            'description' => 'Musoftwares is an elite software engineering studio crafting bespoke Enterprise ERP systems, Meta API cloud integrations, and business platforms in Suez, Egypt.',
        ]);
    }

    public function leadershipBio()
    {
        return view('public.bio', [
            'title' => 'Mahmoud Amin — Founder & Chief Software Architect | Musoftwares',
            'description' => 'Biography and engineering profile of Mahmoud Amin, Founder and Chief Software Architect at Musoftwares in Suez, Egypt.',
        ]);
    }

    public function startProject()
    {
        return Inertia::render('Public/Wizard/SystemBuilder', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Start a Project — System Architecture Wizard | Musoftwares',
                'description' => 'Configure your custom enterprise system, ERP, WhatsApp automation, or SaaS application step-by-step with transparent modules and instant quotation.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function companyCareers()
    {
        return view('public.careers', [
            'title' => 'Careers — Join Our Engineering Studio | Musoftwares',
            'description' => 'Explore engineering and systems architecture roles at Musoftwares Studio in Suez, Egypt.',
        ]);
    }

    public function companyContact()
    {
        return view('public.contact', [
            'title' => 'Contact Us — Talk Directly with Chief Software Architect | Musoftwares',
            'description' => 'Direct technical communication with Mahmoud Amin. No middle management. Rapid responses and transparent project scoping.',
        ]);
    }

    public function privacyPolicy()
    {
        return view('public.legal.privacy', [
            'title' => 'Privacy Policy | Data Protection & Security | Musoftwares',
            'description' => 'Learn how Musoftwares protects your personal data and maintains strict privacy and security standards.',
        ]);
    }

    public function termsOfService()
    {
        return view('public.legal.terms', [
            'title' => 'Terms of Service | Enterprise Agreement | Musoftwares',
            'description' => 'Read our Terms of Service governing the use of Musoftwares software and infrastructure.',
        ]);
    }

    public function cookiePolicy()
    {
        return view('public.legal.cookies', [
            'title' => 'Cookie Policy | Tracking & Analytics | Musoftwares',
            'description' => 'Information about how cookies and session storage are used on Musoftwares platforms.',
        ]);
    }

    public function compare($slug = 'laravel-vs-nodejs')
    {
        return view('public.compare', [
            'title' => 'Laravel vs Node.js: 2026 Enterprise Architecture Benchmark | Musoftwares',
            'description' => 'Technical comparison and performance benchmark: Laravel 12 vs Node.js for Enterprise ERP, SaaS platforms, and real-time APIs.',
        ]);
    }

    public function pricing()
    {
        return redirect()->route('estimator', [], 301);
    }

    public function estimator(Request $request)
    {
        $rate = 50.0;
        try {
            $usd = Currency::where('currency', 'USD')->first();
            $egp = Currency::where('currency', 'EGP')->first();
            if ($usd && $egp) {
                $rate = CurrenciesExchange::RateToday(1.0, $usd->id, $egp->id) ?: 50.0;
            }
        } catch (\Throwable $e) {
            // Keep default 50.0 fallback on connection/lookup failure
            $rate = 50.0;
        }

        $estimatorData = (new ProjectEstimatorDataService())->getEstimatorData((float)$rate);

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

