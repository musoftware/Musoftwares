<?php

namespace App\Http\Controllers;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\WebsiteService;
use App\Services\AI\ProjectEstimatorAiService;
use App\Services\IpGeolocationService;
use App\Services\PricingService;
use App\Traits\ConvertsCurrency;
use App\Models\Project;
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

    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if (! ($user->enable_3d_dashboard ?? true)) {
                return redirect()->route('client.projects.index');
            }
            $dashboardService = app(\App\Services\DashboardService::class);
            $data = $dashboardService->getClientDashboardData($user);

            return view('dashboard.v8_main', $data);
        }

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

        return Inertia::render('Public/Home', [
            'dbProjects' => $dbProjects,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Home | Musoftware',
                'description' => 'Comprehensive ERP and CRM solutions, business management tools, and technical consulting to scale your operations.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function portfolio()
    {
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

        return Inertia::render('Public/Portfolio', [
            'dbProjects' => $dbProjects,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Portfolio | Musoftware',
                'description' => 'Explore our successful projects, case studies, and the robust applications we have built for businesses worldwide.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function portfolioShow($slug)
    {
        $project = Project::where('show_on_landing_portfolio', true)
            ->where(function($q) use ($slug) {
                $q->where('portfolio_title', $slug)
                  ->orWhere('project_name', $slug)
                  ->orWhere(DB::raw('LOWER(REPLACE(portfolio_title, " ", "-"))'), $slug)
                  ->orWhere(DB::raw('LOWER(REPLACE(project_name, " ", "-"))'), $slug);
            })->first();

        $dbProject = null;
        if ($project) {
            $dbProject = [
                'slug' => $slug,
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
        }

        return Inertia::render('Public/PortfolioShow', [
            'slug' => $slug,
            'dbProject' => $dbProject,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => ucfirst(str_replace('-', ' ', $slug)).' - Portfolio | Musoftware',
                'description' => 'Discover the details and success story of '.ucfirst(str_replace('-', ' ', $slug)).' developed by Musoftware.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
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
        return Inertia::render('Public/Platforms', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Platforms | Musoftware',
                'description' => 'Discover our suite of business platforms including CRM, ERP, and Cloud solutions tailored for your business needs.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function platformCrm()
    {
        return Inertia::render('Public/Platforms/Crm', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'CRM Platform | Musoftware',
                'description' => 'Manage your customer relationships efficiently with our powerful CRM platform.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function platformErp()
    {
        return Inertia::render('Public/Platforms/Erp', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'ERP Platform | Musoftware',
                'description' => 'Streamline your enterprise resources and operations with our integrated ERP platform.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function platformCloud()
    {
        return Inertia::render('Public/Platforms/Cloud', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Cloud Solutions | Musoftware',
                'description' => 'Secure, scalable, and reliable cloud solutions to host and manage your applications.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function solutions()
    {
        return Inertia::render('Public/Solutions', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Industry Solutions | Musoftware',
                'description' => 'Tailored software solutions for Healthcare, Education, E-commerce, Real Estate, and Finance sectors.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function solutionHealthcare()
    {
        return Inertia::render('Public/Solutions/Healthcare', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Healthcare Solutions | Musoftware',
                'description' => 'Advanced digital health and clinic management software solutions.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function solutionEducation()
    {
        return Inertia::render('Public/Solutions/Education', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Education Solutions | Musoftware',
                'description' => 'Innovative e-learning platforms and school management systems.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function solutionEcommerce()
    {
        return Inertia::render('Public/Solutions/Ecommerce', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'E-commerce Solutions | Musoftware',
                'description' => 'Scalable online stores and multi-vendor marketplace solutions.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function solutionRealEstate()
    {
        return Inertia::render('Public/Solutions/RealEstate', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Real Estate Solutions | Musoftware',
                'description' => 'Property management and real estate listing platforms.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function solutionFinance()
    {
        return Inertia::render('Public/Solutions/Finance', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Finance Solutions | Musoftware',
                'description' => 'Secure financial software, accounting tools, and fintech solutions.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function company()
    {
        return Inertia::render('Public/Company', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Company | Musoftware',
                'description' => 'Learn more about Musoftware, our mission, vision, and the team driving innovation.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function companyAbout()
    {
        return Inertia::render('Public/Company/About', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'About Us | Musoftware',
                'description' => 'Discover our story, values, and what makes Musoftware a leader in digital solutions.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function companyCareers()
    {
        return Inertia::render('Public/Company/Careers', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Careers | Musoftware',
                'description' => 'Join our dynamic team. Explore open positions and career opportunities at Musoftware.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function companyContact()
    {
        return Inertia::render('Public/Company/Contact', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Contact Us | Musoftware',
                'description' => 'Get in touch with our team for inquiries, support, or to discuss your next big project.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function privacyPolicy()
    {
        return Inertia::render('Public/Legal/Privacy', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Privacy Policy | Musoftware',
                'description' => 'Read our privacy policy to understand how we collect, use, and protect your data.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function termsOfService()
    {
        return Inertia::render('Public/Legal/Terms', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Terms of Service | Musoftware',
                'description' => 'Review the terms and conditions governing the use of Musoftware services.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function cookiePolicy()
    {
        return Inertia::render('Public/Legal/Cookies', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
            'meta' => [
                'title' => 'Cookie Policy | Musoftware',
                'description' => 'Information about how we use cookies to improve your browsing experience.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function pricing(Request $request, IpGeolocationService $geoService)
    {
        $user = Auth::user();

        $usdCurrency = Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;

        $egpCurrency = Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;

        $userCurrencyId = null;
        if ($user && $user->currency_id) {
            $userCurrencyId = $user->currency_id;
        } else {
            // Use IP Geolocation for guest users
            $ipCurrencyCode = $geoService->getCurrencyCodeForIp($request->ip());
            if ($ipCurrencyCode) {
                $ipCurrency = Currency::where('currency', $ipCurrencyCode)->first();
                if ($ipCurrency) {
                    $userCurrencyId = $ipCurrency->id;
                }
            }
            // Fallback to USD if no IP currency match
            if (! $userCurrencyId) {
                $userCurrencyId = $usdCurrencyId;
            }
        }

        $userCurrency = Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';

        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        $egpRate = 50; // Fallback
        if ($usdCurrency && $egpCurrencyId) {
            $egpRate = CurrenciesExchange::RateToday(1, $usdCurrency->id, $egpCurrencyId) ?: 50;
        }

        $basePricesEGP = config('saas.modules', []);
        $convertPrice = function ($egpPrice) use ($egpRate, $rate, $currencyCode) {
            if ($currencyCode === 'EGP') {
                return round($egpPrice);
            }
            $usdPrice = $egpPrice / $egpRate;
            $converted = $usdPrice * $rate;

            return psychological_price($converted);
        };

        $pricingService = new PricingService;
        $serviceItems = $pricingService->getServiceItems($convertPrice);

        return Inertia::render('Public/Pricing', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'serviceItems' => $serviceItems,
            'currency' => $currencyCode,
            'targetModule' => $request->query('module'),
            'targetTool' => $request->query('tool'),
            'targetPlan' => $request->query('plan'),
        ])->withViewData([
            'meta' => [
                'title' => 'Pricing | Musoftware',
                'description' => 'Transparent and flexible pricing plans for our CRM, ERP, and specialized software services.',
                'image' => asset('images/default-meta.png'),
                'url' => url()->current(),
            ],
        ]);
    }

    public function estimator()
    {
        $usd = Currency::where('currency', 'USD')->first();
        $egp = Currency::where('currency', 'EGP')->first();
        $rate = 50.0;
        if ($usd && $egp) {
            try {
                $rate = CurrenciesExchange::RateToday(1.0, $usd->id, $egp->id);
            } catch (\Throwable $e) {
                // Keep default 50.0 fallback on failure
            }
        }

        return Inertia::render('Public/Estimator', [
            'exchangeRate' => (float)$rate,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ])->withViewData([
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
     * Analyze project requirements using AI to auto-populate Estimator (Admin only).
     */
    public function analyzeEstimatorWithAi(Request $request, ProjectEstimatorAiService $aiService)
    {
        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only administrators can use the AI Estimator Assistant.',
            ], 403);
        }

        $validated = $request->validate([
            'prompt' => 'required|string|min:5|max:5000',
        ]);

        $result = $aiService->analyze($validated['prompt']);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI estimation. Please verify AI API keys in settings and try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Display the official printable corporate quotation Blade view.
     */
    public function showQuotation($code)
    {
        $quote = Cache::get("quotation:{$code}");

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

