<?php

namespace App\Services;

class PortfolioData
{
    /**
     * Get all authentic static portfolio projects strictly organized by categories.
     *
     * @return array<string, array>
     */
    public static function all(): array
    {
        return [
            // ==========================================
            // 1. Web Apps (تطبيقات ومواقع الويب)
            // ==========================================
            'kbdny' => [
                'slug' => 'kbdny',
                'title_en' => 'Kbdny Dropshipping & Multi-Vendor Ecosystem',
                'title_ar' => 'منصة كبدني (Kbdny) للتجارة والتسويق بالعمولة والدروب شيبنج',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'High-scale multi-tier dropshipping platform connecting suppliers, affiliate marketers, and logistics providers with automated commission payouts and bulk shipping manifests.',
                'desc_ar' => 'منصة دروبشيبينغ وتسويق بالعمولة تربط الموردين والمسوقين وشركات الشحن مع حساب العمولات آلياً ومتابعة بوالص الشحن.',
                'img' => '/images/portfolio/kbdny.png',
                'techs' => ['Laravel', 'Alpine.js', 'MySQL', 'Queue Workers', 'TailwindCSS'],
                'live_url' => null,
                'metrics' => [
                    'Volume' => '10k+ Orders / Month',
                    'Shipping' => 'B2B Courier Integration',
                    'Commissions' => 'Real-Time Wallet System',
                    'Inventory' => 'Multi-Vendor SKU Sync',
                ],
                'highlights_en' => [
                    'Multi-role portals for Affiliates, Suppliers, Warehouse Managers, and Admins.',
                    'Automated order state lifecycle: Confirmed, Dispatched, Partial Delivery, and Returned.',
                    'Bulk Excel manifest generation and tracking number sync for courier companies.',
                    'Affiliate digital wallet with automated commission calculation upon delivery.',
                ],
                'highlights_ar' => [
                    'بوابات مخصصة لكل من: المسوقين، التجار الموردين، مسؤولي المخازن، والإدارة.',
                    'دورة حياة ذكية للطلبات: تأكيد، شحن، تسليم جزئي، ومرتجع مع تحديثات فورية.',
                    'تصدير واستيراد كشوفات الشحن المجمعة ومزامنة بوالص الشحن.',
                    'محفظة مالية رقمية للمسوقين تحسب العمولات تلقائياً عند تأكيد التسليم.',
                ],
            ],

            'mini-fatora' => [
                'slug' => 'mini-fatora',
                'title_en' => 'Mini Fatora E-Invoicing & Billing SaaS',
                'title_ar' => 'منصة ميني فاتورة (Mini Fatora) للفوترة السريعة وإدارة الفواتير',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'Lightweight, rapid billing platform for freelancers and service agencies to issue branded PDF invoices, track payment status, and accept online transactions.',
                'desc_ar' => 'نظام سحابي لإصدار الفواتير الاحترافية ومتابعة تحصيل المدفوعات وتوليد فواتير PDF بضغطة زر للمستقلين والشركات.',
                'img' => '/images/portfolio/minifatora.png',
                'techs' => ['Laravel', 'Vue.js', 'DomPDF', 'MySQL', 'TailwindCSS'],
                'live_url' => null,
                'metrics' => [
                    'Generation' => 'Instant PDF Rendering',
                    'Sharing' => 'One-Click WhatsApp / Email',
                    'Currencies' => 'Multi-Currency Support',
                    'Reminders' => 'Automated Due Date Alerts',
                ],
                'highlights_en' => [
                    'Customizable invoice templates with brand logo, terms, and tax QR codes.',
                    'Client payment portal with online card and wallet checkout integration.',
                    'Automatic payment reminder dispatch via email and WhatsApp upon overdue dates.',
                    'Comprehensive income analytics, recurring subscriptions, and tax summaries.',
                ],
                'highlights_ar' => [
                    'قوالب فواتير قابلة للتخصيص بالهوية والشعار ورموز الاستجابة السريعة (QR).',
                    'بوابة دفع مخصصة للعميل لسداد الفواتير عبر البطاقات والمحافظ الإلكترونية.',
                    'إرسال تذكيرات دفع تلقائية عند اقتراب أو تأخر موعد السداد.',
                    'تحليلات شاملة للمقبوضات، الفواتير المتكررة، وملخصات الضرائب الدورية.',
                ],
            ],

            'trenz-whatscrm' => [
                'slug' => 'trenz-whatscrm',
                'title_en' => 'Trenz whatsCRM & WhatsApp Booking Platform',
                'title_ar' => 'منصة Trenz whatsCRM لخدمة العملاء وحجز المواعيد',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'Comprehensive WhatsApp Business automation suite featuring appointment booking, broadcast scheduling, multi-agent inbox, and Meta Graph API webhook pipelines.',
                'desc_ar' => 'منصة سحابية متكاملة لخدمة العملاء وحجز المواعيد عبر واتساب مع صندوق وارد موحد لفريق العمل وبث الحملات.',
                'img' => '/images/portfolio/trenz-whatscrm.png',
                'techs' => ['Laravel', 'Vue/React', 'Meta Cloud API', 'WebSockets', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Throughput' => '100+ msgs / sec',
                    'Integration' => 'Official Meta Graph API',
                    'Agents' => 'Multi-agent Collaboration',
                    'Automations' => 'Smart Keyword Routing',
                ],
                'highlights_en' => [
                    'Official Meta Cloud API integration with automatic template approval syncing.',
                    'Appointment booking engine with automated reminder messages before consultations.',
                    'Multi-agent unified inbox with chat assignment, internal notes, and tagging.',
                    'Automated conversational chatbots with interactive buttons and list menus.',
                ],
                'highlights_ar' => [
                    'ربط رسمي مع Meta Cloud API ومزامنة قوالب الرسائل المعتمدة.',
                    'نظام متقدم لحجز المواعيد مع إرسال تذكيرات آلية قبل الموعد.',
                    'صندوق وارد موحد متعدد الوكلاء لتوزيع المحادثات وإضافة الملاحظات الداخلية.',
                    'روبوتات رد تفاعلية بالقوائم والأزرار للرد الفوري على استفسارات العملاء.',
                ],
            ],

            'amc-academy' => [
                'slug' => 'amc-academy',
                'title_en' => 'AMC Academy E-Learning & Video Streaming Platform',
                'title_ar' => 'أكاديمية AMC Academy للتعليم والتدريب وبث الفيديو المشفر',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'Scalable learning management system with DRM-protected video delivery, dynamic watermarking, interactive quizzes, student progress tracking, and certificate issuance.',
                'desc_ar' => 'منصة تعليمية وبث فيديو مشفر بحماية كاملة من التسجيل، علامات مائية ديناميكية، اختبارات ذكية، وشهادات معتمدة.',
                'img' => '/images/portfolio/amcacademy.jpg',
                'techs' => ['Laravel', 'React', 'Video.js', 'FFmpeg', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Protection' => 'Dynamic User Watermarking',
                    'Streaming' => 'HLS Adaptive Bitrate',
                    'Assessment' => 'Automated Quiz Grading',
                    'Engagement' => 'Student Progress Analytics',
                ],
                'highlights_en' => [
                    'Anti-piracy dynamic screen watermarks displaying student IP and phone number.',
                    'HLS adaptive video encoding for seamless playback on low-bandwidth networks.',
                    'Automated grading engine with timed exam constraints and randomized question banks.',
                    'Certificate generation with verifiable cryptographic QR validation codes.',
                ],
                'highlights_ar' => [
                    'حماية متقدمة ضد تسريب المحتوى بعلامات مائية تظهر بيانات الطالب لحظياً.',
                    'بث فيديو تكيفي (HLS) يعمل بكفاءة عالية حتى مع سرعات الإنترنت الضعيفة.',
                    'نظام اختبارات ذكي بالتصحيح التلقائي وتحديد أوقات الإجابة وبنوك الأسئلة.',
                    'إصدار شهادات إتمام الدورات مع رمز استجابة سريعة (QR) للتحقق من المصداقية.',
                ],
            ],

            'vodafone-crm' => [
                'slug' => 'vodafone-crm',
                'title_en' => 'Vodafone Distribution Operations & CRM',
                'title_ar' => 'نظام إدارة الموزعين والعمليات الميدانية Vodafone CRM',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'Mission-critical enterprise CRM developed for a primary telecom distributor, handling thousands of line activations, scratch card serial tracking, and representative commissions.',
                'desc_ar' => 'لوحة تحكم وعمليات ميدانية للموزعين لإدارة شبكة المناديب والمحلات، تتبع شرائح الخطوط التسلسلية، وحساب العمولات.',
                'img' => '/images/portfolio/vodafone-crm.jpg',
                'techs' => ['Laravel', 'PostgreSQL', 'Livewire', 'Excel Engine', 'Redis'],
                'live_url' => null,
                'metrics' => [
                    'Scale' => '50k+ SIM Serial Records',
                    'Representatives' => 'GPS & Route Tracking',
                    'Reconciliation' => 'End-of-Day Balance Audit',
                    'Security' => 'Role-Based Operational Access',
                ],
                'highlights_en' => [
                    'Comprehensive serial number lifecycle tracking from central warehouse to retail shops.',
                    'Representative route planning, cash collection verification, and daily targets.',
                    'Dynamic tiered commission calculation based on activated line volume and packages.',
                    'Real-time discrepancy detection between warehouse dispatches and collected balances.',
                ],
                'highlights_ar' => [
                    'تتبع دقيق لحركة الأرقام التسلسلية من المستودع الرئيسي حتى وصولها للمحلات.',
                    'إدارة خطوط سير المناديب، استلام العهد النقدية، ومتابعة الأهداف البيعية.',
                    'حساب عمولات المناديب والموزعين آلياً بناءً على الشرائح وحجم المبيعات.',
                    'مطابقة فورية لمنع أي فروقات بين البضائع المسلمة والنقدية المحصلة.',
                ],
            ],

            'telecom-system' => [
                'slug' => 'telecom-system',
                'title_en' => 'B2B Telecom & ISP Automated Recharge Engine',
                'title_ar' => 'بوابة شحن وإدارة خدمات الاتصالات والإنترنت Telecom System B2B',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'High-throughput telecom gateway communicating with ISP APIs to execute instant automated bill recharges, bundle activations, and quota monitoring for enterprise clients.',
                'desc_ar' => 'بوابة شحن وإدارة خدمات الاتصالات B2B متصلة بواجهات مزودي الإنترنت لمعالجة المدفوعات والاشتراكات الفورية.',
                'img' => '/images/portfolio/telecom-system.png',
                'techs' => ['Laravel', 'RabbitMQ', 'Redis', 'REST APIs', 'PostgreSQL'],
                'live_url' => null,
                'metrics' => [
                    'Execution' => '< 1.2s API Response',
                    'Reliability' => '99.98% Gateway Uptime',
                    'Concurrency' => 'Bulk Batch Requests',
                    'Security' => 'Encrypted Token Handshake',
                ],
                'highlights_en' => [
                    'Direct asynchronous API connectors to top internet service providers and telecoms.',
                    'Automated retry and fallback routing for transient carrier timeouts.',
                    'B2B merchant wallet architecture with pre-funded balances and credit limits.',
                    'Detailed transactional log auditing with raw payload inspection for debugging.',
                ],
                'highlights_ar' => [
                    'ربط مباشر بواجهات مزودي خدمات الإنترنت والاتصالات لتنفيذ العمليات فوراً.',
                    'نظام ذكي لإعادة المحاولة والتحويل التلقائي في حال انقطاع أي مزود.',
                    'محافظ مالية مسبقة الدفع للتجار مع إدارة حدود الائتمان وسجلات العمليات.',
                    'سجل عمليات مفصل يتيح فحص البيانات التقنية لضمان دقة المعاملات.',
                ],
            ],

            'altayaraa' => [
                'slug' => 'altayaraa',
                'title_en' => 'Altayaraa High-Traffic E-Commerce Platform',
                'title_ar' => 'منصة ومتجر الطيارة (Altayaraa) للتجارة الإلكترونية السريعة',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'Fast e-commerce storefront engineered for viral social media campaigns, featuring 1-click cash-on-delivery checkout, abandoned cart recovery, and warehouse integration.',
                'desc_ar' => 'متجر إلكتروني سريع مصمم للحملات الإعلانية المكثفة، يدعم الشراء السريع بنقرة واحدة (COD) ومزامنة المخازن والشحن.',
                'img' => '/images/portfolio/altayaraa.png',
                'techs' => ['Laravel', 'Alpine.js', 'MySQL', 'TailwindCSS', 'Redis'],
                'live_url' => null,
                'metrics' => [
                    'Checkout' => 'Single-Page COD Funnel',
                    'Conversion' => '+34% Boost over standard carts',
                    'Load Time' => 'Sub-800ms First Contentful Paint',
                    'Mobile' => '100% Touch-Optimized',
                ],
                'highlights_en' => [
                    'Frictionless 1-page checkout without mandatory account registration.',
                    'Automated SMS & WhatsApp verification triggers to reduce fake order rates.',
                    'Dynamic upsell and cross-sell quantity discounts right on the product page.',
                    'Direct automated syncing with fulfillment centers and shipping couriers.',
                ],
                'highlights_ar' => [
                    'شراء فوري من صفحة واحدة دون اشتراط إنشاء حساب لرفع نسبة التحويل.',
                    'تأكيد الطلبات تلقائياً عبر الرسائل النصية وواتساب لتقليل نسبة المرتجعات.',
                    'عروض بيع إضافية (Upsells) وخصومات كميات تفاعلية في صفحة المنتج.',
                    'ربط مباشر مع غرف التعبئة وشركات الشحن لإصدار البوالص آلياً.',
                ],
            ],

            'project-manager' => [
                'slug' => 'project-manager',
                'title_en' => 'Enterprise Project & Agile Task Manager',
                'title_ar' => 'منصة إدارة المشاريع وفرق العمل Project Manager',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'Collaborative engineering workspace featuring Kanban boards, Gantt timelines, live time-tracking counters, milestone deliverables, and automated client status reporting.',
                'desc_ar' => 'منصة سحابية لإدارة المشاريع وفرق العمل تدعم لوحات كانبان، تسجيل ساعات العمل بدقة، وإصدار التقارير للمشتركين.',
                'img' => '/images/portfolio/projectmanager.png',
                'techs' => ['Laravel', 'React', 'TypeScript', 'TailwindCSS', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Tracking' => 'Sub-second Timers',
                    'Views' => 'Kanban, List, Gantt, Calendar',
                    'Collaboration' => 'Real-time Task Updates',
                    'Reporting' => 'Automated Client Portals',
                ],
                'highlights_en' => [
                    'Interactive drag-and-drop Kanban boards with custom status pipelines and priority filters.',
                    'Integrated stopwatch timer recording billable hours and generating direct client invoices.',
                    'Granular milestone progress bars and task dependency relationship mapping.',
                    'Secure client view portal allowing stakeholders to monitor project velocity in real-time.',
                ],
                'highlights_ar' => [
                    'لوحات كانبان تفاعلية بالسحب والإفلات مع تخصيص مراحل العمل والأولويات.',
                    'مؤقتات زمنية مدمجة لتسجيل ساعات العمل القابلة للفوترة وإصدار فواتيرها.',
                    'تتبع نسب إنجاز المعالم الرئيسية وربط المهام بتبعيات التنفيذ.',
                    'بوابة وصول آمنة للعملاء لمتابعة سير تنفيذ مشاريعهم بشفافية واحترافية.',
                ],
            ],

            // ==========================================
            // 2. Mobile Apps (تطبيقات الموبايل)
            // ==========================================
            'nokhpa' => [
                'slug' => 'nokhpa',
                'title_en' => 'Nokhpa Premium E-Commerce Mobile App',
                'title_ar' => 'تطبيق النخبة (Nokhpa) للتسوق الإلكتروني وتتبع الـ GPS',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'Luxury retail mobile shopping application featuring curated product lookbooks, personalized recommendations, Apple Pay integration, and live GPS order tracking.',
                'desc_ar' => 'تطبيق تسوق إلكتروني متكامل للجوال مع تتبع الـ GPS لمندوب التوصيل، دفع إلكتروني، وتجربة تسوق ذكية.',
                'img' => '/images/portfolio/nokhpa.png',
                'techs' => ['Flutter', 'Laravel REST API', 'Apple Pay', 'Google Maps API', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Design' => 'High-Fashion Minimal Aesthetic',
                    'Checkout' => 'Apple Pay & Card Gateways',
                    'Tracking' => 'Live Courier Geolocation',
                    'Notifications' => 'Rich Media Push Alerts',
                ],
                'highlights_en' => [
                    'Seamless native checkout with Apple Pay, credit cards, and installment plans (Tabby/Tamara).',
                    'Interactive product storytelling with high-resolution image galleries and size guides.',
                    'Real-time delivery driver tracking on interactive vector maps.',
                    'Smart customer loyalty club rewarding repeated purchases with discount points.',
                ],
                'highlights_ar' => [
                    'دفع فوري متكامل مع Apple Pay والبطاقات البنكية وأنظمة التقسيط.',
                    'عرض بصري فائق الدقة للمنتجات مع تفاصيل المقاسات والمواصفات.',
                    'تتبع حي لموقع مندوب التوصيل على الخريطة التفاعلية حتى باب المنزل.',
                    'نظام ولاء ومكافآت ذكي يمنح نقاطاً وخصومات عند كل عملية شراء.',
                ],
            ],

            'forex-app' => [
                'slug' => 'forex-app',
                'title_en' => 'Forex Signal & Trading Mobile App',
                'title_ar' => 'تطبيق إشارات وتنبيهات أسواق المال والعملات Forex App',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'Real-time financial trading companion delivering sub-second market alerts, technical indicator overlays, and algorithmic copy-trade notifications to traders globally.',
                'desc_ar' => 'تطبيق إشارات وتنبيهات أسواق المال والعملات للجوال يرسل تنبيهات الصفقات اللحظية ومؤشرات التحليل الفني بدقة عالية.',
                'img' => '/images/portfolio/forex-app.png',
                'techs' => ['Flutter', 'Node.js', 'WebSockets', 'TradingView API', 'Redis'],
                'live_url' => null,
                'metrics' => [
                    'Alert Speed' => '< 150ms Push Notification',
                    'Feeds' => 'Live MT4 / MT5 Integration',
                    'Charts' => 'Embedded Interactive Canvas',
                    'Security' => 'Biometric Authentication',
                ],
                'highlights_en' => [
                    'Ultra-low-latency push notifications with exact Entry, Take-Profit, and Stop-Loss levels.',
                    'Live chart integrations with custom technical indicator overlays (RSI, MACD, Bollinger).',
                    'Historical signal performance tracker with transparent win-rate metrics.',
                    'Subscription tier gating powered by in-app purchases and digital wallet tokens.',
                ],
                'highlights_ar' => [
                    'إشعارات فورية سريعة بنقاط الدخول وجني الأرباح ووقف الخسارة بدقة.',
                    'رسوم بيانية حية مع مؤشرات التحليل الفني المتقدمة لتسهيل اتخاذ القرار.',
                    'سجل شفاف لنتائج الإشارات السابقة ونسب نجاح الصفقات التاريخية.',
                    'نظام اشتراكات وباقات متميزة مع بوابات دفع آمنة.',
                ],
            ],

            'wallet-app' => [
                'slug' => 'wallet-app',
                'title_en' => 'Digital Wallet & Instant P2P Payment App',
                'title_ar' => 'تطبيق المحفظة الرقمية وتحويل الأموال للموبايل Wallet App',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'Fintech mobile wallet providing instant peer-to-peer money transfers, QR code merchant payments, virtual cards, and biometric security.',
                'desc_ar' => 'تطبيق المحفظة الرقمية وتحويل الأموال للموبايل مع مسح QR للدفع الفوري ومتابعة الرصيد والمعاملات بأعلى أمان.',
                'img' => '/images/portfolio/wallet-app.png',
                'techs' => ['Flutter', 'Laravel API', 'PostgreSQL', 'Biometric Auth', 'WebSockets'],
                'live_url' => null,
                'metrics' => [
                    'Transfer Speed' => '< 500ms P2P Settlement',
                    'Security' => 'End-to-End Key Encryption',
                    'QR Payments' => 'Instant Merchant Scan',
                    'Cards' => 'Virtual Card Provisioning',
                ],
                'highlights_en' => [
                    'Instant zero-fee peer-to-peer transfers using mobile phone numbers or user handles.',
                    'Merchant QR checkout enabling frictionless payments in retail stores.',
                    'Biometric security with face recognition and fingerprint payment authorization.',
                    'Categorized monthly spending analytics and budget tracking.',
                ],
                'highlights_ar' => [
                    'تحويلات مالية فورية ومجانية بين المستخدمين عبر رقم الهاتف أو اسم المستخدم.',
                    'دفع فوري لدى التجار والمحلات بمسح رمز الاستجابة السريعة (QR Code).',
                    'أمان معزز بمصادقة بصمة الوجه والإصبع لتأكيد كافة المعاملات.',
                    'تقارير تحليلية للمصروفات الشهرية وتصنيف النفقات تلقائياً.',
                ],
            ],

            'qcoin-wallet' => [
                'slug' => 'qcoin-wallet',
                'title_en' => 'QCoin Crypto & Digital Investment App',
                'title_ar' => 'تطبيق متابعة الاستثمارات والعملات الرقمية QCoin App',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'Non-custodial crypto wallet and transaction tracker featuring real-time token valuation charts, QR address generator, and automated peer-to-peer transfers.',
                'desc_ar' => 'تطبيق متابعة الاستثمارات والعملات الرقمية للجوال مع محافظ مشفرة، أسعار الصرف الحية، وتتبع حركة المحفظة.',
                'img' => '/images/portfolio/qcoin-app.png',
                'techs' => ['Flutter', 'Node.js', 'Web3 APIs', 'PostgreSQL', 'TailwindCSS'],
                'live_url' => null,
                'metrics' => [
                    'Security' => 'Client-Side Key Encryption',
                    'Tracking' => 'Live Price Tickers',
                    'Transfers' => 'Instant QR Scanning',
                    'Auditing' => 'Full Ledger Explorer',
                ],
                'highlights_en' => [
                    'Encrypted key vault with biometric unlocking for secure on-device asset management.',
                    'Real-time market price charts with percentage changes and profit/loss calculation.',
                    'QR code scanning and generation for instant, error-free wallet address transfers.',
                    'Integrated transaction history explorer with transaction hash verification.',
                ],
                'highlights_ar' => [
                    'خزنة مشفرة بمصادقة البصمة لإدارة الأصول الرقمية بأعلى معايير الأمان.',
                    'رسوم بيانية لأسعار السوق المحدثة لحظياً مع حساب الأرباح والخسائر التلقائي.',
                    'توليد ومسح رموز الـ QR لتحويل الأموال واستقبالها بدون أي أخطاء في العناوين.',
                    'سجل معاملات تاريخي شامل مع فحص كود المعاملة (Tx Hash).',
                ],
            ],

            'amc-social' => [
                'slug' => 'amc-social',
                'title_en' => 'AMC Social Student Community Mobile App',
                'title_ar' => 'تطبيق مجتمع وفعاليات الطلاب AMC Social',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'Interactive student community mobile app enabling learners to join study groups, access schedules, participate in live campus discussions, and receive instant push notifications.',
                'desc_ar' => 'تطبيق مجتمع وفعاليات الطلاب على الهواتف الذكية للتواصل، متابعة المحاضرات والأنشطة، وتبادل الملفات.',
                'img' => '/images/portfolio/amcsocial.png',
                'techs' => ['Flutter', 'Laravel REST API', 'Firebase FCM', 'WebSockets', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Engagement' => 'Live Group Feeds',
                    'Notifications' => 'Sub-second Broadcast',
                    'Community' => 'Direct Peer Messaging',
                    'Media' => 'In-app File Sharing',
                ],
                'highlights_en' => [
                    'Dedicated community feed with likes, comments, and media attachments.',
                    'Real-time private and group chat with instant push delivery.',
                    'Event calendar with one-tap RSVP and schedule reminders.',
                    'Course study groups organized by instructor and topic.',
                ],
                'highlights_ar' => [
                    'خلاصة تفاعلية للمنشورات مع دعم التعليقات ومشاركة الوسائط والملفات.',
                    'محادثات فورية فردية وجماعية مع وصول فوري للإشعارات.',
                    'تقويم تفاعلي للأنشطة والفعاليات مع إمكانية تأكيد الحضور بضغطة واحدة.',
                    'مجموعات دراسية مخصصة حسب المواد الأكاديمية والأساتذة.',
                ],
            ],

            // ==========================================
            // 3. Desktop Apps (برامج سطح المكتب والأتمتة)
            // ==========================================
            'stock-manager' => [
                'slug' => 'stock-manager',
                'title_en' => 'StockManager POS & Inventory Desktop Software',
                'title_ar' => 'برنامج كاشير ونقاط البيع وإدارة المخازن Stock Manager',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'High-performance desktop Point of Sale and inventory suite built for retail and wholesale businesses with barcode scanning, shift reconciliations, and offline resilience.',
                'desc_ar' => 'برنامج كاشير ونقاط البيع وإدارة المخازن للكمبيوتر POS يدعم الباركود، الفواتير الحرارية، وإدارة الشيفتات والتقارير.',
                'img' => '/images/portfolio/stockmanager.png',
                'techs' => ['C#', '.NET', 'WPF', 'SQLite', 'ESC/POS SDK'],
                'live_url' => null,
                'metrics' => [
                    'POS Speed' => '< 50ms Item Scan',
                    'Multi-Branch' => 'Real-Time Stock Transfers',
                    'Hardware' => 'ESC/POS Thermal Printers',
                    'Security' => 'Shift Balance Reconciliation',
                ],
                'highlights_en' => [
                    'Rapid keyboard-driven POS interface with instant barcode scanner support.',
                    'Inter-branch stock transfer requests with digital dispatch and receipt verification.',
                    'Automated low-stock threshold notifications and suggested purchase orders.',
                    'Cashier drawer opening, shift opening/closing, and daily variance reports.',
                ],
                'highlights_ar' => [
                    'واجهة كاشير ونقاط بيع فائقة السرعة تدعم اختصارات لوحة المفاتيح والباركود.',
                    'إدارة التحويلات المخزنية بين الفروع مع اعتمادات الاستلام والتسليم.',
                    'تنبيهات تلقائية بوصول المخزون لحد الطلب وتوليد أوامر الشراء.',
                    'إدارة أدراج النقدية، فتح وإغلاق الشيفتات، وتقارير العجز والزيادة اليومية.',
                ],
            ],

            'whatsapp-sender' => [
                'slug' => 'whatsapp-sender',
                'title_en' => 'WhatsApp Sender Desktop Automation Suite',
                'title_ar' => 'برنامج أتمتة إرسال رسائل الواتساب WhatsApp Sender',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Desktop marketing engine designed to dispatch personalized customer notifications and marketing media with automated delay controls and number verification.',
                'desc_ar' => 'برنامج أتمتة إرسال رسائل الواتساب لسطح المكتب مع فواصل زمنية متغيرة، تخصيص المتغيرات، وفحص الأرقام المسجلة.',
                'img' => '/images/portfolio/whatsapp-sender.png',
                'techs' => ['C#', 'Selenium Core', 'Chromium Driver', 'SQLite', 'WPF'],
                'live_url' => null,
                'metrics' => [
                    'Personalization' => 'Dynamic {{Name}} Tags',
                    'Safety' => 'Human-Like Random Delay Engine',
                    'Attachments' => 'PDFs, Images, Videos, Audio',
                    'Filter' => 'WhatsApp Number Validator',
                ],
                'highlights_en' => [
                    'Smart random delay engine imitating human typing cadence to preserve account safety.',
                    'Dynamic message variables pulling customized client data directly from Excel sheets.',
                    'Integrated filter scanning contact lists to verify WhatsApp registration prior to broadcast.',
                    'Multi-account rotation alternating between numbers during large notification campaigns.',
                ],
                'highlights_ar' => [
                    'نظام محاكاة الكتابة البشرية وفواصل زمنية متغيرة لحماية الحسابات.',
                    'دعم المتغيرات المخصصة لجلب أسماء وتفاصيل الفواتير من ملف الإكسيل لكل عميل.',
                    'فاحص مدمج للتحقق من وجود الحساب على واتساب قبل بدء الإرسال.',
                    'دعم التبديل بين عدة حسابات وأرقام بالتناوب خلال الحملات الإعلانية الكبيرة.',
                ],
            ],

            'telegram-sender' => [
                'slug' => 'telegram-sender',
                'title_en' => 'Telegram Sender Broadcaster & Community Engine',
                'title_ar' => 'برنامج البث والنشر التلقائي على تيليجرام Telegram Sender',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'High-speed broadcast desktop software utilizing native MTProto APIs to deliver announcements, signals, and media across thousands of Telegram groups and channels simultaneously.',
                'desc_ar' => 'برنامج البث والنشر التلقائي على تيليجرام للويندوز يتيح النشر المجدول وإدارة الحسابات المتعددة ببروتوكول MTProto السريع.',
                'img' => '/images/portfolio/telegram-sender.png',
                'techs' => ['C#', 'TDLib / WTelegramClient', 'SQLite', '.NET Core'],
                'live_url' => null,
                'metrics' => [
                    'Speed' => 'Native MTProto Sub-second Dispatch',
                    'Multi-Session' => 'Unlimited Account Switching',
                    'Targeting' => 'Groups, Channels, and Direct Users',
                    'Scheduler' => 'Time-based Automated Queue',
                ],
                'highlights_en' => [
                    'Native Telegram MTProto integration ensuring maximum transmission speed and reliability.',
                    'Multi-session manager maintaining multiple logged-in accounts safely without re-auth.',
                    'Group member scraper extracting active discussion participants into targeted broadcast lists.',
                    'Automated message scheduling with rich formatting, inline buttons, and pinned messages.',
                ],
                'highlights_ar' => [
                    'ربط مباشر ببروتوكول MTProto لسرعة إرسال قياسية واستقرار تام.',
                    'إدارة جلسات متعددة تتيح تشغيل عدة حسابات بأمان ودون الحاجة لتسجيل الدخول المتكرر.',
                    'استخراج أعضاء المجموعات النشطين لإنشاء قوائم استهداف مهتمة.',
                    'جدولة النشر التلقائي مع دعم الأزرار الشفافة وتنسيقات النصوص الغنية وتثبيت الرسائل.',
                ],
            ],

            'inbox-sender' => [
                'slug' => 'inbox-sender',
                'title_en' => 'Inbox Sender Desktop Warmup & Delivery Engine',
                'title_ar' => 'برنامج إرسال البريد وتدوير الـ SMTP للكمبيوتر Inbox Sender',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Desktop application designed for transactional email warmup, multi-threaded SMTP testing, and targeted corporate outreach with customized header fingerprints.',
                'desc_ar' => 'برنامج إرسال البريد الإلكتروني وتدوير الـ SMTP للكمبيوتر مع فحص الـ DNS وتسخين السيرفرات لضمان الوصول للـ Inbox.',
                'img' => '/images/portfolio/inbox-sender.png',
                'techs' => ['C#', '.NET Core', 'MailKit', 'MimeKit', 'SQLite'],
                'live_url' => null,
                'metrics' => [
                    'Throughput' => 'Multi-threaded SMTP Engine',
                    'Warmup' => 'Gradual Ramp-Up Scheduling',
                    'Testing' => 'Spam Score & DNS Pre-flight Check',
                    'Security' => 'TLS 1.3 / SSL Encryption',
                ],
                'highlights_en' => [
                    'Automated gradual warmup schedules training spam filters to recognize new domains as safe.',
                    'Comprehensive DNS diagnostic verifying SPF, DKIM, DMARC, and MX records before sending.',
                    'Spam trigger word analyzer warning users about dangerous promotional copy.',
                    'Threaded sending queue distributing emails across multiple authenticated accounts.',
                ],
                'highlights_ar' => [
                    'جدولة تدريجية لتسخين الدومينات الجديدة لبناء سمعة ممتازة لدى مزودي البريد.',
                    'فحص شامل لإعدادات الـ DNS (SPF, DKIM, DMARC) قبل بدء الإرسال لضمان الأمان.',
                    'محلل ذكي يفحص نصوص الرسائل وينبه المستخدم عند وجود كلمات قد تسبب الحظر.',
                    'توزيع الحمل على خيوط معالجة متعددة لتسريع وتيرة الإرسال دون إجهاد السيرفر.',
                ],
            ],

            'chartcash' => [
                'slug' => 'chartcash',
                'title_en' => 'ChartCash Financial Accounting & BI Desktop Platform',
                'title_ar' => 'برنامج ومنصة القيود والتحليلات المالية المتقدمة ChartCash',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Advanced financial ledger and business intelligence software providing real-time cash flow projections, double-entry journals, and interactive revenue analytics.',
                'desc_ar' => 'برنامج ومنصة القيود والتحليلات المالية المتقدمة، يدعم القيود المحاسبية، تقارير الأرباح والخسائر، ولوحات القيادة التفاعلية.',
                'img' => '/images/portfolio/chartcash.png',
                'techs' => ['C#', '.NET', 'WPF', 'SQLite', 'LiveCharts'],
                'live_url' => null,
                'metrics' => [
                    'Processing' => 'Real-Time Aggregations',
                    'Dashboards' => 'Customizable BI Widgets',
                    'Visualization' => 'Interactive Canvas',
                    'Export' => 'PDF / Excel Reports',
                ],
                'highlights_en' => [
                    'Automated cash flow heatmaps and predictive burn-rate analytics.',
                    'Double-entry journal engine with multi-currency conversion.',
                    'Dynamic customer lifetime value (LTV) and profit margin tracking.',
                    'Executive reporting export in Excel and printable PDF formats.',
                ],
                'highlights_ar' => [
                    'خرائط حرارية للتدفقات النقدية ومعدل حرق السيولة المالي.',
                    'محرك قيود مزدوجة متكامل مع تحويل العملات المتعددة.',
                    'تتبع هوامش الربحية والقيمة الدائمة للعملاء.',
                    'تصدير تقارير تنفيذية عالية الجودة للإدارات وصناع القرار.',
                ],
            ],

            'stocktalk-ai' => [
                'slug' => 'stocktalk-ai',
                'title_en' => 'StockTalk AI Smart Conversational Bot & Engine',
                'title_ar' => 'محرك ومساعد الذكاء الاصطناعي التفاعلي StockTalk AI',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Intelligent conversational AI agent connected to ERP databases via WhatsApp and Desktop hooks, answering inquiries, quoting prices, and placing orders 24/7.',
                'desc_ar' => 'محرك ومساعد الذكاء الاصطناعي للرد على العملاء، فحص المخزون والأسعار، وإتمام المعاملات آلياً على مدار الساعة.',
                'img' => '/images/portfolio/stocktalk.png',
                'techs' => ['Python', 'OpenAI API', 'Desktop Runner', 'SQLite', 'Redis'],
                'live_url' => null,
                'metrics' => [
                    'Response Time' => '< 1.8s AI Natural Reply',
                    'Integration' => 'Direct Live Database Queries',
                    'Availability' => '24/7/365 Automated Uptime',
                    'Accuracy' => 'Grounded ERP RAG Architecture',
                ],
                'highlights_en' => [
                    'RAG (Retrieval-Augmented Generation) connecting AI directly to live ERP inventory stock.',
                    'Understands natural Arabic and English conversational queries regarding product availability.',
                    'Automated checkout flow generating invoice links directly inside the chat window.',
                    'Seamless live human agent handoff when complex customer inquiries arise.',
                ],
                'highlights_ar' => [
                    'تقنية RAG لربط نموذج الذكاء الاصطناعي بقاعدة بيانات المخزون الحية بدقة تامة.',
                    'فهم اللهجات العربية المختلفة والإنجليزية والإجابة على استفسارات الأسعار والتوافر.',
                    'إتمام عمليات الشراء وتوليد روابط الفواتير والدفع الإلكتروني داخل المحادثة مباشرة.',
                    'تحويل ذكي للعميل إلى موظف بشري عند وجود استفسارات معقدة أو طلبات خاصة.',
                ],
            ],

            'forex-bot' => [
                'slug' => 'forex-bot',
                'title_en' => 'Autonomous Forex Algorithmic Trading Bot',
                'title_ar' => 'روبوت التداول الخوارزمي الآلي Forex Bot',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Automated execution bot connecting to MetaTrader APIs, implementing quantitative risk management models, trailing stop mechanics, and multi-currency hedge strategies.',
                'desc_ar' => 'روبوت التداول الخوارزمي الآلي للويندوز والـ VPS، متصل بمنصات MT4/MT5 لتنفيذ صفقات التداول وإدارة المخاطر آلياً.',
                'img' => '/images/portfolio/forex.png',
                'techs' => ['Python', 'MQL5', 'MetaTrader API', 'ZeroMQ', 'Windows Service'],
                'live_url' => null,
                'metrics' => [
                    'Execution' => '< 15ms Latency',
                    'Risk Engine' => 'Max Drawdown Enforcement',
                    'Uptime' => '24/7 VPS Optimized',
                    'Logging' => 'Encrypted Trade Journal',
                ],
                'highlights_en' => [
                    'Strict quantitative position sizing based on account equity and dynamic volatility.',
                    'Dynamic trailing stop loss and partial take-profit automation during high-impact news.',
                    'Multi-pair correlation matrix avoiding overexposure to correlated currency baskets.',
                    'Instant Telegram and Discord execution alerts with trade rationale logs.',
                ],
                'highlights_ar' => [
                    'تحديد دقيق لحجم اللوت والصفقات بناءً على حجم رأس المال والتقلبات.',
                    'وقف خسارة متحرك (Trailing Stop) وجني أرباح جزئي آلي عند تحقيق الأهداف.',
                    'مصفوفة قياس ارتباط أزواج العملات لمنع المخاطر التراكمية.',
                    'إشعارات فورية عبر تيليجرام وديسكورد بكل العمليات والصفقات المنفذة.',
                ],
            ],

            'duplicate-finder' => [
                'slug' => 'duplicate-finder',
                'title_en' => 'Duplicate Finder High-Speed Desktop Utility',
                'title_ar' => 'برنامج فحص واستخراج الملفات المكررة Duplicate Finder',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Native Windows desktop application utilizing multi-threaded SHA-256 block hashing to scan terabytes of storage and eliminate duplicate files safely in seconds.',
                'desc_ar' => 'برنامج فحص واستخراج الملفات المكررة للويندوز يعتمد على خوارزميات التجزئة السريعة لتنظيف الأقراص واستعادة المساحة.',
                'img' => '/images/portfolio/duplicate-finder.jpg',
                'techs' => ['C#', '.NET Core', 'WPF', 'Multi-Threading', 'Win32 API'],
                'live_url' => null,
                'metrics' => [
                    'Scan Rate' => '10,000+ Files / Second',
                    'Accuracy' => '100% Bitwise SHA Verification',
                    'Memory Footprint' => '< 45MB RAM Usage',
                    'Safety' => 'Recycle Bin Integration',
                ],
                'highlights_en' => [
                    'Multi-staged scanning pipeline: Size Filter -> Header Check -> Full Hash.',
                    'Parallel multi-core processing scanning massive NAS and local SSD drives effortlessly.',
                    'Visual duplicate inspection with inline image, video, and audio previews.',
                    'Safe cleanup options with direct Recycle Bin routing or symbolic link creation.',
                ],
                'highlights_ar' => [
                    'فحص متعدد المراحل (الحجم -> ترويسة الملف -> التشفير الكامل) لسرعة قصوى.',
                    'استغلال كامل لكافة أنوية المعالج للتعامل مع محركات الأقراص العملاقة.',
                    'معاينة بصرية فورية للصور ومقاطع الفيديو والملفات الصوتية المكررة.',
                    'خيارات حذف آمنة مع دعم سلة المهملات وإنشاء الروابط الرمزية.',
                ],
            ],

            'map-extractor' => [
                'slug' => 'map-extractor',
                'title_en' => 'Map Extractor B2B Lead Generation Desktop Studio',
                'title_ar' => 'برنامج استخراج بيانات العملاء والشركات Map Extractor',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'High-performance desktop crawler extracting verified business leads (names, phone numbers, websites, GPS coordinates) from Google Maps with zero proxy bans.',
                'desc_ar' => 'برنامج استخراج بيانات العملاء والشركات للديسك توب من خرائط جوجل مع تصنيف أرقام الهواتف وتصديرها لإكسيل وجهات الاتصال.',
                'img' => '/images/portfolio/map-extractor.jpg',
                'techs' => ['C#', '.NET Core', 'Chromium Embedded', 'Excel Interop', 'SQLite'],
                'live_url' => null,
                'metrics' => [
                    'Speed' => '2,500+ Leads / Hour',
                    'Data Points' => 'Phone, Website, Email, Reviews, Rating',
                    'Export' => 'XLSX, CSV, VCF (Phonebook)',
                    'Bypass' => 'Anti-Bot Fingerprint Spoofing',
                ],
                'highlights_en' => [
                    'Deep geographical grid scanning covering specified cities, districts, and categories.',
                    'Automated website scraper following business URLs to discover contact emails and socials.',
                    'Direct export to VCF phonebook files ready for instant mobile contact importing.',
                    'Built-in duplicate filtering ensuring unique phone numbers across campaign runs.',
                ],
                'highlights_ar' => [
                    'فحص جغرافي دقيق يغطي مدن ومناطق وتصنيفات تجارية محددة.',
                    'مستكشف آلي يزور مواقع الشركات المستخرجة لجلب البريد الإلكتروني وروابط التواصل.',
                    'تصدير مباشر لملفات جهات الاتصال (VCF) لحفظ الأرقام في الهاتف بضغطة واحدة.',
                    'فلترة تلقائية تمنع تكرار أي رقم هاتف أو شركة في ملفات الإكسيل الناتجة.',
                ],
            ],

            'instagram-manager' => [
                'slug' => 'instagram-manager',
                'title_en' => 'Instagram Manager Desktop Growth & Automation Suite',
                'title_ar' => 'برنامج جدولة ونشر حسابات انستجرام Instagram Manager',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Desktop tool designed for managing multiple Instagram accounts, auto-publishing scheduled reels and posts, and monitoring customer direct messages efficiently.',
                'desc_ar' => 'برنامج جدولة ونشر حسابات انستجرام للكمبيوتر، يدعم إدارة الحسابات المتعددة، جدولة الريلز والمنشورات، والرد على الرسائل.',
                'img' => '/images/portfolio/instagram-manager.png',
                'techs' => ['C#', '.NET Core', 'Chromium Driver', 'WPF', 'SQLite'],
                'live_url' => null,
                'metrics' => [
                    'Multi-Account' => 'Unlimited Instagram Profiles',
                    'Automation' => 'Scheduled Reels & Carousel Upload',
                    'Proxy Support' => 'Dedicated HTTP/SOCKS5 Proxies',
                    'Safety' => 'Humanized Interaction Delays',
                ],
                'highlights_en' => [
                    'Schedule high-resolution Reels, single posts, and carousels across multiple profiles.',
                    'Dedicated proxy assigning per profile to protect account trust scores.',
                    'Auto-resizing and media metadata cleaner optimizing posts for algorithm reach.',
                    'Direct inbox synchronization allowing fast replies from desktop.',
                ],
                'highlights_ar' => [
                    'جدولة مقاطع الريلز والمنشورات بجودة عالية على حسابات متعددة بالتزامن.',
                    'تخصيص بروكسي منفصل لكل حساب لحماية الحساب من أي تقييد.',
                    'تنظيف البيانات الوصفية وضبط أبعاد الصور والفيديوهات تلقائياً.',
                    'متابعة الرسائل المباشرة والرد السريع عليها من شاشة الكمبيوتر.',
                ],
            ],

            'heic-converter' => [
                'slug' => 'heic-converter',
                'title_en' => 'HEIC to JPG/PNG Batch Converter Utility',
                'title_ar' => 'أداة تحويل صور الآيفون HEIC Converter لسطح المكتب',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'Lightweight Windows utility allowing photographers and agencies to convert thousands of Apple HEIC photos into JPG/PNG formats instantly while preserving EXIF metadata.',
                'desc_ar' => 'أداة تحويل صور الآيفون HEIC لسطح المكتب، تحول آلاف الصور دفعة واحدة إلى JPG و PNG مع الحفاظ على دقة الألوان وبيانات الكاميرا.',
                'img' => '/images/portfolio/heic-converter.png',
                'techs' => ['C#', 'ImageMagick Native', 'WPF', '.NET Core'],
                'live_url' => null,
                'metrics' => [
                    'Speed' => '100+ Photos / Minute',
                    'Metadata' => 'Full EXIF & GPS Preservation',
                    'Interface' => 'Drag-and-Drop Batch Queue',
                    'Quality' => 'Lossless Compression Engine',
                ],
                'highlights_en' => [
                    'Drag-and-drop entire folders of HEIC images for simultaneous conversion.',
                    'Preserves original camera metadata including capture date, exposure, and GPS location.',
                    'Configurable JPEG quality slider balancing file size against crisp visual clarity.',
                    'Offline zero-cloud processing guaranteeing total user privacy.',
                ],
                'highlights_ar' => [
                    'سحب وإفلات مجلدات كاملة من الصور لتحويلها جميعاً بضغطة زر.',
                    'الحفاظ على البيانات الوصفية للكاميرا (تاريخ الالتقاط، إعدادات العدسة، والموقع الجغرافي).',
                    'التحكم في درجة ضغط وجودة الصورة للموازنة بين الحجم ودقة التفاصيل.',
                    'معالجة محلية 100% بدون إنترنت لضمان الخصوصية التامة لصور المستخدم.',
                ],
            ],
        ];
    }

    /**
     * Get a specific project by slug.
     */
    public static function find(string $slug): ?array
    {
        $all = self::all();
        return $all[$slug] ?? null;
    }

    /**
     * Get strictly organized categories.
     */
    public static function categories(): array
    {
        return [
            'All' => ['en' => 'All Systems', 'ar' => 'جميع الأنظمة'],
            'Web Apps' => ['en' => 'Web Apps', 'ar' => 'تطبيقات ومواقع الويب'],
            'Mobile Apps' => ['en' => 'Mobile Apps', 'ar' => 'تطبيقات الموبايل'],
            'Desktop Apps' => ['en' => 'Desktop Apps', 'ar' => 'برامج سطح المكتب والأتمتة'],
        ];
    }
}
