<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\GuestTicket;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PublicToolsController extends Controller
{
    /**
     * Display the index directory of public free tools.
     */
    public function index()
    {
        return view('public.tools.index', [
            'title' => 'Developer & Business Tools Directory | Musoftwares',
            'description' => 'Explore our collection of free developer, business, and architecture estimation tools.',
        ]);
    }

    /**
     * Display the Facebook Page management cost calculator.
     */
    public function facebookCostCalculator()
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

        $pricingConfig = [
            'base_under_10k' => 100,
            'base_10k_50k' => 180,
            'base_50k_250k' => 350,
            'base_over_250k' => 600,
            'price_per_post' => 12,
            'chatbot_basic' => 45,
            'chatbot_ai' => 220,
            'ads_management' => 120,
        ];

        return Inertia::render('Public/Tools/FacebookCostCalculator', [
            'exchangeRate' => (float)$rate,
            'pricingConfig' => $pricingConfig,
        ])->withViewData([
            'meta' => [
                'title' => __('tools.fb_title') . ' | Musoftwares',
                'description' => __('tools.fb_desc'),
                'url' => route('public.tools.facebook-cost'),
            ],
        ]);
    }

    /**
     * Display the Universal Software & Web Cost Calculator.
     */
    public function websiteCostCalculator()
    {
        return redirect()->route('estimator', [], 301);
    }

    /**
     * Display the Free Invoice Generator.
     */
    public function invoiceGenerator()
    {
        return Inertia::render('Public/Tools/InvoiceGenerator')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.invoice_title') . ' | Musoftwares',
                    'description' => __('tools.invoice_desc'),
                    'url' => route('public.tools.invoice-generator'),
                ],
            ]);
    }

    /**
     * Capture Lead Magnet submissions and store as a CRM Guest Ticket.
     */
    public function captureLead(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:50',
            'business_name' => 'nullable|string|max:255',
            'tool_name' => 'required|string|max:255',
            'breakdown' => 'required|string',
        ]);

        $ticket = GuestTicket::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'subject' => "Lead Magnet: " . $request->input('tool_name') . " estimation for " . ($request->input('business_name') ?: 'Individual'),
            'body' => "Business Name: " . ($request->input('business_name') ?: 'N/A') . "\n\nBreakdown Details:\n" . $request->input('breakdown'),
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('tools.fb_lead_success'),
            'ticket_id' => $ticket->id
        ]);
    }

    /**
     * Display the Website Audit/Checker tool.
     */
    public function websiteChecker()
    {
        return Inertia::render('Public/Tools/WebsiteChecker')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.audit_title') . ' | Musoftwares',
                    'description' => __('tools.audit_desc'),
                    'url' => route('public.tools.website-checker'),
                ],
            ]);
    }

    /**
     * Perform cURL & SSL diagnostic checks on the target URL.
     */
    public function inspectWebsite(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        
        // Normalize URL protocol
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        if (empty($host)) {
            return response()->json([
                'success' => false,
                'message' => 'الرابط غير صالح. يرجى التأكد من كتابة الرابط بشكل صحيح.'
            ]);
        }

        // 1. HTTP cURL check
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($response === false) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر الاتصال بالموقع. يرجى التحقق من الرابط وإعادة المحاولة.'
            ]);
        }

        $headerSize = $info['header_size'];
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // 2. Technology & Stack Detection
        $tech = 'Custom Stack';
        $isLaravel = false;
        $isWordpress = false;
        $isShopify = false;

        if (stripos($headers, 'laravel_session') !== false || stripos($body, 'laravel_session') !== false) {
            $isLaravel = true;
            $tech = 'Laravel';
        }
        if (stripos($body, 'wp-content') !== false || stripos($body, 'wp-includes') !== false) {
            $isWordpress = true;
            $tech = 'WordPress';
        }
        if (stripos($body, 'cdn.shopify.com') !== false || stripos($body, 'shopify-features') !== false) {
            $isShopify = true;
            $tech = 'Shopify';
        }

        // Double check Laravel /up route if not detected yet
        if (!$isLaravel) {
            $upUrl = rtrim($url, '/') . '/up';
            $chUp = curl_init();
            curl_setopt($chUp, CURLOPT_URL, $upUrl);
            curl_setopt($chUp, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chUp, CURLOPT_NOBODY, true);
            curl_setopt($chUp, CURLOPT_TIMEOUT, 3);
            curl_exec($chUp);
            $upCode = curl_getinfo($chUp, CURLINFO_HTTP_CODE);
            curl_close($chUp);
            if ($upCode == 200) {
                $isLaravel = true;
                $tech = 'Laravel';
            }
        }

        // 3. SSL Expiration Calculation
        $sslDaysLeft = null;
        $sslExpired = false;
        try {
            $g = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
            $r = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $g);
            if ($r) {
                $cont = stream_context_get_params($r);
                if (isset($cont["options"]["ssl"]["peer_certificate"])) {
                    $cert = openssl_x509_parse($cont["options"]["ssl"]["peer_certificate"]);
                    $sslValidTo = $cert['validTo_time_t'] ?? null;
                    if ($sslValidTo) {
                        $sslDaysLeft = (int)ceil(($sslValidTo - time()) / (60 * 60 * 24));
                        if ($sslDaysLeft <= 0) {
                            $sslExpired = true;
                        }
                    }
                }
            } else {
                $sslExpired = true;
            }
        } catch (\Throwable $e) {
            $sslExpired = true;
        }

        // 4. Performance latency scoring
        $responseTime = $info['total_time'];
        $score = 100;
        $warnings = [];

        // Check SSL status
        if ($sslExpired) {
            $warnings[] = [
                'type' => 'danger',
                'title_ar' => 'شهادة الأمان SSL منتهية أو غير صالحة',
                'title_en' => 'SSL certificate is expired or invalid',
                'desc_ar' => 'متصفحات العملاء ستظهر رسالة تحذير حمراء تفيد بأن الموقع غير آمن، مما يمنعهم من تصفحه.',
                'desc_en' => 'Customers will see a red warning that your site is insecure, blocking traffic.'
            ];
            $score -= 25;
        } elseif ($sslDaysLeft !== null && $sslDaysLeft < 15) {
            $warnings[] = [
                'type' => 'warning',
                'title_ar' => "شهادة الأمان SSL تنتهي خلال {$sslDaysLeft} يوماً",
                'title_en' => "SSL certificate expires in {$sslDaysLeft} days",
                'desc_ar' => 'يجب تجديد الشهادة فوراً لتجنب إغلاق المتصفحات للموقع.',
                'desc_en' => 'Renew the certificate immediately to avoid browsers blocking the website.'
            ];
            $score -= 10;
        }

        // Check speed
        if ($responseTime > 2.0) {
            $warnings[] = [
                'type' => 'danger',
                'title_ar' => "سرعة استجابة الموقع بطيئة جداً (" . round($responseTime, 2) . " ثانية)",
                'title_en' => "Website response is very slow (" . round($responseTime, 2) . "s)",
                'desc_ar' => 'الموقع يستغرق وقتاً طويلاً للتحميل، مما يؤدي إلى هروب الزوار وخسارة العملاء.',
                'desc_en' => 'The site takes too long to respond, leading to visitor drop-offs and lost conversions.'
            ];
            $score -= 25;
        } elseif ($responseTime > 0.8) {
            $warnings[] = [
                'type' => 'warning',
                'title_ar' => "سرعة استجابة متوسطة (" . round($responseTime, 2) . " ثانية)",
                'title_en' => "Average response speed (" . round($responseTime, 2) . "s)",
                'desc_ar' => 'يمكن تحسين سرعة تحميل الموقع لتقديم تجربة تصفح أفضل لزيادة الترتيب في جوجل.',
                'desc_en' => 'Performance can be optimized to give a better user experience and improve SEO rankings.'
            ];
            $score -= 10;
        }

        // Check tech and debug leaks
        if ($isLaravel) {
            $hasDebug = (stripos($body, 'ignition') !== false || stripos($body, 'debugbar') !== false || stripos($body, 'whoops') !== false);
            if ($hasDebug) {
                $warnings[] = [
                    'type' => 'danger',
                    'title_ar' => 'وضع تصحيح الأخطاء (Debug Mode) مفتوح علناً!',
                    'title_en' => 'Laravel Debug Mode is enabled!',
                    'desc_ar' => 'هذا يشكل خطراً أمنياً فادحاً لأنه يعرض تفاصيل وقواعد البيانات للمخترقين.',
                    'desc_en' => 'Critical security risk. Database passwords and credentials can be exposed to attackers.'
                ];
                $score -= 20;
            }
        } elseif ($isWordpress) {
            $warnings[] = [
                'type' => 'warning',
                'title_ar' => 'الموقع مبني بنظام WordPress',
                'title_en' => 'Website is built on WordPress',
                'desc_ar' => 'مواقع الووردبريس تعاني غالباً من ثغرات أمنية في الإضافات وبطء التحميل إذا لم تتم صيانتها دورياً.',
                'desc_en' => 'WordPress sites often suffer from plugin vulnerabilities and slowdowns without regular maintenance.'
            ];
            $score -= 10;
        }

        // Sitemap check
        if (stripos($body, 'sitemap.xml') === false) {
            $warnings[] = [
                'type' => 'warning',
                'title_ar' => 'لم نجد رابط Sitemap.xml مهيأ في الصفحة الرئيسية',
                'title_en' => 'Sitemap.xml was not found',
                'desc_ar' => 'خارطة الموقع ضرورية جداً لجوجل حتى يقوم بفهرسة وتنزيل صفحات موقعك في البحث.',
                'desc_en' => 'A sitemap is required for Google search engines to properly index your pages.'
            ];
            $score -= 10;
        }

        $score = max(20, $score); // Minimum score is 20

        return response()->json([
            'success' => true,
            'url' => $url,
            'tech' => $tech,
            'score' => $score,
            'responseTime' => round($responseTime, 2),
            'sslDaysLeft' => $sslDaysLeft,
            'warnings' => $warnings
        ]);
    }

    /**
     * Display Speed Loss Calculator.
     */
    public function speedLossCalculator()
    {
        return Inertia::render('Public/Tools/SpeedLossCalculator')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.speed_loss_title') . ' | Musoftwares',
                    'description' => __('tools.speed_loss_desc'),
                    'url' => route('public.tools.speed-loss-calculator'),
                ],
            ]);
    }

    /**
     * Inspect Speed and calculate loss parameters.
     */
    public function inspectSpeedLoss(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // only fetch headers for fast connection benchmark
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $responseTime = $info['total_time'];
        if ($responseTime == 0) {
            $responseTime = 2.4; // Fallback
        }

        return response()->json([
            'success' => true,
            'url' => $url,
            'responseTime' => round($responseTime, 2)
        ]);
    }

    /**
     * Display Payment Gateway Compliance Auditor.
     */
    public function paymentGatewayAuditor()
    {
        return Inertia::render('Public/Tools/PaymentGatewayAuditor')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.pay_audit_title') . ' | Musoftwares',
                    'description' => __('tools.pay_audit_desc'),
                    'url' => route('public.tools.payment-gateway-auditor'),
                ],
            ]);
    }

    /**
     * Perform gateway compliance scans.
     */
    public function inspectPaymentGateway(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        // HTTP cURL check
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($response === false) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر الاتصال بالموقع.'
            ]);
        }

        $headerSize = $info['header_size'];
        $body = substr($response, $headerSize);

        // Check SSL
        $sslActive = false;
        try {
            $g = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
            $r = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 4, STREAM_CLIENT_CONNECT, $g);
            if ($r) {
                $sslActive = true;
            }
        } catch (\Throwable $e) {}

        // Check policies
        $hasPrivacy = (stripos($body, '/privacy') !== false || stripos($body, 'privacy policy') !== false || stripos($body, 'سياسة الخصوصية') !== false);
        $hasReturn = (stripos($body, '/return') !== false || stripos($body, '/refund') !== false || stripos($body, 'سياسة الاسترجاع') !== false || stripos($body, 'سياسة الاستبدال') !== false || stripos($body, 'refund policy') !== false);
        $hasReg = (stripos($body, 'سجل تجاري') !== false || stripos($body, 'بطاقة ضريبية') !== false || stripos($body, 'tax registration') !== false || stripos($body, 'commercial registration') !== false);
        $hasAddress = (stripos($body, 'address') !== false || stripos($body, 'العنوان') !== false || stripos($body, 'مقر') !== false);

        $warnings = [];
        if (!$sslActive) {
            $warnings[] = [
                'title_ar' => 'شهادة الأمان SSL غير نشطة',
                'title_en' => 'SSL Certificate is Inactive',
                'desc_ar' => 'بوابات الدفع ترفض المتاجر التي لا تملك شهادات SSL نشطة تماماً.',
                'desc_en' => 'Payment processors strictly reject stores without active SSL certificates.'
            ];
        }
        if (!$hasPrivacy) {
            $warnings[] = [
                'title_ar' => 'صفحة سياسة الخصوصية مفقودة',
                'title_en' => 'Privacy Policy Page is Missing',
                'desc_ar' => 'تطلب البوابات وجود رابط واضح لسياسة الخصوصية في تذييل الموقع.',
                'desc_en' => 'Processors require a visible Privacy Policy page link in the footer.'
            ];
        }
        if (!$hasReturn) {
            $warnings[] = [
                'title_ar' => 'صفحة سياسة الاستبدال والاسترجاع مفقودة',
                'title_en' => 'Return & Refund Policy is Missing',
                'desc_ar' => 'سياسات الاسترجاع ضرورية لحماية المستهلك وموافقة Moyasar/Paymob.',
                'desc_en' => 'Refund guidelines are required for gateway audit compliance.'
            ];
        }
        if (!$hasReg) {
            $warnings[] = [
                'title_ar' => 'بيانات السجل التجاري / الرقم الضريبي غير معلنة',
                'title_en' => 'Commercial Registration/Tax Number Missing',
                'desc_ar' => 'يُفضل إدراج الرقم الضريبي أو السجل التجاري في أسفل الموقع لتأكيد المصداقية.',
                'desc_en' => 'Adding commercial details in footer increases compliance pass rates.'
            ];
        }

        return response()->json([
            'success' => true,
            'url' => $url,
            'sslActive' => $sslActive,
            'hasPrivacy' => $hasPrivacy,
            'hasReturn' => $hasReturn,
            'hasReg' => $hasReg,
            'hasAddress' => $hasAddress,
            'warnings' => $warnings
        ]);
    }

    /**
     * Display Ad Tracking Pixel Auditor.
     */
    public function pixelAuditor()
    {
        return Inertia::render('Public/Tools/PixelTrackerAuditor')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.pixel_title') . ' | Musoftwares',
                    'description' => __('tools.pixel_desc'),
                    'url' => route('public.tools.pixel-tracker-auditor'),
                ],
            ]);
    }

    /**
     * Inspect script trackers on target site.
     */
    public function inspectPixel(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $body = curl_exec($ch);
        curl_close($ch);

        if ($body === false) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر الاتصال بالموقع.'
            ]);
        }

        // Count pixel occurrences
        $fbCount = substr_count(strtolower($body), 'connect.facebook.net/en_us/fbevents.js') + substr_count(strtolower($body), 'fbq(');
        $hasFb = $fbCount > 0;
        
        $gaCount = substr_count(strtolower($body), 'googletagmanager.com/gtag/js') + substr_count(strtolower($body), 'g-');
        $hasGa = $gaCount > 0;

        $tiktokCount = substr_count(strtolower($body), 'analytics.tiktok.com') + substr_count(strtolower($body), 'ttq(');
        $hasTiktok = $tiktokCount > 0;

        $warnings = [];
        if (!$hasFb) {
            $warnings[] = [
                'title_ar' => 'Facebook Pixel غير مكتشف',
                'title_en' => 'Facebook Pixel not detected',
                'desc_ar' => 'لا يمكنك تتبع تحويلات حملات الفيسبوك الممولة وإعادة استهداف الزوار بدقة.',
                'desc_en' => 'You cannot track Facebook ad conversion rates or run dynamic retargeting.'
            ];
        } elseif ($fbCount > 4) {
            $warnings[] = [
                'title_ar' => 'تكرار أكواد Facebook Pixel في الكود',
                'title_en' => 'Duplicate Facebook Pixel codes detected',
                'desc_ar' => 'تثبيت بيكسل مكرر يؤدي لتسجيل تحويلات خاطئة واحتساب مزدوج للمبيعات في الإعلانات.',
                'desc_en' => 'Duplicate pixel installation leads to double conversion tracking errors.'
            ];
        }

        if (!$hasGa) {
            $warnings[] = [
                'title_ar' => 'Google Analytics 4 (GA4) غير مكتشف',
                'title_en' => 'Google Analytics 4 not detected',
                'desc_ar' => 'تخسر فرصة الحصول على تحليلات دقيقة لمسارات زيارات موقعك.',
                'desc_en' => 'You lose out on deep dashboard visitor analytics data tracking.'
            ];
        }

        return response()->json([
            'success' => true,
            'url' => $url,
            'hasFb' => $hasFb,
            'fbCount' => $fbCount,
            'hasGa' => $hasGa,
            'hasTiktok' => $hasTiktok,
            'warnings' => $warnings
        ]);
    }

    /**
     * Display Competitor E-commerce Tech Spy.
     */
    public function competitorSpy()
    {
        return Inertia::render('Public/Tools/CompetitorTechSpy')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.spy_title') . ' | Musoftwares',
                    'description' => __('tools.spy_desc'),
                    'url' => route('public.tools.competitor-tech-spy'),
                ],
            ]);
    }

    /**
     * Perform tech stack competitor analysis scans.
     */
    public function inspectCompetitor(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $body = curl_exec($ch);
        curl_close($ch);

        if ($body === false) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر الاتصال بالموقع.'
            ]);
        }

        // Platforms
        $platform = 'Custom Engineering / Unknown';
        if (stripos($body, 'salla.sa') !== false || stripos($body, 'salla-cdn') !== false) {
            $platform = 'Salla (سلة)';
        } elseif (stripos($body, 'zid.sa') !== false || stripos($body, 'zid-cdn') !== false) {
            $platform = 'Zid (زد)';
        } elseif (stripos($body, 'cdn.shopify.com') !== false || stripos($body, 'shopify-features') !== false) {
            $platform = 'Shopify';
        } elseif (stripos($body, 'wp-content') !== false || stripos($body, 'wp-includes') !== false) {
            $platform = 'WooCommerce / WordPress';
        }

        // Payments
        $payments = [];
        if (stripos($body, 'paymob') !== false) $payments[] = 'Paymob';
        if (stripos($body, 'moyasar') !== false) $payments[] = 'Moyasar';
        if (stripos($body, 'tabby') !== false) $payments[] = 'Tabby';
        if (stripos($body, 'tamara') !== false) $payments[] = 'Tamara';
        if (stripos($body, 'stripe') !== false) $payments[] = 'Stripe';
        if (empty($payments)) $payments[] = 'Direct Cash / Bank Transfer';

        // Shipping
        $shipping = [];
        if (stripos($body, 'aramex') !== false) $shipping[] = 'Aramex';
        if (stripos($body, 'smsa') !== false) $shipping[] = 'SMSA Express';
        if (stripos($body, 'dhl') !== false) $shipping[] = 'DHL';
        if (stripos($body, 'bosta') !== false) $shipping[] = 'Bosta';
        if (empty($shipping)) $shipping[] = 'Custom Shipping Integration';

        return response()->json([
            'success' => true,
            'url' => $url,
            'platform' => $platform,
            'payments' => implode(', ', $payments),
            'shipping' => implode(', ', $shipping)
        ]);
    }

    /**
     * Display the Image Grid Cropper.
     */
    public function imageCropper()
    {
        return Inertia::render('Public/Tools/ImageCropper');
    }

    /**
     * Subscribe email to newsletter / technical updates.
     */
    public function subscribeNewsletter(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = $request->input('email');

        // Check if ticket already exists recently for this email
        $existing = GuestTicket::where('email', $email)
            ->where('subject', 'LIKE', 'Newsletter Subscription%')
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if (!$existing) {
            GuestTicket::create([
                'name' => 'Newsletter Subscriber',
                'email' => $email,
                'mobile' => '',
                'subject' => 'Newsletter Subscription: ' . $email,
                'body' => 'User subscribed to technical updates and platform release notes via footer newsletter form.',
                'status' => 'open',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing to our technical updates!',
        ]);
    }
}
