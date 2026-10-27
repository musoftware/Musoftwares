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
                'title_en' => 'Kbdny: Affiliate Selling Platform',
                'title_ar' => 'قبضني: منصة أفلييت للمسوقين والتجار',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'An Arabic affiliate platform for Egypt. Merchants list their products, marketers sell them to their own customers with a commission they set, and the platform handles stock, shipping companies, cash on delivery, returns and payouts. Every order is split automatically between the merchant, the marketer and the platform. Other businesses can run their own copy under their own name.',
                'desc_ar' => 'منصة أفلييت عربي لمصر. التاجر بيعرض منتجاته، والمسوق بيبيعها لعملائه بعمولة هو اللي يحددها، والمنصة بتتولى المخزن وشركات الشحن والدفع عند الاستلام والمرتجعات وصرف الأرباح. كل طلب بيتقسم لوحده بين التاجر والمسوق والمنصة. وأي شركة تقدر تشغّل نسخة باسمها.',
                'img' => '/images/portfolio/kbdny-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/kbdny-1.webp', '/images/portfolio/kbdny-2.webp', '/images/portfolio/kbdny-3.webp', '/images/portfolio/kbdny-4.webp', '/images/portfolio/kbdny-5.webp'],
                'techs' => ['Laravel', 'Vue.js', 'MySQL', 'Laravel Excel', 'mPDF'],
                'live_url' => 'https://www.kbdny.com',
                'metrics' => [
                    'Users' => 'Merchants, marketers and admin',
                    'Commission' => 'Set by the marketer',
                    'Split' => 'Automatic for every order',
                    'Delivery' => 'Shipping companies and cash on delivery',
                ],
                'highlights_en' => [
                    'The marketer adds their own commission on top of the product price, and the customer pays the total plus delivery.',
                    'Each order creates the merchant share, the marketer commission and the platform commission, approved on delivery.',
                    'Merchants add products with variants and stock, and the admin approves them before they show.',
                    'Order statuses from new to delivered, returns, shipping company sheets in Excel, PDF invoices and payout requests.',
                ],
                'highlights_ar' => [
                    'المسوق بيضيف عمولته فوق سعر المنتج، والعميل بيدفع الإجمالي ومعاه الشحن.',
                    'كل طلب بيعمل نصيب التاجر وعمولة المسوق وعمولة المنصة، وبتتأكد لما الطلب يتسلّم.',
                    'التاجر بيضيف منتجاته بالمقاسات والمخزون، والأدمن بيوافق عليها قبل ما تظهر.',
                    'حالات الطلب من جديد لحد التسليم، والمرتجعات، وشيتات شركات الشحن إكسل، وفواتير PDF، وطلبات سحب الأرباح.',
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

            'amc-turbo' => [
                'slug' => 'amc-turbo',
                'title_en' => 'AMC Turbo: Bots and Leads for Marketers',
                'title_ar' => 'AMC Turbo: بوتات وعملاء للمسوقين',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'The earlier marketing platform for AMC Academy\'s students and marketers: a visual chatbot builder for Facebook, Instagram, WhatsApp, Telegram, Viber and more, rules that act on post comments, scheduled posts, and lead lists with WhatsApp, Telegram and call actions. It also licenses AMC\'s desktop software.',
                'desc_ar' => 'منصة التسويق الأقدم لطلاب ومسوقين AMC Academy: محرر بوتات بالسحب والإفلات لفيسبوك وإنستجرام وواتساب وتليجرام وفايبر وغيرهم، وقواعد بتشتغل على تعليقات البوستات، وبوستات مجدولة، وقوائم عملاء بأزرار واتساب وتليجرام ومكالمة. وكمان بتدير تراخيص برامج AMC للكمبيوتر.',
                'img' => '/images/portfolio/amc-turbo-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/amc-turbo-1.webp', '/images/portfolio/amc-turbo-2.webp', '/images/portfolio/amc-turbo-3.webp'],
                'techs' => ['Laravel', 'Livewire', 'Vue.js', 'Elasticsearch', 'Pusher'],
                'live_url' => null,
                'metrics' => [
                    'Bots' => 'Visual flow builder',
                    'Platforms' => 'Facebook, Instagram, WhatsApp, Telegram and more',
                    'Leads' => 'Lists with quick contact actions',
                    'Licences' => 'For AMC desktop software',
                ],
                'highlights_en' => [
                    'A visual bot builder: a keyword starts a flow of messages and quick replies.',
                    'Rules that act on comments on Facebook and Instagram posts, and scheduled posts.',
                    'Lead lists with course, source and status, and one-click WhatsApp, Telegram or call.',
                    'Subscriptions, coupons and licences for the AMC desktop programs.',
                ],
                'highlights_ar' => [
                    'محرر بوتات بالسحب والإفلات: كلمة بتبدأ سلسلة رسايل وردود سريعة.',
                    'قواعد بتشتغل على تعليقات بوستات فيسبوك وإنستجرام، وبوستات مجدولة.',
                    'قوائم عملاء بالكورس والمصدر والحالة، وواتساب أو تليجرام أو مكالمة بضغطة.',
                    'الاشتراكات والكوبونات وتراخيص برامج AMC للكمبيوتر.',
                ],
            ],

            'content-unlocker' => [
                'slug' => 'content-unlocker',
                'title_en' => 'Content Unlocker: Leads Through WhatsApp',
                'title_ar' => 'Content Unlocker: عملاء جداد عن طريق واتساب',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A lead-generation platform. A business hides a file, a coupon or a recording behind a small widget on its website, and the visitor unlocks it by verifying their WhatsApp number. It also has a visual WhatsApp bot builder, broadcast campaigns to the collected leads, and a landing page builder that can start from an AI prompt.',
                'desc_ar' => 'منصة لجمع العملاء. الشركة بتخبي ملف أو كوبون أو تسجيل ورا ويدجت صغيرة في موقعها، والزائر بيفتحه لما يأكد رقم الواتساب بتاعه. وفيها كمان محرر بوتات واتساب بالسحب والإفلات، وحملات للأرقام اللي اتجمعت، ومحرر صفحات هبوط يقدر يبدأ من وصف بالذكاء الاصطناعي.',
                'img' => '/images/portfolio/content-unlocker-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/content-unlocker-1.webp', '/images/portfolio/content-unlocker-2.webp', '/images/portfolio/content-unlocker-3.webp', '/images/portfolio/content-unlocker-4.webp'],
                'techs' => ['Laravel', 'React', 'Alpine.js', 'Tailwind CSS', 'GrapesJS', 'Gemini'],
                'live_url' => null,
                'metrics' => [
                    'Leads' => 'Verified WhatsApp numbers',
                    'Widget' => 'Embedded in any website',
                    'Bots' => 'Visual WhatsApp flows',
                    'Pages' => 'Landing page builder with AI',
                ],
                'highlights_en' => [
                    'Content lockers with an embed code, a pause switch and a list of the leads each one collected.',
                    'A visual WhatsApp bot builder with messages, buttons, lists, conditions and hand-off to a person.',
                    'Broadcast campaigns to the collected leads, with a personal message and sent and failed counts.',
                    'A drag-and-drop landing page builder with ready templates, and pages or bots generated from a text prompt.',
                ],
                'highlights_ar' => [
                    'ويدجت لكل محتوى بكود تضمين وزرار إيقاف وقائمة بالعملاء اللي جمعها.',
                    'محرر بوتات واتساب بالسحب والإفلات: رسايل وأزرار وقوائم وشروط وتحويل لموظف.',
                    'حملات للأرقام اللي اتجمعت برسالة باسم كل واحد وعدد اللي اتبعت واللي فشل.',
                    'محرر صفحات هبوط بالسحب والإفلات بقوالب جاهزة، وصفحات أو بوتات بتتعمل من وصف مكتوب.',
                ],
            ],

            'food-lab' => [
                'slug' => 'food-lab',
                'title_en' => 'Food Testing Lab: CRM and Client Portal',
                'title_ar' => 'معمل تحاليل الأغذية: نظام إدارة وبوابة عملاء',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A system for a company that tests food samples through accredited partner labs. Staff move each job from quotation to order, sample and test; analysts enter results without seeing the client, a second person approves them, and the client receives a certificate of analysis with a QR check. Clients follow their orders, certificates, invoices and result trends in their own portal.',
                'desc_ar' => 'نظام لشركة بتحلل عينات الأكل في معامل شريكة معتمدة. الموظفين بيمشّوا كل شغلانة من عرض السعر للطلب للعينة للتحليل، والمحلل بيدخل النتايج من غير ما يعرف العميل، وشخص تاني بيعتمدها، والعميل بياخد شهادة تحليل بكود QR للتحقق. والعملاء بيتابعوا طلباتهم وشهاداتهم وفواتيرهم وتطور نتايجهم من بوابة خاصة بيهم.',
                'img' => '/images/portfolio/food-lab-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/food-lab-1.webp', '/images/portfolio/food-lab-2.webp', '/images/portfolio/food-lab-3.webp', '/images/portfolio/food-lab-4.webp'],
                'techs' => ['Laravel', 'React', 'Inertia.js', 'Tailwind CSS', 'Mantine', 'DomPDF'],
                'live_url' => null,
                'metrics' => [
                    'Workflow' => 'Quotation, order, sample and test',
                    'Results' => 'Blind entry and a second approval',
                    'Certificates' => 'PDF with QR verification',
                    'Clients' => 'Own portal with result trends',
                ],
                'highlights_en' => [
                    'Orders split into tests routed to partner labs, with due dates and overdue alerts on the dashboard.',
                    'Results checked against the specification limits; nobody can approve their own results.',
                    'Certificates as PDF with a QR code and a public check page; corrections are new revisions, never edits.',
                    'A client portal with orders, certificates, quotations to accept, invoices and a trend chart per test.',
                ],
                'highlights_ar' => [
                    'الطلبات بتتقسم لتحاليل بتروح للمعامل الشريكة، بمواعيد تسليم وتنبيه للمتأخر على اللوحة.',
                    'النتايج بتتقارن بالحدود المسموحة، ومحدش يقدر يعتمد نتايجه بنفسه.',
                    'الشهادات PDF بكود QR وصفحة تحقق عامة، والتصحيح بيطلع نسخة جديدة مش تعديل.',
                    'بوابة للعميل فيها الطلبات والشهادات وعروض الأسعار للموافقة والفواتير ورسم لكل تحليل.',
                ],
            ],

            'airtech' => [
                'slug' => 'airtech',
                'title_en' => 'AirTech: Stock and Sales for AC Dealers',
                'title_ar' => 'إيرتك: المخزن والمبيعات لتجار التكييفات',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'An Arabic system for air-conditioner dealers in Egypt. Each dealer keeps their own stock, suppliers, purchase orders, clients and sales invoices, while the admin runs one catalogue of brands and models with a central price list and activates dealer accounts.',
                'desc_ar' => 'نظام عربي لتجار التكييفات في مصر. كل تاجر بيمسك مخزنه وموردينه وطلبات الشراء وعملاءه وفواتير البيع بتاعته، والأدمن بيدير كتالوج واحد للماركات والموديلات بقائمة أسعار موحدة وبيفعّل حسابات التجار.',
                'img' => '/images/portfolio/airtech-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/airtech-1.webp', '/images/portfolio/airtech-2.webp', '/images/portfolio/airtech-3.webp', '/images/portfolio/airtech-4.webp'],
                'techs' => ['Laravel', 'Vue.js', 'Bootstrap', 'Laravel Excel'],
                'live_url' => null,
                'metrics' => [
                    'Users' => 'Dealers and admin',
                    'Stock' => 'Units and value by brand',
                    'Orders' => 'Purchases and sales invoices',
                    'Prices' => 'One central price list',
                ],
                'highlights_en' => [
                    'Stock per dealer, with total units and value by brand and category, and a switch to hide balances.',
                    'Purchase orders from suppliers with several items, discounts and refunds.',
                    'Sales invoices that only offer models in stock, with WhatsApp and call links for each client.',
                    'An admin catalogue with an editable price list, drag-and-drop ordering and Excel import and export.',
                ],
                'highlights_ar' => [
                    'مخزن لكل تاجر، بعدد الوحدات وقيمتها حسب الماركة والنوع، وزرار يخفي الأرصدة.',
                    'طلبات شراء من الموردين بأكتر من صنف وخصومات ومرتجعات.',
                    'فواتير بيع بتعرض بس الموديلات اللي في المخزن، ولينك واتساب ومكالمة لكل عميل.',
                    'كتالوج للأدمن بقائمة أسعار بتتعدل مكانها وترتيب بالسحب واستيراد وتصدير إكسل.',
                ],
            ],

            'app-licensing' => [
                'slug' => 'app-licensing',
                'title_en' => 'Licensing and Subscriptions for a Desktop App',
                'title_ar' => 'نظام تراخيص واشتراكات لبرنامج ديسك توب',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'The backend of a paid Windows program: customer accounts, plans and subscriptions, licence keys tied to each device with a device limit per plan, usage logs, announcements and app versions. Customers manage their devices from a dashboard, the admin manages everything from a panel, and the program checks its licence through an API.',
                'desc_ar' => 'السيستم اللي ورا برنامج ويندوز مدفوع: حسابات العملاء، والباقات والاشتراكات، ومفاتيح ترخيص مربوطة بكل جهاز بحد أقصى للأجهزة في كل باقة، وسجل الاستخدام، والإعلانات وإصدارات البرنامج. العميل بيدير أجهزته من لوحة، والأدمن بيدير كل حاجة من لوحة تحكم، والبرنامج بيتأكد من الترخيص عن طريق API.',
                'img' => '/images/portfolio/app-licensing-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/app-licensing-1.webp', '/images/portfolio/app-licensing-2.webp', '/images/portfolio/app-licensing-3.webp'],
                'techs' => ['Laravel', 'Filament', 'Laravel Sanctum', 'Python'],
                'live_url' => null,
                'metrics' => [
                    'Licences' => 'Tied to each device',
                    'Plans' => 'Free, Pro and Business',
                    'Devices' => 'Limit per plan',
                    'API' => 'Checked by the program',
                ],
                'highlights_en' => [
                    'Licence keys tied to a device ID, with revoke from the admin panel.',
                    'Plans and subscriptions with cancel and extend by 30 days.',
                    'A customer dashboard to see and remove registered devices within the plan limit.',
                    'An API for the program: sign in, licence check and heartbeat, devices, usage logs and version check.',
                ],
                'highlights_ar' => [
                    'مفاتيح ترخيص مربوطة بـ ID الجهاز، وإلغاؤها من لوحة الأدمن.',
                    'باقات واشتراكات بإلغاء وتمديد 30 يوم.',
                    'لوحة للعميل يشوف فيها أجهزته ويشيل اللي مش محتاجه في حدود الباقة.',
                    'API للبرنامج: تسجيل دخول، وتحقق من الترخيص، والأجهزة، وسجل الاستخدام، والتحقق من الإصدار.',
                ],
            ],

            'vodafone-cash-confirmation' => [
                'slug' => 'vodafone-cash-confirmation',
                'title_en' => 'Automatic Vodafone Cash Payment Confirmation',
                'title_ar' => 'تأكيد دفع فودافون كاش تلقائيًا',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'For an online store in Egypt: a phone forwards each Vodafone Cash or InstaPay SMS to the store, the system reads the reference number and amount in Arabic or English, matches them to the order the customer paid for, checks that the amount covers it, and marks the order paid by itself.',
                'desc_ar' => 'لمتجر أونلاين في مصر: الموبايل بيبعت كل رسالة فودافون كاش أو إنستاباي للمتجر، والسيستم بيقرا رقم العملية والمبلغ بالعربي أو الإنجليزي، ويطابقهم بالطلب اللي العميل دفعه، ويتأكد إن المبلغ مكفي، ويعلّم الطلب مدفوع لوحده.',
                'img' => '/images/portfolio/vodafone-cash-confirmation-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/vodafone-cash-confirmation-1.webp'],
                'techs' => ['PHP', 'Laravel', 'MacroDroid'],
                'live_url' => null,
                'metrics' => [
                    'Payments' => 'Vodafone Cash and InstaPay',
                    'Input' => 'SMS forwarded from a phone',
                    'Languages' => 'Arabic and English messages',
                    'Result' => 'Order marked paid automatically',
                ],
                'highlights_en' => [
                    'MacroDroid on a phone forwards every payment SMS to the store.',
                    'The reference number, amount and method are read from Arabic and English messages.',
                    'A scheduled job matches the payment to the order by the number the customer entered at checkout.',
                    'When the amount covers the order, it is marked paid and the usual notifications go out.',
                ],
                'highlights_ar' => [
                    'MacroDroid على الموبايل بيبعت كل رسالة دفع للمتجر.',
                    'رقم العملية والمبلغ وطريقة الدفع بيتقروا من الرسايل العربي والإنجليزي.',
                    'مهمة مجدولة بتطابق الدفع بالطلب بالرقم اللي العميل كتبه وهو بيطلب.',
                    'لما المبلغ يغطي الطلب، بيتعلّم مدفوع وبتطلع الإشعارات المعتادة.',
                ],
            ],

            'amc-academy' => [
                'slug' => 'amc-academy',
                'title_en' => 'AmcTasks: Marketing Platform for AMC Academy',
                'title_ar' => 'AmcTasks: منصة التسويق لـ AMC Academy',
                'category' => 'Web Apps',
                'category_ar' => 'تطبيقات ومواقع الويب',
                'desc_en' => 'A marketing platform built for Ahmed Maher\'s AMC Academy. Marketers and small businesses use one dashboard for official WhatsApp messaging, chatbots on seven platforms with an AI mode, automatic replies to Facebook comments, AI-written scheduled posts, and lead lists with SMS and call campaigns.',
                'desc_ar' => 'منصة تسويق معمولة لـ AMC Academy بتاعة أحمد ماهر. المسوقين والشركات الصغيرة بيستخدموا لوحة واحدة للواتساب الرسمي، وبوتات على 7 منصات بوضع ذكاء اصطناعي، وردود تلقائية على تعليقات فيسبوك، وبوستات مجدولة بيكتبها الذكاء الاصطناعي، وقوائم عملاء بحملات SMS ومكالمات.',
                'img' => '/images/portfolio/amc-academy-1.webp',
                // screenshots taken from a local run with demo data
                'gallery' => ['/images/portfolio/amc-academy-1.webp', '/images/portfolio/amc-academy-2.webp', '/images/portfolio/amc-academy-3.webp', '/images/portfolio/amc-academy-4.webp'],
                'techs' => ['Laravel', 'Livewire', 'Vue.js', 'WhatsApp Cloud API', 'Facebook Graph API', 'Elasticsearch', 'Pusher'],
                'live_url' => 'https://amctasks.com',
                'metrics' => [
                    'WhatsApp' => 'Official Cloud API inbox and campaigns',
                    'Bots' => 'Seven platforms with an AI mode',
                    'Comments' => 'Automatic replies and hiding',
                    'Leads' => 'Lists with SMS and call campaigns',
                ],
                'highlights_en' => [
                    'A shared WhatsApp inbox on the official Cloud API, with templates, scheduled campaigns and leads from click-to-WhatsApp ads.',
                    'Chatbots for Messenger, Instagram, Telegram, Viber, VK, WhatsApp and Twitter, with replies, a workflow builder and human agents.',
                    'An AI mode for each bot (a local model, Gemini or OpenAI) with its own knowledge base.',
                    'Rules that reply to, message or hide Facebook comments, and posts written and scheduled by AI.',
                ],
                'highlights_ar' => [
                    'صندوق رسايل واتساب مشترك على الـ Cloud API الرسمي، بقوالب وحملات مجدولة وعملاء من إعلانات واتساب.',
                    'بوتات لماسنجر وإنستجرام وتليجرام وفايبر وVK وواتساب وتويتر، بردود ومحرر خطوات وموظفين يستلموا المحادثة.',
                    'وضع ذكاء اصطناعي لكل بوت (موديل محلي أو Gemini أو OpenAI) وليه قاعدة معرفة خاصة بيه.',
                    'قواعد بترد على تعليقات فيسبوك أو تبعت رسالة خاصة أو تخفي التعليق، وبوستات بيكتبها ويجدولها الذكاء الاصطناعي.',
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
