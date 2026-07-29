<!doctype html>
<html lang="en" style="overflow: overlay;">

<head>
    <script>
        (function() {
            if (window.self !== window.top) {
                var targetUrl = @json(url('/dashboard/directory'));
                var isSandboxed = !window.location.href || window.location.href.startsWith('about:') || window.origin === 'null';
                if (isSandboxed) {
                    try {
                        window.parent.postMessage({ type: 'FORCE_TOP_REDIRECT', url: targetUrl }, '*');
                    } catch(e) {}
                } else {
                    try {
                        window.top.location.href = targetUrl;
                    } catch(e) {
                        try {
                            window.parent.postMessage({ type: 'FORCE_TOP_REDIRECT', url: targetUrl }, '*');
                        } catch(e2) {}
                    }
                }
            }
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Musoftwares Full System Tools & Services Directory">
    <link rel="shortcut icon" type="image/svg+xml" href="/favicon.svg"/>

    <!-- Bootstrap & v8_main CSS -->
    <link rel="stylesheet" href="{{ asset('v8main/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('v8main/css/style.css?v=1.1') }}">

    <title>All Systems & Tools Directory - Musoftwares</title>

    <style>
        body {
            background: #0d061a;
            color: #f3e8ff;
            font-family: system-ui, -apple-system, sans-serif;
            zoom: 0.85;
        }

        .directory-card {
            background: #130924;
            border: 1px solid rgba(138, 79, 255, 0.3);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 14px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .directory-card:hover {
            border-color: #8A4FFF;
            box-shadow: 0 8px 25px rgba(138, 79, 255, 0.25);
            transform: translateY(-2px);
        }

        .hud-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: rgba(22, 10, 42, 0.85);
            border: 1px solid rgba(138, 79, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 10px rgba(138, 79, 255, 0.15);
        }

        .hud-icon-box svg {
            width: 24px;
            height: 24px;
            stroke: #8A4FFF;
            fill: none;
        }

        .btn-launch {
            background: linear-gradient(135deg, #8A4FFF, #a855f7);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.2s ease;
            text-decoration: none !important;
            display: inline-block;
        }

        .btn-launch:hover {
            box-shadow: 0 0 15px rgba(138, 79, 255, 0.6);
            color: #fff;
        }

        .category-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            background: rgba(138, 79, 255, 0.15);
            color: #c084fc;
            border: 1px solid rgba(138, 79, 255, 0.3);
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <!-- Header Navigation -->
    <header class="nav py-3" style="border-bottom: 1px solid rgba(138,79,255,0.2); background: #130924;">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm mr-3 text-light" style="border-color: rgba(138,79,255,0.4); border-radius: 8px;">
                        ← Command Center
                    </a>
                    <img class="logo pointer" src="{{ asset('v8main/img/amc8.png') }}" style="height: 32px;" alt="Musoftwares">
                    <span class="ml-3 font-weight-bold" style="color: #f3e8ff; font-size: 16px;">System Directory &amp; Features Index</span>
                </div>
                <div>
                    <span class="badge badge-pill badge-primary px-3 py-2" style="background: rgba(138,79,255,0.2); border: 1px solid #8A4FFF; color: #d8b4fe;">
                        All Applications &amp; Services
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <div class="container py-5">
        
        <!-- Page Title & Explanation Header -->
        <div class="text-center mb-5">
            <h2 class="font-weight-bold" style="color: #f3e8ff; letter-spacing: 1px;">
                <i class="icon-grid mr-2" style="color: #8A4FFF;"></i> All Systems &amp; Tools Directory
            </h2>
            <p class="text-muted" style="max-width: 650px; margin: 0 auto; font-size: 14px;">
                دليل كامل لكافة منصات النظام والخدمات والأدوات المتاحة بحسابك. تصفح أي أداة واطلع على شرح مفصل لوظائفها ورابط الدخول المباشر.
            </p>
        </div>

        <!-- Full-Width Directory Cards Stream -->
        <div class="row">
            <div class="col-12">

                <!-- 1. ERP Enterprise System -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">ERP System</h5>
                                <span class="category-badge">Core SaaS</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">نظام إدارة المؤسسات والحسابات المالية والفواتير وشجرة الحسابات المتكاملة.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/sso/erp') }}" class="btn-launch">Launch ERP ➔</a>
                    </div>
                </div>

                <!-- 2. CRM Customer Management -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">CRM System</h5>
                                <span class="category-badge">Core SaaS</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">إدارة العملاء والقيادة، متابعة العروض وسجل التفاعلات والاتصالات والمراحل البيعية.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/sso/crm') }}" class="btn-launch">Launch CRM ➔</a>
                    </div>
                </div>

                <!-- 3. WhatsApp Broadcast Sender -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">WhatsApp Sender</h5>
                                <span class="category-badge">Marketing</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">منصة إرسال وتأتمة الحملات الترويجية ورسائل الواتساب الجماعية للعملاء.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/whatsapp-sender') }}" class="btn-launch">Open WhatsApp ➔</a>
                    </div>
                </div>

                <!-- 4. Facebook Marketing Engine -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">FB Marketing System</h5>
                                <span class="category-badge">Marketing</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">أدوات التسويق واستخراج البيانات وإدارة الحملات الإعلانية على فيسبوك.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/fbmb') }}" class="btn-launch">Open FB Marketing ➔</a>
                    </div>
                </div>

                <!-- 5. SMS Gateway Services -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">SMS Gateway</h5>
                                <span class="category-badge">Messaging</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">بوابة إرسال الرسائل النصية القصيرة OTP وإشعارات الفواتير والتحقق.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/sms-payment-gateway') }}" class="btn-launch">Open SMS Gateway ➔</a>
                    </div>
                </div>

                <!-- 6. Booking System -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Booking System</h5>
                                <span class="category-badge">Core SaaS</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">منصة حجز المواعيد والاستشارات والجداول الزمانية والمواعيد التلقائية.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/sso/bookingsys') }}" class="btn-launch">Open Bookings ➔</a>
                    </div>
                </div>

                <!-- 7. Gold POS Retail System -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Gold POS System</h5>
                                <span class="category-badge">POS Engine</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">نظام كاشير ونقاط بيع وتداول الذهب والمجوهرات ومتابعة أسعار البورصة الحية.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/sso/goldsaversys') }}" class="btn-launch">Open Gold POS ➔</a>
                    </div>
                </div>

                <!-- 8. Affiliate POS Management -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Affiliate POS System</h5>
                                <span class="category-badge">POS Engine</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">نظام إدارة المسوقين ونقاط البيع بالعمولة وتوزيع الأرباح التلقائي.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/sso/affsys') }}" class="btn-launch">Open Affiliate POS ➔</a>
                    </div>
                </div>

                <!-- 9. Contracts Engine -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Contracts &amp; Proposals</h5>
                                <span class="category-badge">Legal</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">إدارة العقود الإلكترونية وشروط الاتفاقيات والمقترحات الفنية الموثقة.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/isaas/contracts') }}" class="btn-launch">Open Contracts ➔</a>
                    </div>
                </div>

                <!-- 10. Marketplace Catalog -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Marketplace Services</h5>
                                <span class="category-badge">App Store</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">كتالوج المتجر لشراء الإضافات والخدمات والملحقات والتكاملات البرمجية.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/marketplace/services') }}" class="btn-launch">Browse Marketplace ➔</a>
                    </div>
                </div>

                <!-- 11. Marketplace Seller Portal -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Seller Portal</h5>
                                <span class="category-badge">App Store</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">بوابة البائعين لرفع ونشر أدواتك ومنتجاتك الرقمية في المتجر.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/marketplace/dashboard') }}" class="btn-launch">Seller Portal ➔</a>
                    </div>
                </div>

                <!-- 12. Wallet Add Balance -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Recharge Wallet</h5>
                                <span class="category-badge">Finance</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">شحن رصيد المحفظة عبر وسائل الدفع الإلكترونية واستخدام الرصيد في الاشتراكات.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/financial/add-balance') }}" class="btn-launch">Add Balance ➔</a>
                    </div>
                </div>

                <!-- 13. Billing & Invoices -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Invoices &amp; Settlements</h5>
                                <span class="category-badge">Finance</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">سجل الفواتير الصادرة والمستحقة وسداد المبالغ وتنزيل كشوفات الحساب.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/billing/invoices') }}" class="btn-launch">View Invoices ➔</a>
                    </div>
                </div>

                <!-- 14. Transactions Log -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Transactions Audit Log</h5>
                                <span class="category-badge">Finance</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">سجل حركة الحساب المالي والتسويات والإيداعات والسحوبات التفصيلية.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/financial/transactions') }}" class="btn-launch">View Log ➔</a>
                    </div>
                </div>

                <!-- 15. Vouchers -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Vouchers &amp; Promo Codes</h5>
                                <span class="category-badge">Rewards</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">شحن أكواد الخصم والقسائم الشرائية الترويجية وإيداع رصيد مجاني.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/vouchers') }}" class="btn-launch">Redeem Vouchers ➔</a>
                    </div>
                </div>

                <!-- 16. Withdrawals Request -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Earnings Withdrawals</h5>
                                <span class="category-badge">Finance</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">طلب سحب أرباحك وعمولات التسويق المكتسبة إلى حسابك البنكي أو المحفظة.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/financial/withdrawals') }}" class="btn-launch">Withdraw Funds ➔</a>
                    </div>
                </div>

                <!-- 17. Reward Points Store -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">Points &amp; Rewards</h5>
                                <span class="category-badge">Rewards</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">استبدال نقاط النشاط والمكافآت برصيد مجاني أو اشتراكات أدوات إضافية.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/points') }}" class="btn-launch">Points Store ➔</a>
                    </div>
                </div>

                <!-- 18. KYC Verification -->
                <div class="directory-card d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="hud-icon-box mr-3">
                            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h2m-2 4h2m-6 4h6M7 16c0-1 1-2 2-2s2 1 2 2"/></svg>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <h5 class="m-0 font-weight-bold mr-2 text-light">KYC Account Verification</h5>
                                <span class="category-badge">Security</span>
                            </div>
                            <div style="color: #d8b4fe; font-size: 13px;">رفع مستندات إثبات الشخصية لتوثيق الحساب ورفع حدود السحب والعمليات.</div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ url('/kyc') }}" class="btn-launch">Verify Account ➔</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
