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
            'acelbay' => [
                'slug' => 'acelbay',
                'title_en' => 'Acel Bay Red Sea Resort Website',
                'title_ar' => 'موقع منتجع أسيل باي على البحر الأحمر',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'Website for Acel Bay, a new resort destination on the Red Sea in Egypt. It presents the hotels, the beach, the activities and the frequent questions.',
                'desc_ar' => 'موقع لأسيل باي، وجهة سياحية جديدة على البحر الأحمر في مصر. بيعرض الفنادق والشاطئ والأنشطة والأسئلة الشائعة.',
                'img' => '/images/portfolio/acelbay.png',
                'techs' => [],
                'live_url' => 'https://acelbay.com/',
                'metrics' => [
                    'Type' => 'Resort destination website',
                    'Content' => 'Hotels, beach and activities',
                    'Pages' => 'Destinations, hotels and FAQs',
                    'Mobile' => 'Works on any phone',
                ],
                'highlights_en' => [
                    'A full-screen beachfront welcome page that presents the destination.',
                    'A page for each hotel: Sentido Acel Beach Resort, and Acel Marina coming soon.',
                    'Activities such as diving, spa and dinners on the beach.',
                    'Frequent questions and a contact page.',
                ],
                'highlights_ar' => [
                    'صفحة ترحيب بصورة الشاطئ بتعرّف بالوجهة.',
                    'صفحة لكل فندق: سنتيدو أسيل بيتش، وأسيل مارينا قريبًا.',
                    'الأنشطة زي الغطس والسبا والعشا على البحر.',
                    'الأسئلة الشائعة وصفحة للتواصل.',
                ],
            ],

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
                    'Users' => 'Marketers and merchants',
                    'Shipping' => 'Courier integration',
                    'Commissions' => 'Calculated for each marketer',
                    'Store' => 'Products in many categories',
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
                'title_en' => 'Mini Fatora Affiliate Selling Platform',
                'title_ar' => 'ميني فاتورة: منصة أفلييت للمسوقين والتجار',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'An affiliate platform where merchants list their products and marketers sell them. Each marketer sets their own selling price and keeps the profit, while the platform handles storage and delivery.',
                'desc_ar' => 'منصة أفلييت بيسجل فيها التجار والمسوقين: التاجر بيعرض منتجاته، والمسوق بيبيعها بالسعر اللي يحدده ومكسبه بيبقى بتاعه، والمنصة مسؤولة عن التخزين والتوصيل.',
                'img' => '/images/portfolio/minifatora.png',
                'techs' => ['Laravel', 'MySQL', 'TailwindCSS'],
                'live_url' => null,
                'metrics' => [
                    'Users' => 'Merchants and marketers',
                    'Pricing' => 'Marketer sets the selling price',
                    'Profit' => 'Marketer keeps the difference',
                    'Fulfilment' => 'Storage and delivery by the platform',
                ],
                'highlights_en' => [
                    'Merchants and marketers each register and get their own account.',
                    'The marketer chooses the selling price of each product and the profit is theirs.',
                    'The platform stores the goods and delivers the orders, so the marketer only sells.',
                    'Each marketer gets their own online store to share with customers.',
                ],
                'highlights_ar' => [
                    'التاجر والمسوق كل واحد بيسجل وبيبقى ليه حساب خاص بيه.',
                    'المسوق بيحدد سعر البيع لكل منتج، والمكسب بيبقى بتاعه.',
                    'المنصة بتخزّن البضاعة وبتوصّل الطلبات، والمسوق عليه البيع بس.',
                    'كل مسوق بيبقى ليه متجر أونلاين يشاركه مع عملائه.',
                ],
            ],

            'trenz-whatscrm' => [
                'slug' => 'trenz-whatscrm',
                'title_en' => 'Trenz whatsCRM: Appointments and WhatsApp Reminders',
                'title_ar' => 'Trenz whatsCRM: حجز المواعيد وتذكير واتساب',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A subscription platform for businesses to book appointments and send automatic WhatsApp reminders. WhatsApp can be connected by scanning a QR code or through the official WhatsApp Business API.',
                'desc_ar' => 'منصة باشتراك للشركات لحجز المواعيد وإرسال تذكير تلقائي على واتساب. الواتساب بيتربط يا إما بمسح QR أو عن طريق WhatsApp Business API الرسمي.',
                'img' => '/images/portfolio/trenz-whatscrm.png',
                'techs' => ['Laravel', 'MySQL', 'WhatsApp Business API'],
                'live_url' => null,
                'metrics' => [
                    'WhatsApp' => 'QR code or official API',
                    'Reminders' => 'Sent automatically',
                    'Plans' => 'Subscription plans with limits',
                    'Templates' => 'Reusable message templates',
                ],
                'highlights_en' => [
                    'An appointments scheduler for each business.',
                    'WhatsApp connected by QR code or through the official WhatsApp Business API.',
                    'Automatic reminders, with a count of sent and pending reminders.',
                    'Message templates, and subscription plans that limit accounts and daily bookings.',
                ],
                'highlights_ar' => [
                    'جدول مواعيد لكل نشاط.',
                    'ربط الواتساب بمسح QR أو عن طريق WhatsApp Business API الرسمي.',
                    'تذكير تلقائي، مع عدد الرسايل اللي اتبعتت واللي مستنية.',
                    'قوالب رسايل، وباقات اشتراك بحدود للحسابات والحجوزات اليومية.',
                ],
            ],

            'zawdha' => [
                'slug' => 'zawdha',
                'title_en' => 'Zawdha: Cart-Value App for Salla Stores',
                'title_ar' => 'زودها: تطبيق زيادة قيمة السلة لمتاجر سلة',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'An app for Salla stores that shows offers to the shopper at the right moment: a countdown for each visitor, buy-together bundles, quantity discounts, a free-shipping bar and product upsell, with no code needed.',
                'desc_ar' => 'تطبيق لمتاجر سلة يعرض على العميل عروضاً في اللحظة المناسبة: عدّاد تنازلي لكل زائر، واشتري معاً، وخصم الكميات، وشريط الشحن المجاني، وترقية المنتج، من غير أي برمجة.',
                'img' => '/images/portfolio/zawdha.png',
                'techs' => ['Salla App Platform', 'OAuth', 'Arabic RTL'],
                'live_url' => 'https://zawdha.app/',
                'metrics' => [
                    'Platform' => 'Salla app store',
                    'Free trial' => '7 days',
                    'Billing' => 'Salla official payment',
                    'Setup' => 'No code needed',
                ],
                'highlights_en' => [
                    'Offers in one app, including: per-visitor countdown, buy together, quantity discounts, free-shipping bar and product upsell.',
                    'A live preview on the site lets a store owner try the offers before installing.',
                    'Installed from the Salla app store, with billing, renewal and cancellation handled by Salla.',
                    'The privacy page says the app does not sell data, does not store the customer personal data in the order, and deletes access tokens on uninstall.',
                ],
                'highlights_ar' => [
                    'عروض في تطبيق واحد، منها: عدّاد تنازلي لكل زائر، واشتري معاً، وخصم الكميات، وشريط الشحن المجاني، وترقية المنتج.',
                    'معاينة حية في الموقع تخلي صاحب المتجر يجرّب العروض قبل التثبيت.',
                    'بيتثبّت من متجر تطبيقات سلة، والفوترة والتجديد والإلغاء بتتم عن طريق سلة.',
                    'صفحة الخصوصية بتقول إن التطبيق لا يبيع البيانات ولا يحفظ بيانات العميل الشخصية في الطلب، وبيحذف رموز الوصول عند الإلغاء.',
                ],
            ],

            'amc-academy' => [
                'slug' => 'amc-academy',
                'title_en' => 'AMC Academy & AMCTasks.com — AI Marketing Automation & Ecosystem',
                'title_ar' => 'منظومة AMCTasks & AMC Academy للتسويق الرقمي والأتمتة الذكية',
                'category' => 'Marketing & AI Systems',
                'category_ar' => 'أنظمة التسويق والذكاء الاصطناعي',
                'desc_en' => 'Comprehensive marketing automation and software ecosystem founded by entrepreneur and digital marketing expert Ahmed Maher. Powers multi-channel social management across Facebook, Instagram, Twitter, Telegram, Viber, and VK, paired with 24/7 intelligent chatbots, reducing operating overhead and driving business growth.',
                'desc_ar' => 'منظومة تسويقية وبرمجية شاملة لشركة AMC Academy التي أسسها خبير التسويق الرقمي ورائد الأعمال "أحمد ماهر". تدير حسابات السوشيال ميديا المتعددة عبر فيسبوك، إنستجرام، تويتر، تليجرام، فايبر، VK، مع بوتات الرد الذكي الآلي (Chatbots) على مدار 24 ساعة، وتوفير تكاليف التشغيل.',
                'img' => '/images/portfolio/case_study_amc_square.png',
                'techs' => ['Laravel', 'React', 'Node.js', 'Python AI', 'Meta Graph API', 'Telegram Bot API', 'MySQL', 'Redis'],
                'live_url' => 'https://amctasks.com',
                'metrics' => [
                    'Channels' => 'Facebook, WhatsApp, Telegram, Instagram and more',
                    'Bots' => 'Chatbots for messages and comments',
                    'Tools' => 'Desktop tools and browser extensions',
                    'Plans' => 'Yearly plans',
                ],
                'highlights_en' => [
                    'AMCTasks.com unified dashboard managing multi-channel social accounts and task scheduling.',
                    '24/7 conversational chatbots automatically responding to customer messages and post comments.',
                    'AMC Turbo and AMC Social for fast account workflows and automated omnichannel broadcasting.',
                    'SEO suite featuring AMC Article Maker for automated content generation and AMC Backlinks engine.',
                ],
                'highlights_ar' => [
                    'منصة AMCTasks.com لإدارة ونشر ومتابعة حسابات السوشيال ميديا المتعددة من لوحة تحكم مركزية موحدة.',
                    'برمجة وتدريب بوتات ذكية 24/7 للرد الفوري على رسائل وكومنتات العملاء وخفض تكاليف التشغيل وخدمة العملاء.',
                    'نظامي AMC Turbo و AMC Social لإدارة الحسابات باحترافية وتفعيل الشات بوت على فيسبوك وتليجرام وواتساب.',
                    'حزمة أدوات الـ SEO والمحتوى: برنامج AMC Article Maker للمقالات، و AMC Backlinks، وتحليل محتوى فيسبوك.',
                ],
            ],

            'bulk-data-automation' => [
                'slug' => 'bulk-data-automation',
                'title_en' => 'Distributed Bulk Data Automation & Multi-Worker Engine',
                'title_ar' => 'منظومة أتمتة ومعالجة البيانات المجمعة الذكية',
                'category' => 'Distributed Systems & Big Data',
                'category_ar' => 'أنظمة موزعة وبيانات ضخمة',
                'desc_en' => 'A master and worker system: one dashboard splits large record lists into small tasks, and many desktop worker bots check and update the records on several web portals at the same time, with proxy rotation, locking so no record is handled twice, and Elasticsearch search.',
                'desc_ar' => 'نظام فيه لوحة رئيسية بتقسّم قوائم السجلات الكبيرة لمهام صغيرة، وبوتات ديسك توب كتير بتراجع السجلات وتحدّثها على أكتر من بوابة إلكترونية في نفس الوقت، مع تبديل البروكسي، وقفل يمنع إن سجل يتعمل مرتين، وبحث بـ Elasticsearch.',
                'img' => '/images/portfolio/case_study_bulk_data_square.png',
                'techs' => ['Laravel', 'Elasticsearch', 'C# (.NET)', 'CefSharp', 'MySQL', 'Telegram Bot API', 'Redis'],
                'live_url' => null,
                'metrics' => [
                    'Architecture' => 'One master, many desktop workers',
                    'Search' => 'Elasticsearch',
                    'Workers' => 'Desktop browser automation',
                    'Alerts' => 'Telegram progress messages',
                ],
                'highlights_en' => [
                    'Master orchestration dashboard receiving and partitioning massive CSV/Excel datasets into micro-tasks.',
                    'Search indexing with Elasticsearch for fast filtering across the records.',
                    'Multi-node desktop automation workers (C# .NET & CefSharp) executing human-like browser workflows.',
                    'Smart Proxy Rotation detecting platform rate limits and swapping IPs dynamically without downtime.',
                    'Concurrency management engine with distributed locking to prevent duplicate queries across workers.',
                    'Realtime Telegram Webhook integration dispatching instant progress alerts and batch summaries.',
                ],
                'highlights_ar' => [
                    'لوحة تحكم مركزية (العقل المدبر) تستقبل وتجزئ ملفات البيانات الضخمة CSV/Excel لمهام صغيرة مجدولة.',
                    'فهرسة بمحرك Elasticsearch لتصفية السجلات بسرعة.',
                    'شبكة روبوتات سطح مكتب متوازية (C# .NET و CefSharp) تحاكي السلوك البشري بدقة عالية.',
                    'نظام Smart Proxy Rotation لكشف محاولات الحظر وتغيير الـ IPs تلقائياً وضمان استمرار العمل.',
                    'إدارة التزامن (Concurrency Management) بنظام قفل متقدم لمنع أي تداخل أو تكرار للاستعلامات.',
                    'ربط مباشر مع Telegram لإرسال تنبيهات لحظية فور اكتمال معالجة الملفات الكبيرة أو إشعار الإدارة.',
                ],
            ],

            'whatsapp-oman-alerts' => [
                'slug'        => 'whatsapp-oman-alerts',
                'title_en'    => 'Intelligent WhatsApp Alert & Official Campaign System',
                'title_ar'    => 'نظام التنبيهات الذكي والحملات الإعلانية الرسمية عبر واتساب',
                'category'    => 'Communication Systems & Marketing Automation',
                'category_ar' => 'أنظمة التواصل والتسويق الآلي',
                'desc_en'     => 'Two integrated systems for an Oman-based company: an event-driven intelligent alert engine that fires real-time branded WhatsApp notifications from backend events, and an official WhatsApp Business API campaign platform for approved advertising with analytics.',
                'desc_ar'     => 'منظومتان برمجيتان متكاملتان لشركة ناشئة في عُمان: محرك تنبيهات ذكي يطلق رسائل واتساب آنية مرتبطة بأحداث الباك-إند، ومنصة حملات إعلانية رسمية عبر WhatsApp Business API المعتمد مع لوحة تحليلات شاملة.',
                'img'         => '/images/portfolio/case_study_whatsapp_oman_square.jpg',
                'techs'       => ['Laravel', 'WhatsApp Business API', 'Meta Cloud API', 'Redis Queues', 'MySQL', 'Webhook Listeners'],
                'live_url'    => null,
                'metrics' => [
                    'Alerts' => 'Sent when something happens in the system',
                    'Campaigns' => 'Official WhatsApp Business API',
                    'Templates' => 'Personal variables per recipient',
                    'Retries' => 'Failed messages retried',
                ],
                'highlights_en' => [
                    'Event-driven alert engine: Laravel listeners fire WhatsApp messages instantly on backend state changes.',
                    'Campaigns sent through the official WhatsApp Business API.',
                    'Dynamic branded message templates with personalized variables per recipient.',
                    'Queue-backed retry engine with exponential backoff and full delivery logging.',
                    'Campaign management platform: audience targeting, scheduling, template approval, and real-time analytics.',
                ],
                'highlights_ar' => [
                    'محرك تنبيهات مدفوع بالأحداث: مستمعو Laravel يطلقون رسائل واتساب فورياً عند تغيير حالة الباك-إند.',
                    'تكامل رسمي مع WhatsApp Business API — صفر خطر حظر الأرقام وأعلى معدلات التسليم.',
                    'قوالب رسائل ديناميكية بمتغيرات شخصية لكل مستلم.',
                    'محرك قوائم انتظار مع إعادة المحاولة بنظام Exponential Backoff وتسجيل كامل للتسليم.',
                    'منصة إدارة الحملات: استهداف الجماهير، الجدولة، اعتماد القوالب، وتقارير لحظية.',
                ],
            ],

            'vodafone-crm' => [
                'slug' => 'vodafone-crm',
                'title_en' => 'My Line Call Centre for Vodafone Landline',
                'title_ar' => 'نظام كول سنتر My Line لعملاء فودافون الأرضي',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A call centre system for a Vodafone landline agent. Agents record each customer (landline, owner, mobile numbers, ID and address) and their service requests, request files and statuses are uploaded in bulk, and managers follow the performance of each agent.',
                'desc_ar' => 'نظام كول سنتر لوكيل فودافون الأرضي. الموظفين بيسجلوا كل عميل (الخط الأرضي وصاحب الخط وأرقام الموبايل والرقم القومي والعنوان) وطلبات الخدمة بتاعته، وملفات الطلبات وحالاتها بتترفع مرة واحدة، والمدير بيتابع أداء كل موظف.',
                'img' => '/images/portfolio/telecom-system.png',
                'techs' => ['Laravel', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Records' => 'Customer and landline data',
                    'Requests' => 'Service requests and pending list',
                    'Agents' => 'Performance per agent',
                    'Uploads' => 'Requests and statuses in bulk',
                ],
                'highlights_en' => [
                    'A customer record for each landline, with the name of the agent who entered it.',
                    'Creating service requests, a list of the pending ones, and uploading requests and statuses from files.',
                    'An agent performance page for the manager.',
                    'Search by date, status, landline, mobile number or request number, in Arabic and English.',
                ],
                'highlights_ar' => [
                    'ملف لكل خط أرضي، باسم الموظف اللي دخّله.',
                    'عمل طلبات الخدمة، وقائمة باللي لسه مخلصش، ورفع الطلبات وحالاتها من ملفات.',
                    'صفحة لأداء الموظفين للمدير.',
                    'بحث بالتاريخ أو الحالة أو الخط الأرضي أو الموبايل أو رقم الطلب، بالعربي والإنجليزي.',
                ],
            ],

            'altayaraa' => [
                'slug' => 'altayaraa',
                'title_en' => 'Altayaraa Delivery Service and Driver Management',
                'title_ar' => 'الطيارة: نظام خدمة توصيل الطلبات وإدارة المندوبين',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A delivery service platform for a city: customers open an account and send delivery requests, anyone can track an order live, and drivers work from their own panel, around the clock.',
                'desc_ar' => 'منصة لخدمة توصيل الطلبات داخل المدينة: العميل بيعمل حساب ويبعت طلب التوصيل، وأي حد يقدر يتابع الطلب لحظة بلحظة، والمندوبين بيشتغلوا من لوحة خاصة بيهم، على مدار الساعة.',
                'img' => '/images/portfolio/altayaraa.png',
                'techs' => ['Laravel', 'MySQL', 'TailwindCSS'],
                'live_url' => null,
                'metrics' => [
                    'Users' => 'Customers, drivers and admin',
                    'Tracking' => 'Live order tracking',
                    'Service' => '24/7',
                    'Mobile' => 'Works on any phone',
                ],
                'highlights_en' => [
                    'Customer accounts: send a delivery request and see all your orders in one place.',
                    'Order tracking page that works with the order number, no login needed.',
                    'A separate panel for drivers to receive orders and update their status.',
                    'An admin panel to assign orders to drivers and follow every delivery.',
                ],
                'highlights_ar' => [
                    'حساب لكل عميل: يبعت طلب توصيل ويشوف كل طلباته في مكان واحد.',
                    'صفحة تتبع الطلب برقم الطلب من غير تسجيل دخول.',
                    'لوحة خاصة للمندوبين يستلموا منها الطلبات ويحدّثوا حالتها.',
                    'لوحة إدارة لتوزيع الطلبات على المندوبين ومتابعة كل توصيلة.',
                ],
            ],

            'project-manager' => [
                'slug' => 'project-manager',
                'title_en' => 'Project Manager: Projects, Clients and Finance',
                'title_ar' => 'Project Manager: إدارة المشاريع والعملاء والحسابات',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'An Arabic system to run projects and client services: projects and their stages, meetings, invoices and payments, expenses and net profit, and complaints, with a dashboard of revenue and project status.',
                'desc_ar' => 'نظام عربي لإدارة المشاريع وخدمة العملاء: المشاريع ومراحلها، والاجتماعات، والفواتير والمدفوعات، والمصاريف وصافي الربح، والشكاوى، مع لوحة للإيرادات وحالة المشاريع.',
                'img' => '/images/portfolio/projectmanager.png',
                'techs' => ['Laravel', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Projects' => 'Stages and status',
                    'Finance' => 'Invoices, payments, expenses and profit',
                    'Clients' => 'Meetings and complaints',
                    'Reports' => 'Monthly revenue chart',
                ],
                'highlights_en' => [
                    'Projects with stages, and a count of active and completed projects.',
                    'Invoices, payments, money owed by clients and overdue invoices.',
                    'Expenses and net profit, with a monthly revenue chart.',
                    'Meetings and client complaints in the same place.',
                ],
                'highlights_ar' => [
                    'مشاريع بمراحلها، وعدد المشاريع الشغالة واللي خلصت.',
                    'الفواتير والمدفوعات والفلوس اللي على العملاء والفواتير المتأخرة.',
                    'المصاريف وصافي الربح، مع رسم للإيرادات كل شهر.',
                    'الاجتماعات وشكاوى العملاء في نفس المكان.',
                ],
            ],

            // ==========================================
            // 2. Mobile Apps (تطبيقات الموبايل)
            // ==========================================
            'nokhpa' => [
                'slug' => 'nokhpa',
                'title_en' => 'Nokhpa Doctor Booking App',
                'title_ar' => 'تطبيق نخبة لحجز الدكاترة',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'A mobile app to find doctors by specialty and book appointments, with each doctor\'s specialty, clinic and fee, and the patient\'s own appointments and profile.',
                'desc_ar' => 'تطبيق موبايل للبحث عن الدكاترة حسب التخصص وحجز المواعيد، فيه تخصص كل دكتور وعيادته وسعر الكشف، ومواعيد المريض وملفه.',
                'img' => '/images/portfolio/nokhpa.png',
                'techs' => ['Flutter'],
                'live_url' => null,
                'metrics' => [
                    'Search' => 'Doctors by specialty',
                    'Booking' => 'Appointments in the app',
                    'Doctors' => 'Specialty, clinic and fee',
                    'Language' => 'Arabic',
                ],
                'highlights_en' => [
                    'Search for doctors and specialists by name or specialty.',
                    'Each doctor shows the specialty, the clinic and the fee.',
                    'Booking an appointment and seeing all your appointments.',
                    'A profile for each patient, and browsing as a visitor before signing up.',
                ],
                'highlights_ar' => [
                    'بحث عن الدكاترة والمتخصصين بالاسم أو التخصص.',
                    'كل دكتور ظاهر تخصصه وعيادته وسعر الكشف.',
                    'حجز ميعاد ومتابعة كل مواعيدك.',
                    'ملف لكل مريض، وتقدر تتصفح كزائر قبل ما تسجل.',
                ],
            ],

            'qcoin-wallet' => [
                'slug' => 'qcoin-wallet',
                'title_en' => 'QCoin: Top-up Card Wallet and Store',
                'title_ar' => 'QCoin: محفظة ومتجر كروت الشحن',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'An Arabic wallet app and online store for buying mobile top-up cards (Asiacell, Zain and Korek in Iraq): a balance, send and withdraw, QR scan and points of sale.',
                'desc_ar' => 'تطبيق محفظة ومتجر أونلاين لشراء كروت شحن الموبايل (آسياسيل وزين وكورك في العراق): رصيد، وإرسال وسحب، ومسح QR، ونقاط بيع.',
                'img' => '/images/portfolio/qcoin-app.png',
                'techs' => [],
                'live_url' => null,
                'metrics' => [
                    'Cards' => 'Asiacell, Zain and Korek',
                    'Wallet' => 'Balance, send and withdraw',
                    'Scan' => 'QR code',
                    'Sales' => 'Points of sale',
                ],
                'highlights_en' => [
                    'A wallet with the current balance, sending and withdrawing.',
                    'A store to buy top-up cards for Asiacell, Zain and Korek.',
                    'Scanning a QR code from the app.',
                    'Points of sale, the transactions list and support inside the app.',
                ],
                'highlights_ar' => [
                    'محفظة فيها الرصيد الحالي والإرسال والسحب.',
                    'متجر لشراء كروت شحن آسياسيل وزين وكورك.',
                    'مسح QR من التطبيق.',
                    'نقاط البيع وقائمة العمليات والدعم الفني جوه التطبيق.',
                ],
            ],

            'amc-social' => [
                'slug' => 'amc-social',
                'title_en' => 'AMC Social: All Your Bots in One Place',
                'title_ar' => 'AMC Social: كل البوتات في مكان واحد',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A website that gathers bots for every platform in one place: Facebook, Viber, Telegram and others. Each user signs in and runs their bots from one account, with pricing plans and video lessons from AMC Academy.',
                'desc_ar' => 'موقع بيجمع البوتات لكل المنصات في مكان واحد: فيسبوك وفايبر وتليجرام وغيرهم. كل مستخدم بيسجل دخول ويشغّل البوتات بتاعته من حساب واحد، مع باقات أسعار وفيديوهات تعليمية من AMC Academy.',
                'img' => '/images/portfolio/amcsocial.png',
                'techs' => [],
                'live_url' => null,
                'metrics' => [
                    'Platforms' => 'Facebook, Viber, Telegram and more',
                    'Account' => 'One sign-in for all bots',
                    'Plans' => 'Pricing plans',
                    'Languages' => 'Arabic and English',
                ],
                'highlights_en' => [
                    'Bots for Facebook, Viber, Telegram and other platforms in one website.',
                    'One account to run all of your bots.',
                    'Services and pricing pages.',
                    'Arabic and English, with video lessons from AMC Academy.',
                ],
                'highlights_ar' => [
                    'بوتات فيسبوك وفايبر وتليجرام وغيرها في موقع واحد.',
                    'حساب واحد تشغّل منه كل البوتات بتاعتك.',
                    'صفحات الخدمات والأسعار.',
                    'عربي وإنجليزي، مع فيديوهات تعليمية من AMC Academy.',
                ],
            ],

            // ==========================================
            // 3. Desktop Apps (برامج سطح المكتب والأتمتة)
            // ==========================================
            'stock-manager' => [
                'slug' => 'stock-manager',
                'title_en' => 'StockManager: Stock, Sales and Shifts',
                'title_ar' => 'StockManager: المخزن والمبيعات والورديات',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A web system for shops: products and warehouse, orders and customers, cashier shifts, low-stock alerts, stock movements and sales reports, in Arabic and English.',
                'desc_ar' => 'نظام ويب للمحلات: المنتجات والمخزن، والطلبات والعملاء، وورديات الكاشير، وتنبيه نقص المخزون، وحركة المخزون، وتقارير المبيعات، بالعربي والإنجليزي.',
                'img' => '/images/portfolio/stockmanager.png',
                'techs' => ['Laravel', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Stock' => 'Warehouse and low-stock alerts',
                    'Sales' => 'Today, month and average order',
                    'Shifts' => 'Cashier shifts',
                    'Language' => 'Arabic and English',
                ],
                'highlights_en' => [
                    'Products and warehouse, with an alert when a product runs low.',
                    'Orders and customers, with today\'s and this month\'s sales.',
                    'Cashier shifts and the stock movements of each day.',
                    'Reports, in Arabic and English.',
                ],
                'highlights_ar' => [
                    'المنتجات والمخزن، وتنبيه لما منتج يقرب يخلص.',
                    'الطلبات والعملاء، ومبيعات النهارده والشهر.',
                    'ورديات الكاشير وحركة المخزون كل يوم.',
                    'تقارير، بالعربي والإنجليزي.',
                ],
            ],

            'whatsapp-sender' => [
                'slug' => 'whatsapp-sender',
                'title_en' => 'AMC WhatsApp Sender',
                'title_ar' => 'برنامج AMC WhatsApp Sender',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'A Windows program that sends WhatsApp campaigns with random delays between messages and longer pauses, keeps a log of each message, and can add numbers to groups.',
                'desc_ar' => 'برنامج ويندوز بيبعت حملات واتساب بفواصل عشوائية بين الرسايل ووقفات أطول، وبيسجل حالة كل رسالة، وبيقدر يضيف أرقام للجروبات.',
                'img' => '/images/portfolio/whatsapp-sender.png',
                'techs' => ['C#', 'WinForms'],
                'live_url' => null,
                'metrics' => [
                    'Delays' => 'Random, with long pauses',
                    'Log' => 'Status of each message',
                    'Groups' => 'Group adder',
                    'Lists' => 'Random order',
                ],
                'highlights_en' => [
                    'A random delay between messages, and a longer pause every few messages.',
                    'A log with date, campaign, number, status and details of every message.',
                    'Adding numbers to WhatsApp groups.',
                    'Shuffling the list before sending.',
                ],
                'highlights_ar' => [
                    'فاصل عشوائي بين الرسايل، ووقفة أطول كل كام رسالة.',
                    'سجل فيه التاريخ والحملة والرقم والحالة وتفاصيل كل رسالة.',
                    'إضافة أرقام لجروبات الواتساب.',
                    'ترتيب القائمة عشوائي قبل الإرسال.',
                ],
            ],

            'telegram-sender' => [
                'slug' => 'telegram-sender',
                'title_en' => 'TeleBomber: Telegram Sender',
                'title_ar' => 'TeleBomber: برنامج إرسال تيليجرام',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'A Windows program that sends Telegram messages to contact lists loaded from a CSV file, through several connected accounts, with a random delay between messages.',
                'desc_ar' => 'برنامج ويندوز بيبعت رسايل تيليجرام لقوائم أرقام من ملف CSV، من أكتر من حساب متربط، بفاصل عشوائي بين الرسايل.',
                'img' => '/images/portfolio/telegram-sender.png',
                'techs' => ['C#', 'WinForms'],
                'live_url' => null,
                'metrics' => [
                    'Accounts' => 'Several connected accounts',
                    'Contacts' => 'From a CSV file',
                    'Delays' => 'Random between messages',
                    'Results' => 'Success and failure counts',
                ],
                'highlights_en' => [
                    'Connecting several Telegram accounts and sending from all of them.',
                    'Loading contacts from a CSV file (phone, id, first and last name).',
                    'A random delay between messages, and removing numbers already sent to.',
                    'Counts of successful and failed messages, with a log.',
                ],
                'highlights_ar' => [
                    'ربط أكتر من حساب تيليجرام والإرسال منهم كلهم.',
                    'تحميل الأرقام من ملف CSV (الرقم والـ id والاسم).',
                    'فاصل عشوائي بين الرسايل، وشيل الأرقام اللي اتبعتلها.',
                    'عدد الرسايل اللي نجحت واللي فشلت، مع سجل.',
                ],
            ],

            'inbox-sender' => [
                'slug' => 'inbox-sender',
                'title_en' => 'AMC Inbox Sender for Facebook Pages',
                'title_ar' => 'AMC Inbox Sender لصفحات فيسبوك',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'A Windows program that loads the message inbox of a Facebook page and sends a text or photo message to selected conversations or to all of them, with delays between messages.',
                'desc_ar' => 'برنامج ويندوز بيحمّل رسايل صفحة الفيسبوك، وبيبعت رسالة نص أو صورة للمحادثات اللي تختارها أو ليهم كلهم، بفواصل بين الرسايل.',
                'img' => '/images/portfolio/inbox-sender.png',
                'techs' => ['C#', 'WinForms'],
                'live_url' => null,
                'metrics' => [
                    'Platform' => 'Facebook pages',
                    'Message' => 'Text, photo or album',
                    'Send' => 'Selected or all',
                    'Delays' => 'Between messages',
                ],
                'highlights_en' => [
                    'Loading the page inbox, or conversations by their IDs.',
                    'A message with text, a photo or an album, with emoji.',
                    'Sending to selected conversations or to all of them.',
                    'Delays for loading pages and uploading photos.',
                ],
                'highlights_ar' => [
                    'تحميل رسايل الصفحة، أو محادثات بالـ ID بتاعها.',
                    'رسالة فيها نص أو صورة أو ألبوم، وإيموجي.',
                    'الإرسال للمحادثات اللي تختارها أو للكل.',
                    'فواصل لتحميل الصفحات ورفع الصور.',
                ],
            ],

            'chartcash' => [
                'slug' => 'chartcash',
                'title_en' => 'ChartCash: Forex Trading Simulator',
                'title_ar' => 'ChartCash: محاكي تداول الفوركس',
                'category' => 'Mobile Apps',
                'category_ar' => 'تطبيقات الموبايل',
                'desc_en' => 'An app to learn trading on a simulated market, without MetaTrader 4 or 5. The trader gets a practice balance, a live price chart, manual trades and trading bots, and follows profit and loss.',
                'desc_ar' => 'تطبيق لتعلّم التداول على سوق محاكي، من غير ميتاتريدر 4 أو 5. المتداول بياخد رصيد للتدريب، ورسم أسعار مباشر، وصفقات يدوية وبوتات تداول، ويتابع المكسب والخسارة.',
                'img' => '/images/portfolio/chartcash.png',
                'techs' => [],
                'live_url' => null,
                'metrics' => [
                    'Market' => 'Simulated, for learning',
                    'Trading' => 'Manual and by bots',
                    'Chart' => 'Live price chart',
                    'MetaTrader' => 'Not needed',
                ],
                'highlights_en' => [
                    'A practice balance with profit and loss shown all the time.',
                    'A live price chart with the share of buyers and sellers.',
                    'Manual trading, and trading bots the learner can run.',
                    'A blog inside the app to learn trading.',
                ],
                'highlights_ar' => [
                    'رصيد للتدريب والمكسب والخسارة ظاهرين على طول.',
                    'رسم أسعار مباشر ونسبة البايعين والشاريين.',
                    'تداول يدوي، وبوتات تداول يقدر المتعلم يشغّلها.',
                    'مدونة جوه التطبيق لتعلّم التداول.',
                ],
            ],

            'stocktalk-ai' => [
                'slug' => 'stocktalk-ai',
                'title_en' => 'Stocktalk: Car Spare Parts Catalogue',
                'title_ar' => 'Stocktalk: كتالوج قطع غيار العربيات',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'An Arabic web system for a car spare parts catalogue: brands, car models and years, each part linked to the cars it fits, search words, Excel import, and alerts for parts not linked yet.',
                'desc_ar' => 'نظام ويب عربي لكتالوج قطع غيار العربيات: الماركات والموديلات والسنين، وكل قطعة مربوطة بالعربيات اللي بتركب عليها، وكلمات بحث، واستيراد من إكسل، وتنبيه للقطع اللي لسه متربطتش.',
                'img' => '/images/portfolio/stocktalk.png',
                'techs' => ['Laravel', 'MySQL'],
                'live_url' => null,
                'metrics' => [
                    'Catalogue' => 'Brands, models and years',
                    'Parts' => 'Linked to the cars they fit',
                    'Import' => 'From Excel',
                    'Search' => 'Synonyms for each part',
                ],
                'highlights_en' => [
                    'Car brands, models and years in one catalogue.',
                    'Each spare part linked to the cars it fits.',
                    'Importing parts from Excel.',
                    'Search words for each part, and alerts for parts not linked to a car yet.',
                ],
                'highlights_ar' => [
                    'ماركات العربيات وموديلاتها وسنينها في كتالوج واحد.',
                    'كل قطعة غيار مربوطة بالعربيات اللي بتركب عليها.',
                    'استيراد القطع من إكسل.',
                    'كلمات بحث لكل قطعة، وتنبيه للقطع اللي لسه متربطتش بعربية.',
                ],
            ],

            'duplicate-finder' => [
                'slug' => 'duplicate-finder',
                'title_en' => 'Duplicate File Finder',
                'title_ar' => 'برنامج البحث عن الملفات المكررة',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'A Windows program that scans a folder for duplicate files, can skip small files, and removes the duplicates.',
                'desc_ar' => 'برنامج ويندوز بيدور في فولدر على الملفات المكررة، ويقدر يتجاهل الملفات الصغيرة، وبيمسح المكرر.',
                'img' => '/images/portfolio/duplicate-finder.jpg',
                'techs' => ['C#', 'WinForms'],
                'live_url' => null,
                'metrics' => [
                    'Scan' => 'Any folder',
                    'Filter' => 'Skip files under 1 MB',
                    'List' => 'Original next to duplicate',
                    'Clean' => 'Remove duplicates',
                ],
                'highlights_en' => [
                    'Choose a folder and find the duplicate files in it.',
                    'An option to skip files smaller than 1 MB.',
                    'A list of each original file next to its duplicate.',
                    'Removing the duplicates in one click.',
                ],
                'highlights_ar' => [
                    'تختار فولدر ويطلع الملفات المكررة فيه.',
                    'اختيار لتجاهل الملفات الأصغر من 1 ميجا.',
                    'قائمة فيها كل ملف أصلي جنب المكرر بتاعه.',
                    'مسح المكرر بضغطة.',
                ],
            ],

            'map-extractor' => [
                'slug' => 'map-extractor',
                'title_en' => 'Map Extractor: Google Maps Business Data',
                'title_ar' => 'Map Extractor: بيانات الشركات من خرائط جوجل',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'A Windows program that collects business data from Google Maps: name, phone, address, category, rating, area and map link.',
                'desc_ar' => 'برنامج ويندوز بيجمع بيانات الشركات والمحلات من خرائط جوجل: الاسم والتليفون والعنوان والنشاط والتقييم والمنطقة ولينك الخريطة.',
                'img' => '/images/portfolio/map-extractor.jpg',
                'techs' => ['C#', 'WinForms'],
                'live_url' => null,
                'metrics' => [
                    'Data' => 'Name, phone, address, category',
                    'Extra' => 'Rating, area, plus code, link',
                    'Search' => 'On the map',
                    'Delays' => 'Between places',
                ],
                'highlights_en' => [
                    'Searching on the map for the places you want.',
                    'Collecting name, mobile, address, category, rating, area, plus code and link.',
                    'Delays between places so the search runs steadily.',
                    'Start and stop at any time.',
                ],
                'highlights_ar' => [
                    'البحث على الخريطة عن الأماكن اللي عايزها.',
                    'جمع الاسم والموبايل والعنوان والنشاط والتقييم والمنطقة والـ plus code واللينك.',
                    'فواصل بين الأماكن عشان البحث يمشي بثبات.',
                    'تبدأ وتوقف في أي وقت.',
                ],
            ],

            'instagram-manager' => [
                'slug' => 'instagram-manager',
                'title_en' => 'AMC Instagram Manager',
                'title_ar' => 'برنامج AMC Instagram Manager',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'A Windows program for Instagram: find accounts by niche, collect posts, comments, likes and followers, and automate follow and unfollow, likes, comments, mentions and direct messages.',
                'desc_ar' => 'برنامج ويندوز لإنستجرام: بيدور على الحسابات حسب المجال، ويجمع البوستات والتعليقات واللايكات والمتابعين، ويعمل متابعة وإلغاء متابعة ولايكات وتعليقات ومنشن ورسايل بشكل تلقائي.',
                'img' => '/images/portfolio/instagram-manager.png',
                'techs' => ['C#', 'WinForms'],
                'live_url' => null,
                'metrics' => [
                    'Search' => 'Accounts by niche',
                    'Extract' => 'Posts, comments, likes, followers',
                    'Actions' => 'Follow, like, comment, mention',
                    'Messages' => 'Direct messages',
                ],
                'highlights_en' => [
                    'Finding accounts in a niche and collecting their posts.',
                    'Extracting comments, likes, followers and followings.',
                    'Follow and unfollow, like and unlike, comments and mentions.',
                    'Sending direct messages, and managing the account.',
                ],
                'highlights_ar' => [
                    'البحث عن حسابات في مجال معين وجمع بوستاتها.',
                    'استخراج التعليقات واللايكات والمتابعين والمتابَعين.',
                    'متابعة وإلغاء متابعة، ولايك وإلغاء لايك، وتعليقات ومنشن.',
                    'إرسال رسايل مباشرة، وإدارة الحساب.',
                ],
            ],

            'heic-converter' => [
                'slug' => 'heic-converter',
                'title_en' => 'HEIC Converter',
                'title_ar' => 'محوّل صور HEIC',
                'category' => 'Desktop Apps',
                'category_ar' => 'برامج سطح المكتب والأتمتة',
                'desc_en' => 'A Windows program that converts iPhone HEIC and HEIF photos, and AVIF photos, to JPG in batches.',
                'desc_ar' => 'برنامج ويندوز بيحوّل صور الآيفون HEIC وHEIF، وصور AVIF، لـ JPG مرة واحدة.',
                'img' => '/images/portfolio/heic-converter.png',
                'techs' => ['C#', 'WinForms'],
                'live_url' => null,
                'metrics' => [
                    'Input' => 'HEIC, HEIF and AVIF',
                    'Output' => 'JPG',
                    'Batch' => 'Many files at once',
                    'Progress' => 'Progress bar',
                ],
                'highlights_en' => [
                    'Opening many photos at once.',
                    'Choosing HEIF or AVIF as the input format.',
                    'Saving all the photos as JPG.',
                    'A progress bar while converting.',
                ],
                'highlights_ar' => [
                    'فتح صور كتير مرة واحدة.',
                    'اختيار HEIF أو AVIF كنوع الصور.',
                    'حفظ كل الصور JPG.',
                    'شريط بيوضح التقدم أثناء التحويل.',
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
