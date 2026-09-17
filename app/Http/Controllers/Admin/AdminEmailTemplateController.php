<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AdminEmailTemplateController extends Controller
{
    /**
     * Get the registry of all email templates across the system.
     */
    private function getTemplateRegistry(): array
    {
        return [
            'invoice_created_with_loyalty' => [
                'key' => 'invoice_created_with_loyalty',
                'name' => 'Invoice Created with Loyalty Incentives',
                'name_ar' => 'إنشاء فاتورة جديدة مع حوافز ورتب نقاط الولاء',
                'description' => 'Dispatched automatically when an invoice is issued. Features client 3D tier badge, early payment multiplier, and support ticket loyalty points incentives.',
                'description_ar' => 'يُرسل تلقائياً للعميل عند إصدار فاتورة جديدة. يعرض شارة الرتبة الثلاثية الأبعاد، مضاعف السداد المبكر، وحوافز نقاط التذاكر.',
                'category' => 'invoices',
                'category_label' => 'Invoices & Billing',
                'category_label_ar' => 'الفواتير والمالية',
                'blade_view' => 'emails.invoices.created_with_loyalty',
                'blade_path' => 'resources/views/emails/invoices/created_with_loyalty.blade.php',
                'subject' => 'Invoice INV-2026-0892 — Settle Early to Earn Loyalty Points',
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'clientName' => 'Mahmoud Mohamed',
                        'invoiceNumber' => 'INV-2026-0892',
                        'totalAmountFormatted' => '$1,450.00',
                        'dueDateFormatted' => Carbon::now('Africa/Cairo')->addDays(10)->format('M d, Y'),
                        'invoiceUrl' => url('/client/invoices/1'),
                        'tierSlug' => 'gold',
                        'tierName' => 'Gold',
                        'tierDiscount' => 10,
                        'pointsBalance' => 1250,
                        'pointsCashValueFormatted' => '$12.50',
                        'potentialEarlyPoints' => 400,
                    ];
                },
            ],
            'loyalty_progress' => [
                'key' => 'loyalty_progress',
                'name' => 'Loyalty Tier Progress Update',
                'name_ar' => 'تقرير تقدم العميل نحو الترقية في الرتبة',
                'description' => 'Regular check-in update showing progress percentage toward the next tier, points needed, and newly unlocked perks.',
                'description_ar' => 'تقرير دوري يوضح نسبة تقدم العميل نحو الرتبة التالية، النقاط المتبقية للوصول، والمزايا المنتظرة.',
                'category' => 'loyalty',
                'category_label' => 'Loyalty & Rewards',
                'category_label_ar' => 'برنامج الولاء والرتب',
                'blade_view' => 'emails.loyalty.progress',
                'blade_path' => 'resources/views/emails/loyalty/progress.blade.php',
                'subject' => "You're 450 points away from Platinum tier!",
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'clientFirstName' => 'Mahmoud',
                        'currentBalance' => 1550,
                        'pointsNeeded' => 450,
                        'nextTierName' => 'Platinum',
                        'progressPct' => 78,
                        'nextTierColor' => '#7928ca',
                        'currentTierName' => 'Gold',
                        'nextTierSlug' => 'platinum',
                        'nextTierDiscount' => 15,
                        'nextTierPriority' => 'High',
                        'perks' => [
                            '15% automatic discount on all invoices',
                            'VIP ultra-fast ticket routing with 1-hour SLA',
                            'Dedicated executive account manager',
                        ],
                    ];
                },
            ],
            'loyalty_tier_upgraded' => [
                'key' => 'loyalty_tier_upgraded',
                'name' => 'Loyalty Tier Upgraded Celebration',
                'name_ar' => 'تهنئة الترقية إلى رتبة ولاء جديدة',
                'description' => 'Dispatched the moment a client crosses lifetime thresholds to reach a higher tier, highlighting new discount rates and VIP support.',
                'description_ar' => 'يُرسل فور وصول العميل للحد الأدنى لنقاط رتبة أعلى، للاحتفال بالترقية واستعراض نسبة الخصم الجديدة وأولوية الدعم الفني.',
                'category' => 'loyalty',
                'category_label' => 'Loyalty & Rewards',
                'category_label_ar' => 'برنامج الولاء والرتب',
                'blade_view' => 'emails.loyalty.tier_upgraded',
                'blade_path' => 'resources/views/emails/loyalty/tier_upgraded.blade.php',
                'subject' => "You've reached Gold tier!",
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'clientFirstName' => 'Mahmoud',
                        'previousTierName' => 'Silver',
                        'newTierName' => 'Gold',
                        'newTierColor' => '#d97706',
                        'newTierSlug' => 'gold',
                        'newTierDiscount' => 10,
                        'newTierPriority' => 'High',
                        'loyaltyBalance' => 1800,
                        'perks' => [
                            '10% automatic discount on all invoices',
                            'High priority routing for tickets and tasks',
                            'Up to 2x accelerated early-payment bonus points',
                        ],
                    ];
                },
            ],
            'loyalty_invoice_due_reminder' => [
                'key' => 'loyalty_invoice_due_reminder',
                'name' => 'Invoice Due Loyalty Reminder',
                'name_ar' => 'تذكير استحقاق الفاتورة مع مضاعف النقاط',
                'description' => 'Sent prior to invoice due date to encourage on-time or early settlement with bonus multiplier points calculation.',
                'description_ar' => 'تذكير قبل موعد استحقاق الفاتورة لتشجيع السداد المبكر بالحصول على مضاعف نقاط الولاء الإضافية.',
                'category' => 'invoices',
                'category_label' => 'Invoices & Billing',
                'category_label_ar' => 'الفواتير والمالية',
                'blade_view' => 'emails.loyalty.invoice_due_reminder',
                'blade_path' => 'resources/views/emails/loyalty/invoice_due_reminder.blade.php',
                'subject' => 'Invoice due in 5 days — earn 600 points by paying now',
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'clientFirstName' => 'Mahmoud',
                        'invoiceNumber' => 'INV-2026-0892',
                        'dueDate' => Carbon::now('Africa/Cairo')->addDays(5)->format('M d, Y'),
                        'potentialPoints' => 600,
                        'basePoints' => 200,
                        'earlyMultiplier' => 2,
                        'invoiceUrl' => url('/client/invoices/1'),
                    ];
                },
            ],
            'recurring_invoice_payment_required' => [
                'key' => 'recurring_invoice_payment_required',
                'name' => 'Recurring Invoice Payment Required',
                'name_ar' => 'إشعار استحقاق سداد فاتورة متكررة',
                'description' => 'Sent automatically on billing cycles for ongoing contracts, retainers, and subscription renewals with itemized breakdown.',
                'description_ar' => 'يُرسل تلقائياً في دورات الفوترة للمشاريع والعقود المتكررة والاشتراكات الشهرية متضمناً بنود الفاتورة.',
                'category' => 'invoices',
                'category_label' => 'Invoices & Billing',
                'category_label_ar' => 'الفواتير والمالية',
                'blade_view' => 'emails.recurring_invoice_payment_required',
                'blade_path' => 'resources/views/emails/recurring_invoice_payment_required.blade.php',
                'subject' => 'Payment Required for Invoice #REC-2026-0041',
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'userName' => 'Mahmoud Mohamed',
                        'invoiceTitle' => 'Dedicated Cloud Hosting & Maintenance Cluster',
                        'invoiceNumber' => 'REC-2026-0041',
                        'invoiceAmount' => '$290.00',
                        'userBalance' => '$40.00',
                        'shortfall' => '$250.00',
                        'invoiceUrl' => url('/client/invoices/1/pay'),
                    ];
                },
            ],
            'subscription_expired_reminder' => [
                'key' => 'subscription_expired_reminder',
                'name' => 'Subscription Expiration Reminder',
                'name_ar' => 'تذكير انتهاء صلاحية الاشتراك',
                'description' => 'Warns users when an active module or cloud license has expired and provides a direct payment link to renew service.',
                'description_ar' => 'تنبيه العميل بانتهاء صلاحية اشتراك إحدى الخدمات أو التراخيص مع رابط تجديد مباشر لتجنب انقطاع الخدمة.',
                'category' => 'subscriptions',
                'category_label' => 'Subscriptions',
                'category_label_ar' => 'الاشتراكات والتراخيص',
                'blade_view' => 'emails.subscription_expired_reminder',
                'blade_path' => 'resources/views/emails/subscription_expired_reminder.blade.php',
                'subject' => 'Subscription Expired — Action Required',
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'isArabic' => false,
                        'userName' => 'Mahmoud Mohamed',
                        'moduleName' => 'Enterprise Core SaaS Suite',
                        'expiresAt' => Carbon::now('Africa/Cairo')->subDay()->format('F j, Y'),
                        'renewUrl' => url('/client/subscriptions'),
                    ];
                },
            ],
            'winback_stage' => [
                'key' => 'winback_stage',
                'name' => 'Client Retention & Winback Campaign',
                'name_ar' => 'حملة استعادة وإعادة تنشيط العملاء الخاملين',
                'description' => 'Automated retention campaigns triggered for inactive clients offering loyalty bonuses and exclusive return coupons.',
                'description_ar' => 'رسائل حملات استعادة وتنشيط العملاء الخاملين وتقديم عروض حصرية ونقاط ولاء إضافية للعودة للتعامل.',
                'category' => 'winback',
                'category_label' => 'Client Retention',
                'category_label_ar' => 'استعادة وتنشيط العملاء',
                'blade_view' => 'emails.winback.stage',
                'blade_path' => 'resources/views/emails/winback/stage.blade.php',
                'subject' => 'A special bonus is waiting for you at Musoftwares',
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'stageSlug' => 'urgency_incentive',
                        'clientFirstName' => 'Mahmoud',
                        'loyaltyBalance' => 2400,
                        'currentTierName' => 'Gold',
                        'pointsToNextTier' => 600,
                        'hasIncentive' => true,
                        'incentivePoints' => 500,
                        'incentiveDiscount' => 20,
                        'incentiveDaysRemaining' => 7,
                        'unsubscribeUrl' => url('/unsubscribe'),
                    ];
                },
            ],
            'services_digest' => [
                'key' => 'services_digest',
                'name' => 'Daily Services Catalog Digest',
                'name_ar' => 'النشرة الدورية لكتالوج الخدمات والحلول البرمجية',
                'description' => 'Showcases new tools, software modules, and automated workflow capabilities to clients and registered leads.',
                'description_ar' => 'استعراض أحدث الأدوات والحلول البرمجية وأنظمة الأتمتة المتاحة للعملاء في المنصة.',
                'category' => 'services',
                'category_label' => 'Services & Products',
                'category_label_ar' => 'الخدمات والمنتجات',
                'blade_view' => 'emails.services.digest',
                'blade_path' => 'resources/views/emails/services/digest.blade.php',
                'subject' => 'Discover What is New at Musoftwares Studio',
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'websiteServices' => collect([
                            (object) [
                                'primary_image_en' => '/images/tools/automation.png',
                                'title_en' => 'AI Automated Workflows Engine',
                                'title_ar' => 'محرك أتمتة مسارات العمل بالذكاء الاصطناعي',
                                'description_en' => 'Streamline multi-step company operations with background tasks, cron triggers, and webhooks.',
                                'description_ar' => 'أتمتة العمليات والمهام الخلفية عبر جدول زمني وخطافات ويب ذكية.',
                                'slug' => 'ai-workflows-engine',
                            ],
                            (object) [
                                'primary_image_en' => '/images/tools/whatsapp.png',
                                'title_en' => 'WhatsApp Cloud Commerce Bridge',
                                'title_ar' => 'جسر التجارة السحابية عبر واتساب',
                                'description_en' => 'Direct client messaging, interactive checkout receipts, and instant notification dispatches.',
                                'description_ar' => 'ربط متكامل لرسائل العملاء والفواتير والإشعارات عبر واتساب كلاود.',
                                'slug' => 'whatsapp-cloud-commerce',
                            ],
                        ]),
                        'marketplaceServices' => collect([
                            (object) [
                                'cover_image' => '/images/tools/pos.png',
                                'title' => 'Affiliate & POS Terminal Sync',
                                'title_translations' => ['en' => 'Affiliate & POS Terminal Sync', 'ar' => 'مزامنة نقاط البيع ونظام التسويق بالعمولة'],
                                'description' => 'Real-time sales commission attribution and dual-currency transaction processing.',
                                'description_translations' => ['en' => 'Real-time sales commission attribution', 'ar' => 'احتساب العمولات والمبيعات اللحظية'],
                                'id' => 1,
                                'url' => url('/marketplace/tools/pos'),
                            ],
                        ]),
                    ];
                },
            ],
            'discount_digest' => [
                'key' => 'discount_digest',
                'name' => 'Marketplace Discounts & Coupons Digest',
                'name_ar' => 'نشرة الخصومات والكوبونات الحصرية',
                'description' => 'Bilingual newsletter highlighting limited-time discounts, vouchers, and promo codes for tools and extensions.',
                'description_ar' => 'نشرة دورية تسلط الضوء على الخصومات والكوبونات وقسائم التخفيض المتاحة لأدوات وبرامج النظام.',
                'category' => 'marketing',
                'category_label' => 'Marketing & Offers',
                'category_label_ar' => 'العروض والخصومات',
                'blade_view' => 'emails.discount_digest',
                'blade_path' => 'resources/views/emails/discount_digest.blade.php',
                'subject' => 'خصومات حصرية اليوم على خدمات ومنتجات استوديو Musoftwares',
                'is_markdown' => false,
                'data_generator' => function () {
                    return [
                        'user' => (object) ['name' => 'محمود محمد'],
                        'discountedServices' => collect([
                            (object) [
                                'title' => 'باقة التحول الرقمي والأتمتة الذكية للشركات',
                                'url' => url('/services'),
                                'seller' => (object) ['name' => 'Musoftware Studio Pro'],
                                'packages' => collect([
                                    (object) [
                                        'has_discount' => true,
                                        'discount_percentage' => 30,
                                        'price' => 210.00,
                                        'old_price' => 300.00,
                                    ],
                                ]),
                            ],
                            (object) [
                                'title' => 'اشتراك استوديو البرمجيات السنوي الشامل',
                                'url' => url('/pricing'),
                                'seller' => (object) ['name' => 'Musoftware Official'],
                                'packages' => collect([
                                    (object) [
                                        'has_discount' => true,
                                        'discount_percentage' => 20,
                                        'price' => 799.00,
                                        'old_price' => 999.00,
                                    ],
                                ]),
                            ],
                        ]),
                    ];
                },
            ],
            'booking_confirmed' => [
                'key' => 'booking_confirmed',
                'name' => 'Appointment Booking Confirmation',
                'name_ar' => 'إشعار تأكيد حجز موعد استشارة أو اجتماع',
                'description' => 'Markdown-based transactional confirmation dispatched after booking an event or consultation slot.',
                'description_ar' => 'إشعار تأكيد الحجز الفوري لموعد استشارة فنية أو اجتماع عمل مع تفاصيل التوقيت والمضيف.',
                'category' => 'bookings',
                'category_label' => 'Bookings',
                'category_label_ar' => 'الحجوزات والمواعيد',
                'blade_view' => 'emails.bookings.confirmed',
                'blade_path' => 'resources/views/emails/bookings/confirmed.blade.php',
                'subject' => 'Booking Confirmed — Technical Strategy Consultation',
                'is_markdown' => true,
                'data_generator' => function () {
                    $eventType = (object) [
                        'title' => 'Technical Strategy Consultation',
                        'duration_minutes' => 45,
                        'user' => (object) ['name' => 'Musoftwares Architecture Team'],
                    ];
                    $booking = (object) [
                        'guest_name' => 'Mahmoud Mohamed',
                        'eventType' => $eventType,
                        'starts_at' => Carbon::now('Africa/Cairo')->addDays(2)->setTime(15, 0),
                        'timezone' => 'Africa/Cairo',
                        'payment_status' => 'paid',
                        'price' => 150,
                        'currency' => 'USD',
                    ];
                    return [
                        'booking' => $booking,
                    ];
                },
            ],
        ];
    }

    /**
     * Render template to HTML string based on markdown flag.
     */
    private function renderTemplateHtml(array $meta, array $sampleData): string
    {
        if (!empty($meta['is_markdown'])) {
            return app(Markdown::class)->render($meta['blade_view'], $sampleData)->toHtml();
        }

        return view($meta['blade_view'], $sampleData)->render();
    }

    /**
     * Display the email templates gallery.
     */
    public function index(Request $request): Response
    {
        $registry = $this->getTemplateRegistry();
        $selectedKey = $request->string('template', 'invoice_created_with_loyalty')->toString();

        if (!isset($registry[$selectedKey])) {
            $selectedKey = array_key_first($registry);
        }

        // Build templates metadata list
        $templates = [];
        foreach ($registry as $key => $meta) {
            $fullPath = base_path($meta['blade_path']);
            $source = File::exists($fullPath) ? File::get($fullPath) : '';
            $sampleData = call_user_func($meta['data_generator']);

            $templates[] = [
                'key' => $meta['key'],
                'name' => $meta['name'],
                'name_ar' => $meta['name_ar'],
                'description' => $meta['description'],
                'description_ar' => $meta['description_ar'],
                'category' => $meta['category'],
                'category_label' => $meta['category_label'],
                'category_label_ar' => $meta['category_label_ar'],
                'blade_view' => $meta['blade_view'],
                'blade_path' => $meta['blade_path'],
                'subject' => $meta['subject'],
                'sample_data' => $sampleData,
                'blade_source' => $source,
            ];
        }

        return Inertia::render('Admin/EmailTemplates/Index', [
            'templates' => $templates,
            'selectedKey' => $selectedKey,
            'adminEmail' => $request->user()?->email ?? 'admin@musoftwares.com',
        ]);
    }

    /**
     * Render isolated raw HTML preview of a specific email template.
     */
    public function preview(Request $request, string $key)
    {
        $registry = $this->getTemplateRegistry();

        if (!isset($registry[$key])) {
            abort(404, 'Template not found');
        }

        $meta = $registry[$key];
        $sampleData = call_user_func($meta['data_generator']);

        // Set locale if requested
        $locale = $request->string('locale')->toString();
        if ($locale && in_array($locale, ['ar', 'en'])) {
            app()->setLocale($locale);
        }

        try {
            $html = $this->renderTemplateHtml($meta, $sampleData);

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        } catch (\Throwable $e) {
            $errorHtml = '<div style="font-family:sans-serif;padding:30px;color:#dc2626;background:#fef2f2;border:1px solid #f87171;border-radius:12px;margin:20px;">'
                .'<h3 style="margin-top:0;">Failed to render email blade template</h3>'
                .'<p><strong>View:</strong> ' . htmlspecialchars($meta['blade_view']) . '</p>'
                .'<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>'
                .'<pre style="background:#fff;padding:15px;border-radius:8px;overflow-x:auto;font-size:12px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>'
                .'</div>';

            return response($errorHtml, 500, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
    }

    /**
     * Send a live test email of the chosen template to a specified address.
     */
    public function sendTest(Request $request, string $key)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $recipient = $request->string('email')->trim()->toString();
        $registry = $this->getTemplateRegistry();

        if (!isset($registry[$key])) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $meta = $registry[$key];
        $sampleData = call_user_func($meta['data_generator']);
        $subject = '[TEST PREVIEW] ' . $meta['subject'];

        try {
            $html = $this->renderTemplateHtml($meta, $sampleData);

            Mail::html($html, function ($message) use ($recipient, $subject) {
                $message->to($recipient)
                    ->subject($subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال البريد التجريبي بنجاح إلى ' . $recipient,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر إرسال البريد التجريبي: ' . $e->getMessage(),
            ], 500);
        }
    }
}
