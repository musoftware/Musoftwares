<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Ready-made Laravel systems (the first six here, three more in SystemsCatalogExtra) for small businesses, sold as source code as it is, or with the programming changes the buyer needs.
 * The facts come from the systems themselves (README, routes, seeders, tests). Prices are in EGP, one payment.
 */
class SystemsCatalog
{
    private const SOURCE_PRICE = 5000;

    private const CUSTOM_PRICE = 20000;

    /** @return array<int, array<string, mixed>> */
    public static function programs(): array
    {
        return array_map(fn (array $system, int $index) => self::build($system, $index + 6), self::systems(), array_keys(self::systems()));
    }

    private static function build(array $system, int $order): array
    {
        $landing = [];
        foreach (['ar', 'en'] as $lang) {
            $landing[$lang] = self::copy($system, $lang);
        }

        $program = [
            'slug' => $system['slug'],
            'sort_order' => $order,
            'image_path' => 'images/software/'.$system['slug'].'.svg',
            'name_en' => $system['name_en'],
            'name_ar' => $system['name_ar'],
            'tagline_en' => $system['tagline_en'],
            'tagline_ar' => $system['tagline_ar'],
            'description_en' => $system['tagline_en'],
            'description_ar' => $system['tagline_ar'],
            'currency' => 'EGP',
            'plans' => self::plans($system),
            'theme' => [
                'colors' => $system['colors'],
                'sections' => ['problems', 'showcase', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery', 'plans'],
            ],
            'landing' => $landing,
        ];

        return isset($system['screens']) ? self::withScreens($program, $system['screens']) : $program;
    }

    /**
     * Systems photographed from a local run with demo data ship their screenshots:
     * images/software/<slug>/1.webp ... (Arabic screens) and en-1.webp ... (English screens).
     * The seeder only fills them on a new product, so pictures uploaded in the admin are never replaced.
     */
    private static function withScreens(array $program, int $count): array
    {
        $shot = fn (string $prefix) => array_map(fn (int $n) => "images/software/{$program['slug']}/{$prefix}{$n}.webp", range(1, $count));
        $program['gallery'] = $shot('');
        $program['theme']['gallery_en'] = $shot('en-');

        return $program;
    }

    /** The stack and the demo data line come from the system: Laravel with demo data unless it says otherwise. */
    private static function plans(array $system): array
    {
        $stack = $system['stack'] ?? 'Laravel';
        $demo = $system['demo_data'] ?? true;
        $guide = $demo ? 'install guide and demo data' : 'install guide';
        $guideAr = $demo ? 'دليل التركيب وبيانات تجريبية' : 'دليل التركيب';

        return [
            ['key' => 'source', 'name' => 'Source code', 'name_ar' => 'الكود المصدري', 'price' => self::SOURCE_PRICE,
                'description' => "Full {$stack} source code, {$guide}, as it is. No modifications. You host it yourself.",
                'description_ar' => "كود {$stack} كامل مع {$guideAr}، زي ما هو من غير أي تعديلات. أنت اللي بتستضيفه."],
            ['key' => 'custom', 'name' => 'Source code with changes', 'name_ar' => 'الكود مع التعديلات', 'price' => self::CUSTOM_PRICE,
                'description' => 'The full source code plus any programming change your business needs. Any change request is available. We agree the list with you on WhatsApp.',
                'description_ar' => 'الكود المصدري كامل مع أي تعديل برمجي يحتاجه شغلك. أي طلب برمجي للتعديل متاح. بنتفق معاك على القائمة على الواتساب.'],
        ];
    }

    private static function copy(array $system, string $lang): array
    {
        $t = $system[$lang];
        $common = self::common($lang);
        $common['included'] = $t['included'] ?? $common['included'];
        $common['need_body'] = $t['need_body'] ?? $common['need_body'];
        $common['faq'][1] = $t['install_faq'] ?? $common['faq'][1];
        $common['faq'][2] = $t['language_faq'] ?? $common['faq'][2];
        $common['faq'][3] = $t['payments_faq'] ?? $common['faq'][3];

        return [
            'kicker' => $t['kicker'],
            'headline' => $t['headline'],
            'subheadline' => $t['subheadline'],
            'overview' => ['title' => $common['overview_title'], 'items' => [
                ['title' => $common['who'], 'body' => $t['who']],
                ['title' => $common['need'], 'body' => $common['need_body']],
                ['title' => $common['after'], 'body' => $common['after_body']],
            ]],
            'problems' => ['kicker' => $common['problems_kicker'], 'title' => $t['problems_title'], 'items' => $t['problems']],
            'showcase' => ['kicker' => $common['showcase_kicker'], 'title' => $t['showcase_title'], 'items' => $t['showcase']],
            'pillars' => ['kicker' => $common['pillars_kicker'], 'title' => $t['pillars_title'], 'items' => $t['pillars']],
            'video' => ['kicker' => $common['video_kicker'], 'title' => $common['video_title']],
            'steps' => ['kicker' => $common['steps_kicker'], 'title' => $common['steps_title'], 'items' => $common['steps']],
            'before_after' => $t['before_after'],
            'metrics' => ['kicker' => $common['metrics_kicker'], 'title' => $common['metrics_title'], 'items' => $t['metrics']],
            'gallery' => ['title' => $common['gallery_title']],
            'plans' => [
                'kicker' => $common['plans_kicker'], 'title' => $common['plans_title'], 'period' => $common['period'],
                'included' => $common['included'], 'note' => $common['plans_note'],
            ],
            'details' => ['title' => $common['details_title'], 'paragraphs' => $t['paragraphs'], 'list_title' => $common['list_title'], 'list' => $t['list']],
            'faq' => ['title' => $common['faq_title'], 'items' => array_merge($t['faq'], $common['faq'])],
            'cta' => ['kicker' => $common['cta_kicker'], 'title' => $t['cta_title'], 'body' => $common['cta_body'], 'button' => $common['cta_button']],
        ];
    }

    private static function common(string $lang): array
    {
        return [
            'ar' => [
                'overview_title' => 'اعرف كل حاجة في دقيقة', 'who' => 'مناسب لمين؟', 'need' => 'محتاج إيه؟', 'after' => 'بعد الدفع إيه اللي بيحصل؟',
                'need_body' => 'استضافة بتدعم PHP 8.3 وقاعدة بيانات SQLite أو MySQL. التركيب على استضافة عادية (cPanel) من غير Redis ولا خدمات إضافية.',
                'after_body' => 'بعد الدفع بتبعتلنا رقم طلبك على الواتساب (الزرار بيظهر بعد الدفع)، ونسلمك الكود. لو اخترت الكود مع التعديلات بنتفق معاك على التعديلات المطلوبة ونبدأ فيها.',
                'problems_kicker' => 'مشاكل الشغل اليومي', 'showcase_kicker' => 'شاشات النظام', 'pillars_kicker' => 'قدرات النظام', 'video_kicker' => 'شوف بنفسك',
                'video_title' => 'جولة سريعة جوه النظام', 'steps_kicker' => 'خطوات الشراء', 'steps_title' => 'من الدفع للتشغيل في 4 خطوات',
                'steps' => [
                    ['title' => 'اختار الباقة', 'body' => 'الكود زي ما هو، أو الكود مع تعديلات على مقاس شغلك.'],
                    ['title' => 'ادفع أونلاين', 'body' => 'من غير تسجيل حساب على موقعنا.'],
                    ['title' => 'ابعتلنا رقم طلبك', 'body' => 'على الواتساب بعد الدفع.'],
                    ['title' => 'استلم وشغّل', 'body' => 'بتستلم النظام وتعمل حساب المالك وتبدأ.'],
                ],
                'metrics_kicker' => 'ملخص', 'metrics_title' => 'النظام في أرقام', 'gallery_title' => 'صور من النظام',
                'plans_kicker' => 'طريقة الشراء', 'plans_title' => 'اختار الكود زي ما هو أو مع تعديلات', 'period' => 'دفعة واحدة',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'دفع نقدي داخل النظام'],
                'plans_note' => 'السعر دفعة واحدة. الكود بتستضيفه انت. أي طلب برمجي للتعديل متاح في باقة التعديلات.',
                'details_title' => 'تفاصيل النظام', 'list_title' => 'اللي هتحصل عليه', 'faq_title' => 'أسئلة شائعة',
                'faq' => [
                    ['q' => 'إيه الفرق بين الباقتين؟', 'a' => 'باقة 5000 هي الكود المصدري كامل زي ما هو من غير أي تعديلات. باقة 20000 هي نفس الكود مع أي تعديل برمجي يحتاجه نظامك، وأي طلب برمجي للتعديل متاح.'],
                    ['q' => 'التركيب صعب؟', 'a' => 'لا. خطوات التركيب مكتوبة في دليل مرفق: تركيب الحزم، وبناء الواجهة، وملف الإعدادات، وتشغيل قاعدة البيانات، وأمر لإنشاء حساب المالك.'],
                    ['q' => 'النظام بيدعم اللغتين؟', 'a' => 'أيوه، العربي هو الأساسي والإنجليزي متاح، وبيشتغل بالاتجاه من اليمين لليسار.'],
                    ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا، الدفع جوه النظام نقدي فقط.'],
                    ['q' => 'لو احتجت مساعدة؟', 'a' => 'كلمنا على الواتساب برقم طلبك.'],
                ],
                'cta_kicker' => 'ابدأ النهارده', 'cta_body' => 'اختار الباقة وادفع. بعد الدفع ابعتلنا رقم طلبك على الواتساب ونسلمك.', 'cta_button' => 'كمّل للدفع',
            ],
            'en' => [
                'overview_title' => 'Understand everything in one minute', 'who' => 'Who is it for?', 'need' => 'What do you need?', 'after' => 'What happens after payment?',
                'need_body' => 'Hosting with PHP 8.3 and an SQLite or MySQL database. It installs on normal shared hosting (cPanel) with no Redis or extra services.',
                'after_body' => 'After payment you send us your order number on WhatsApp (the button appears after payment), and we deliver the code. If you chose the code with changes, we agree the changes with you and start.',
                'problems_kicker' => 'Everyday problems', 'showcase_kicker' => 'System screens', 'pillars_kicker' => 'What the system can do', 'video_kicker' => 'See it yourself',
                'video_title' => 'A quick tour inside the system', 'steps_kicker' => 'How to buy', 'steps_title' => 'From payment to running in 4 steps',
                'steps' => [
                    ['title' => 'Choose the plan', 'body' => 'The code as it is, or the code with changes made for your business.'],
                    ['title' => 'Pay online', 'body' => 'No account needed on our site.'],
                    ['title' => 'Send us your order number', 'body' => 'On WhatsApp after payment.'],
                    ['title' => 'Receive and run', 'body' => 'You get the system, create the owner account and start.'],
                ],
                'metrics_kicker' => 'Summary', 'metrics_title' => 'The system in numbers', 'gallery_title' => 'Pictures of the system',
                'plans_kicker' => 'How to buy', 'plans_title' => 'Choose the code as it is, or with changes', 'period' => 'one payment',
                'included' => ['Arabic and English with right-to-left support', 'Cash payments inside the system'],
                'plans_note' => 'One payment. You host the code yourself. Any programming change request is available in the plan with changes.',
                'details_title' => 'System details', 'list_title' => 'What you get', 'faq_title' => 'Common questions',
                'faq' => [
                    ['q' => 'What is the difference between the two plans?', 'a' => 'The 5,000 plan is the full source code as it is, with no modifications. The 20,000 plan is the same code plus any programming change your system needs. Any change request is available.'],
                    ['q' => 'Is it hard to install?', 'a' => 'No. The steps are in an included guide: install packages, build the front end, set the settings file, run the database and run one command to create the owner account.'],
                    ['q' => 'Does it support both languages?', 'a' => 'Yes. Arabic is the main language and English is available, with right-to-left layout.'],
                    ['q' => 'Does it take online payments inside the system?', 'a' => 'No. Payments inside the system are cash only.'],
                    ['q' => 'What if I need help?', 'a' => 'Message us on WhatsApp with your order number.'],
                ],
                'cta_kicker' => 'Start today', 'cta_body' => 'Choose the plan and pay. After payment send us your order number on WhatsApp and we deliver.', 'cta_button' => 'Continue to payment',
            ],
        ][$lang];
    }

    private static function systems(): array
    {
        return [self::autoCare(), self::clinic(), self::coffee(), self::gym(), self::mobileShop(), self::salon(), ...SystemsCatalogExtra::systems(),
            ...SystemsCatalogEducation::systems(), ...SystemsCatalogLab::systems(), SystemsCatalogRealEstate::system(),
            ...SystemsCatalogWholesale::systems(), SystemsCatalogSupermarket::system(), SystemsCatalogNursery::system(),
            SystemsCatalogMenu::system(), ...SystemsCatalogSaas::systems(),
        ];
    }

    private static function autoCare(): array
    {
        return [
            'slug' => 'workshop-system', 'colors' => ['accent' => '#ea580c', 'ink' => '#1c0f08'],
            'name_en' => 'Car Workshop System', 'name_ar' => 'نظام إدارة ورشة السيارات',
            'tagline_en' => 'Website, online booking and workshop management for a car workshop or car wash.',
            'tagline_ar' => 'موقع وحجز أونلاين وإدارة كاملة لورشة سيارات أو مغسلة.',
            'ar' => [
                'kicker' => 'ورش السيارات والمغاسل', 'headline' => 'ورشتك على شاشة واحدة، من الحجز لحد تسليم العربية.',
                'subheadline' => 'العميل يحجز أونلاين في 3 خطوات ويتابع عربيته برقم اللوحة، ويوافق على التقدير ويدفع أونلاين. وانت تشوف لوحة الورشة لحظة بلحظة وتطلع أوامر الشغل والإيصالات.',
                'who' => 'أصحاب الورش ومراكز الصيانة والمغاسل اللي عايزين يرتبوا الحجوزات والشغل اليومي.',
                'problems_title' => 'ليه الورشة بتتلخبط؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'حجوزات بالتليفون', 'body' => 'مواعيد بتتداخل ومفيش مكان فاضي معروف.'],
                    ['icon' => 'file', 'title' => 'أوراق بدل أوامر شغل', 'body' => 'تقديرات وإيصالات بتتضيع أو بتتكتب بإيد.'],
                    ['icon' => 'users', 'title' => 'عملاء بتنسى صيانتهم', 'body' => 'مفيش تذكير بميعاد الصيانة الجاية.'],
                ],
                'showcase_title' => 'لوحة الورشة والحجز', 'showcase' => [
                    ['title' => 'حجز في 3 خطوات', 'body' => 'خدمة، ثم يوم وموعد، ثم البيانات. السعة بتتحسب بعدد الأماكن الفاضية.'],
                    ['title' => 'لوحة الورشة الحية', 'body' => 'بتتحدث كل 7 ثواني وبتوريك كل عربية في أنهي مرحلة.'],
                    ['title' => 'موافقة ودفع من صفحة التتبع', 'body' => 'العميل يوافق على التقدير ويدفع عربون، ولما العربية تجهز يدفع الباقي أونلاين.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم الورشة',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'حجز أونلاين', 'body' => 'صفحة حجز وتتبع بالعربية برقم اللوحة والتليفون.'],
                    ['icon' => 'wrench', 'title' => 'أوامر الشغل', 'body' => 'تقديرات وإيصالات وفنيين ولوحة حية.'],
                    ['icon' => 'box', 'title' => 'قطع الغيار', 'body' => 'مخزون القطع وتنبيه النقص.'],
                    ['icon' => 'message', 'title' => 'رسايل واتساب', 'body' => 'طلب الموافقة على التقدير، وإن العربية جاهزة، وتذكير الصيانة.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الورشة',
                    'before' => [['b' => 'الحجز', 't' => 'مكالمات وتداخل مواعيد.'], ['b' => 'الشغل', 't' => 'أوراق وتقديرات ضايعة.'], ['b' => 'العملاء', 't' => 'صيانة منسية.']],
                    'after' => [['b' => 'الحجز', 't' => 'أونلاين على حسب الأماكن الفاضية.'], ['b' => 'الشغل', 't' => 'أوامر شغل وإيصالات مطبوعة.'], ['b' => 'العملاء', 't' => 'تذكير صيانة على الواتساب.']]],
                'metrics' => [['value' => '3', 'label' => 'خطوات لحجز العميل'], ['value' => '7s', 'label' => 'تحديث لوحة الورشة'], ['value' => '157', 'label' => 'اختبار آلي في الكود، واختبارات متصفح']],
                'paragraphs' => ['النظام موقع للورشة مع نظام إدارة كامل. العميل بيحجز خدمة ويختار اليوم والموعد، والنظام بيحسب المتاح من عدد أماكن الورشة (الأماكن) المتاحة.', 'جوه النظام: لوحة حية للورشة، وأوامر شغل بتقديرات وإيصالات، والعملاء والسيارات، وتذكيرات الصيانة، والمخزون، والفنيين، وأكواد الخصم. فيه صلاحيتين: المالك وموظف الاستقبال.', 'لو ربطت حساباتك: العميل بيوافق على التقدير من صفحة التتبع (أو يرد بـ 1 على رسالة الواتساب) ويدفع عربون، ولما العربية تجهز بيوصله رسالة فيها الباقي ورابط يدفعه منه، وتذكير الصيانة بيتبعت لوحده لما ميعاده يقرب. ولما العربية تتسلم بيطلع إيصال إلكتروني واحد لمصلحة الضرائب للشغل كله (العربون جواه)، وكود QR بيتطبع على إيصال التسليم. الورشة مفيهاش مرتجعات، فمفيش إيصال مرتجع.'],
                'list' => ['صفحة رئيسية وصفحة حجز من 3 خطوات', 'تتبع السيارة برقم اللوحة والتليفون', 'لوحة ورشة حية', 'أوامر شغل وتقديرات وإيصالات 80 مم وA4', 'موافقة على التقدير ودفع العربون والباقي أونلاين', 'واتساب: طلب الموافقة والعربية جاهزة وتذكير الصيانة', 'إيصال إلكتروني (ETA) بكود QR على إيصال التسليم', 'العملاء والسيارات وتذكيرات الصيانة', 'قطع الغيار والمخزون', 'الفنيين وأكواد الخصم ولوحة المؤشرات'],
                'faq' => [['q' => 'كام نوع مستخدم في النظام؟', 'a' => 'اتنين: المالك وموظف الاستقبال.'], ['q' => 'بيطبع إيصالات؟', 'a' => 'أيوه، بطابعة حرارية 80 مم أو على ورق A4.'], ['q' => 'العميل بيوافق على التقدير إزاي؟', 'a' => 'بزرار في صفحة التتبع الخاصة بيه، أو بالرد بـ 1 على رسالة الواتساب، والموافقة بتتقبل بس لو التقدير متغيرش من ساعة ما شافه. مفيش توقيع إلكتروني. رسايل الواتساب بتتبعت لوحدها لو ربطت حساب واتساب Business، ومن غيره كل زرار بيفتح رسالة جاهزة على واتساب. الربط متجرب على ردود تجريبية بس، مش على خوادم Meta.'], SystemsCatalogOnline::etaFaq('ar')],
                'included' => SystemsCatalogOnline::included('ar', ['payments', 'whatsapp', 'eta']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'للإيصالات الإلكترونية وتذكيرات الواتساب ومتابعة الدفع'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('ar', 'عربون التقدير أو الباقي قبل ما يستلم العربية'),
                'cta_title' => 'جاهز تنظم ورشتك؟',
            ],
            'en' => [
                'kicker' => 'Car workshops and car washes', 'headline' => 'Your workshop on one screen, from booking to handing the car back.',
                'subheadline' => 'Customers book online in 3 steps, follow their car by plate number, approve the estimate and pay online. You see the live workshop board and print job cards and receipts.',
                'who' => 'Workshop and service centre owners and car washes who want to organize bookings and daily work.',
                'problems_title' => 'Why does the workshop get messy?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'Bookings by phone', 'body' => 'Appointments overlap and nobody knows what space is free.'],
                    ['icon' => 'file', 'title' => 'Paper instead of job cards', 'body' => 'Estimates and receipts get lost or are written by hand.'],
                    ['icon' => 'users', 'title' => 'Customers forgotten', 'body' => 'No reminder for the next service date.'],
                ],
                'showcase_title' => 'The workshop board and booking', 'showcase' => [
                    ['title' => 'Booking in 3 steps', 'body' => 'Service, then day and time, then details. Capacity follows the number of free bays.'],
                    ['title' => 'Live workshop board', 'body' => 'Refreshes every 7 seconds and shows each car and its stage.'],
                    ['title' => 'Approve and pay from the tracking page', 'body' => 'The customer approves the estimate and pays a deposit, then pays the balance online when the car is ready.'],
                ],
                'pillars_title' => 'Four things that organize the workshop',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'Online booking', 'body' => 'A booking and tracking page by plate number and phone.'],
                    ['icon' => 'wrench', 'title' => 'Job cards', 'body' => 'Estimates, receipts, technicians and a live board.'],
                    ['icon' => 'box', 'title' => 'Spare parts', 'body' => 'Parts stock and low-stock alerts.'],
                    ['icon' => 'message', 'title' => 'WhatsApp messages', 'body' => 'Estimate approval requests, "your car is ready" and service reminders.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the workshop system',
                    'before' => [['b' => 'Booking', 't' => 'Calls and overlapping times.'], ['b' => 'Work', 't' => 'Lost papers and estimates.'], ['b' => 'Customers', 't' => 'Forgotten services.']],
                    'after' => [['b' => 'Booking', 't' => 'Online, based on free bays.'], ['b' => 'Work', 't' => 'Job cards and printed receipts.'], ['b' => 'Customers', 't' => 'Service reminders on WhatsApp.']]],
                'metrics' => [['value' => '3', 'label' => 'Steps for a customer to book'], ['value' => '7s', 'label' => 'Workshop board refresh'], ['value' => '157', 'label' => 'Automated tests in the code, plus browser tests']],
                'paragraphs' => ['The system is a website for the workshop with a full management system. Customers book a service and choose the day and time, and the system counts what is free from the number of workshop bays.', 'Inside: a live workshop board, job cards with estimates and receipts, customers and cars, service reminders, stock, technicians and promo codes. It has two roles: owner and reception.', 'If you connect your accounts: the customer approves the estimate on the tracking page (or replies 1 to the WhatsApp message) and pays a deposit, gets a message with the balance and a link to pay it when the car is ready, and service reminders go out by themselves when they are due. When the car is delivered, one e-receipt goes to the tax authority for the whole job (deposit included), and its QR code prints on the delivery receipt. The workshop has no refunds, so there are no return receipts.'],
                'list' => ['Home page and a 3-step booking page', 'Track the car by plate and phone', 'Live workshop board', 'Job cards, estimates and 80 mm and A4 receipts', 'Estimate approval, deposit and balance paid online', 'WhatsApp: approval requests, car ready and service reminders', 'ETA e-receipt with a QR code on the delivery receipt', 'Customers, cars and service reminders', 'Spare parts and stock', 'Technicians, promo codes and a dashboard'],
                'faq' => [['q' => 'How many user types are there?', 'a' => 'Two: owner and reception staff.'], ['q' => 'Does it print receipts?', 'a' => 'Yes, on an 80 mm thermal printer or on A4 paper.'], ['q' => 'How does the customer approve the estimate?', 'a' => 'With a button on their private tracking page, or by replying 1 to the WhatsApp message. The approval only counts if the estimate has not changed since they saw it. There is no e-signature. WhatsApp messages go out by themselves once you connect a WhatsApp Business account; without it every button opens a ready WhatsApp message. The connection is tested against simulated answers only, not against Meta\'s servers.'], SystemsCatalogOnline::etaFaq('en')],
                'included' => SystemsCatalogOnline::included('en', ['payments', 'whatsapp', 'eta']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for e-receipts, WhatsApp reminders and payment checks'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('en', 'an estimate deposit, or the balance before picking up the car,'),
                'cta_title' => 'Ready to organize your workshop?',
            ],
        ];
    }

    private static function clinic(): array
    {
        return [
            'slug' => 'clinic-system', 'colors' => ['accent' => '#0284c7', 'ink' => '#06202e'],
            'name_en' => 'Clinic Booking System', 'name_ar' => 'نظام حجز وإدارة العيادة',
            'tagline_en' => 'Website, appointment booking and patient management for a private clinic.',
            'tagline_ar' => 'موقع وحجز مواعيد وإدارة المرضى لعيادة أو مجمع طبي خاص.',
            'ar' => [
                'kicker' => 'العيادات والمجمعات الطبية', 'headline' => 'مواعيد العيادة منظمة، من الحجز لحد الروشتة.',
                'subheadline' => 'المريض يحجز مع الدكتور واليوم والموعد ويوصله تذكير على الواتساب، والاستقبال يدير الطابور، والدكتور يشوف مرضاه بس ويطبع الروشتة.',
                'who' => 'أصحاب العيادات والمجمعات الطبية الصغيرة اللي عايزين يرتبوا الحجز والطابور وملفات المرضى.',
                'problems_title' => 'ليه مواعيد العيادة بتتلخبط؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'طابور غير منظم', 'body' => 'مرضى بيستنوا من غير ترتيب واضح.'],
                    ['icon' => 'file', 'title' => 'ملفات ورقية', 'body' => 'زيارات وروشتات مبعثرة.'],
                    ['icon' => 'lock', 'title' => 'خصوصية المريض', 'body' => 'مفيش معرفة مين فتح ملف مين.'],
                ],
                'showcase_title' => 'من الحجز للمتابعة', 'showcase' => [
                    ['title' => 'حجز للمريض', 'body' => 'دكتور، ثم يوم وموعد، ثم الاسم والتليفون.'],
                    ['title' => 'طابور الاستقبال', 'body' => 'متابعة الحاضرين والحجز المباشر للزيارات بدون موعد.'],
                    ['title' => 'روشتة جاهزة للطباعة', 'body' => 'زيارة وأدوية وإجراءات وروشتة A5.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم العيادة',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'حجز أونلاين', 'body' => 'صفحة حجز، وصفحة "موعدي" برابط خاص والتليفون.'],
                    ['icon' => 'users', 'title' => 'المرضى والفواتير', 'body' => 'ملفات المرضى والفواتير ورابط دفع أونلاين وتذكيرات المتابعة.'],
                    ['icon' => 'file', 'title' => 'ملف الدكتور', 'body' => 'كل دكتور يشوف مرضاه بس، مع سجل الزيارات والروشتات.'],
                    ['icon' => 'shield', 'title' => 'سجل المراجعة', 'body' => 'تعرف مين شاف إيه وفين.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام العيادة',
                    'before' => [['b' => 'الحجز', 't' => 'تليفونات وطابور غير منظم.'], ['b' => 'الملفات', 't' => 'ورق مبعثر.'], ['b' => 'الخصوصية', 't' => 'مفيش سجل مراجعة.']],
                    'after' => [['b' => 'الحجز', 't' => 'أونلاين وطابور منظم وتذكير على الواتساب.'], ['b' => 'الملفات', 't' => 'ملف لكل مريض وروشتة مطبوعة.'], ['b' => 'الخصوصية', 't' => 'سجل مراجعة بمين شاف إيه.']]],
                'metrics' => [['value' => '3', 'label' => 'أنواع مستخدمين: المالك والاستقبال والدكتور'], ['value' => 'A5', 'label' => 'روشتة جاهزة للطباعة'], ['value' => '111', 'label' => 'اختبار آلي في الكود، واختبارات متصفح']],
                'paragraphs' => ['النظام موقع للعيادة ونظام حجز وإدارة. المريض يحجز مع الدكتور المناسب ويتابع موعده من صفحة خاصة، والاستقبال يدير الطابور والحجز المباشر والفواتير، والدكتور يسجل الزيارة والأدوية والإجراءات ويطبع روشتة.', 'المالك عنده لوحة مؤشرات وقايمة أسعار وتقييمات وسجل بمين شاف إيه، وكل دكتور بيشوف مرضاه بس.', 'لو ربطت حساب واتساب Business، المريض بيوصله تذكير بموعده قبلها بساعات (اسمه والدكتور واليوم والساعة بس، من غير أي كلام طبي)، ويرد بـ 1 للتأكيد أو 2 عشان ياخد رابط يغير منه الميعاد. والاستقبال يقدر يعمل رابط دفع أونلاين لفاتورة الزيارة ويبعته للمريض، والدفع بيتسجل في الفاتورة زي الكاش. العيادة مفيهاش ربط بالإيصال الإلكتروني للضرائب.'],
                'list' => ['صفحة رئيسية وحجز بالدكتور واليوم والموعد', 'صفحة "موعدي" برابط خاص ورقم التليفون', 'طابور الاستقبال وحجز الزيارات المباشرة', 'المرضى والفواتير وتذكيرات المتابعة', 'تذكير بالموعد على الواتساب والرد بـ 1 للتأكيد أو 2 للتغيير', 'رابط دفع أونلاين لفاتورة الزيارة', 'سجل الزيارات والأدوية والإجراءات وروشتة A5', 'قايمة الأسعار والتقييمات ولوحة المؤشرات', 'سجل مراجعة بمين شاف إيه'],
                'faq' => [['q' => 'الدكتور يشوف كل المرضى؟', 'a' => 'لا، كل دكتور يشوف مرضاه بس.'], ['q' => 'النظام بيحمي بيانات المرضى؟', 'a' => 'فيه سجل مراجعة بمين شاف إيه. مفيش ادعاء بشهادات امتثال، فراجع القوانين الطبية في بلدك قبل الاستخدام.'], ['q' => 'بيبعت تذكير بالموعد على الواتساب لوحده؟', 'a' => 'أيوه، لو ربطت حساب واتساب Business بتاعك: كل ساعة بيبعت تذكير للمواعيد اللي جاية، مرة واحدة لكل موعد، ومن غير أي بيانات طبية. ردود المرضى بتظهر في ملف المريض للاستقبال. من غير حساب، زرار التذكير بيفتح رسالة جاهزة على واتساب. الربط متجرب على ردود تجريبية بس، مش على خوادم Meta.'], ['q' => 'بيطلع إيصال إلكتروني للضرائب؟', 'a' => 'لا. العيادة مفيهاش ربط بالإيصال الإلكتروني (ETA)، فمفيش إيصال ضريبي بيطلع لا للدفع في المكان ولا للدفع أونلاين.']],
                'included' => SystemsCatalogOnline::included('ar', ['payments', 'whatsapp']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'لتذكيرات الواتساب ومتابعة الدفع'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('ar', 'فاتورة الزيارة قبل الكشف أو بعده'),
                'cta_title' => 'جاهز تنظم عيادتك؟',
            ],
            'en' => [
                'kicker' => 'Clinics and medical centers', 'headline' => 'Clinic appointments in order, from booking to prescription.',
                'subheadline' => 'The patient books a doctor, a day and a time and gets a WhatsApp reminder, reception runs the queue, and each doctor sees only their own patients and prints the prescription.',
                'who' => 'Owners of small clinics and medical centers who want to organize booking, the queue and patient files.',
                'problems_title' => 'Why do clinic appointments get messy?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'An unorganized queue', 'body' => 'Patients wait with no clear order.'],
                    ['icon' => 'file', 'title' => 'Paper files', 'body' => 'Visits and prescriptions are scattered.'],
                    ['icon' => 'lock', 'title' => 'Patient privacy', 'body' => 'No way to know who opened whose file.'],
                ],
                'showcase_title' => 'From booking to follow-up', 'showcase' => [
                    ['title' => 'Patient booking', 'body' => 'Doctor, then day and time, then name and phone.'],
                    ['title' => 'Reception queue', 'body' => 'Follow who has arrived and book walk-in visits.'],
                    ['title' => 'Prescription ready to print', 'body' => 'Visit, medicines, procedures and an A5 prescription.'],
                ],
                'pillars_title' => 'Four things that organize the clinic',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'Online booking', 'body' => 'A booking page and a "my appointment" page by private link and phone.'],
                    ['icon' => 'users', 'title' => 'Patients and billing', 'body' => 'Patient files, billing, online payment links and follow-up reminders.'],
                    ['icon' => 'file', 'title' => 'The doctor file', 'body' => 'Each doctor sees only their patients, with visit and prescription records.'],
                    ['icon' => 'shield', 'title' => 'Audit log', 'body' => 'Know who looked at what and where.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the clinic system',
                    'before' => [['b' => 'Booking', 't' => 'Phone calls and a messy queue.'], ['b' => 'Files', 't' => 'Scattered paper.'], ['b' => 'Privacy', 't' => 'No audit log.']],
                    'after' => [['b' => 'Booking', 't' => 'Online with an organized queue and a WhatsApp reminder.'], ['b' => 'Files', 't' => 'A file per patient and a printed prescription.'], ['b' => 'Privacy', 't' => 'An audit log of who saw what.']]],
                'metrics' => [['value' => '3', 'label' => 'User types: owner, reception and doctor'], ['value' => 'A5', 'label' => 'Prescription ready to print'], ['value' => '111', 'label' => 'Automated tests in the code, plus browser tests']],
                'paragraphs' => ['The system is a clinic website with a booking and management system. The patient books the right doctor and follows the appointment from a private page, reception runs the queue, walk-ins and billing, and the doctor records the visit, medicines and procedures and prints a prescription.', 'The owner has a dashboard, a price list, reviews and an audit log of who saw what, and every doctor sees only their own patients.', 'If you connect a WhatsApp Business account, the patient gets a reminder a few hours before the appointment (first name, doctor, day and time only, never medical text) and replies 1 to confirm or 2 to get a link to change the time. Reception can also make an online payment link for a visit invoice and send it to the patient, and the payment is recorded on the invoice like cash. The clinic has no ETA e-receipt connection.'],
                'list' => ['Home page and booking by doctor, day and time', 'A "my appointment" page with a private link and phone', 'Reception queue and walk-in visits', 'Patients, billing and follow-up reminders', 'WhatsApp appointment reminders, reply 1 to confirm or 2 to change', 'Online payment link for a visit invoice', 'Visit records, medicines, procedures and an A5 prescription', 'Price list, reviews and a dashboard', 'An audit log of who saw what'],
                'faq' => [['q' => 'Does a doctor see all patients?', 'a' => 'No. Each doctor sees only their own patients.'], ['q' => 'Does it protect patient data?', 'a' => 'It has an audit log of who saw what. It makes no claim of compliance certificates, so check the medical data laws in your country before use.'], ['q' => 'Does it send WhatsApp appointment reminders by itself?', 'a' => 'Yes, once you connect your own WhatsApp Business account: every hour it sends a reminder for the coming appointments, once per appointment, with no medical data. Patient replies show in the patient file for reception. Without an account, the reminder button opens a ready WhatsApp message. The connection is tested against simulated answers only, not against Meta\'s servers.'], ['q' => 'Does it issue a tax e-receipt?', 'a' => 'No. The clinic has no ETA e-receipt connection, so no tax receipt is issued for payments at the desk or online.']],
                'included' => SystemsCatalogOnline::included('en', ['payments', 'whatsapp']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for WhatsApp reminders and payment checks'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('en', 'a visit invoice, before or after the visit,'),
                'cta_title' => 'Ready to organize your clinic?',
            ],
        ];
    }

    private static function coffee(): array
    {
        return [
            'slug' => 'coffee-shop-system', 'colors' => ['accent' => '#b45309', 'ink' => '#1c1009'],
            'name_en' => 'Coffee Shop Ordering System', 'name_ar' => 'نظام طلبات الكافيه',
            'tagline_en' => 'An animated website and ordering system for a coffee shop: dine-in, take-away and delivery.',
            'tagline_ar' => 'موقع متحرك ونظام طلبات لكافيه: داخل المكان وتيك أواي وتوصيل.',
            'ar' => [
                'kicker' => 'الكافيهات والمطاعم الصغيرة', 'headline' => 'كافيهك يستقبل الطلبات من الموبايل والترابيزة، والباريستا يشوفها فورا.',
                'subheadline' => 'موقع متحرك بقائمة طلب وسلة ودفع أونلاين، وكود QR لكل ترابيزة، ولوحة طلبات حية بتنبه على الطلب الجديد.',
                'who' => 'أصحاب الكافيهات والمحلات الصغيرة اللي عايزين طلبات من الموبايل وترابيزات ودليفري.',
                'problems_title' => 'ليه الطلبات بتتلخبط وقت الزحمة؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'طلبات شفوية', 'body' => 'الجرسون بيسجل والباريستا بيسمع غلط.'],
                    ['icon' => 'users', 'title' => 'مفيش طلب من الترابيزة', 'body' => 'العميل لازم يستنى حد ياخد طلبه.'],
                    ['icon' => 'chart', 'title' => 'متابعة الطلبات ضعيفة', 'body' => 'مش معروف الطلب فين دلوقتي.'],
                ],
                'showcase_title' => 'من المنيو للطباعة', 'showcase' => [
                    ['title' => 'قايمة الطلب', 'body' => 'أحجام وإضافات وسلة، من الموبايل أو من الترابيزة.'],
                    ['title' => 'لوحة طلبات حية', 'body' => 'بتتحدث كل 6 ثواني وبتصفّر لما يجي طلب جديد.'],
                    ['title' => 'تذكرة وإيصال إلكتروني', 'body' => 'تذكرة حرارية 80 مم عليها كود QR بتاع الإيصال الإلكتروني (ETA).'],
                ],
                'pillars_title' => 'أربع حاجات تشغل الكافيه',
                'pillars' => [
                    ['icon' => 'phone', 'title' => 'طلب بـ QR', 'body' => 'كود لكل ترابيزة يفتح المنيو مباشرة.'],
                    ['icon' => 'send', 'title' => 'توصيل', 'body' => 'مناطق التوصيل وأسعارها.'],
                    ['icon' => 'check', 'title' => 'شاشة الكاشير', 'body' => 'طلب جديد بسرعة وأكواد خصم.'],
                    ['icon' => 'message', 'title' => 'دفع أونلاين وواتساب', 'body' => 'العميل يدفع وهو بيطلب، ويوصله إن طلبه اتستلم وجاهز وفي الطريق.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الكافيه',
                    'before' => [['b' => 'الطلب', 't' => 'شفوي وبيتنسي.'], ['b' => 'الترابيزة', 't' => 'العميل بيستنى الجرسون.'], ['b' => 'المتابعة', 't' => 'مفيش لوحة للطلبات.']],
                    'after' => [['b' => 'الطلب', 't' => 'من الموبايل أو QR.'], ['b' => 'الترابيزة', 't' => 'العميل بيطلب بنفسه.'], ['b' => 'المتابعة', 't' => 'لوحة حية بتنبه على كل طلب.']]],
                'metrics' => [['value' => '6s', 'label' => 'تحديث لوحة الطلبات'], ['value' => '173', 'label' => 'اختبار آلي في الكود، واختبارات متصفح'], ['value' => 'QR', 'label' => 'كود لكل ترابيزة']],
                'paragraphs' => ['النظام موقع متحرك للكافيه مع نظام طلبات. العميل يشوف المنيو ويختار الحجم والإضافات ويطلب داخل المكان أو تيك أواي أو توصيل، ويتابع طلبه بكود.', 'الموظفين عندهم لوحة طلبات حية وشاشة كاشير وطباعة تذكرة، والمالك يدير المنيو والترابيزات وأكواد QR ومناطق التوصيل والخصومات والتقييمات والموظفين.', 'لو ربطت حساباتك: العميل يدفع أونلاين وهو بيطلب، والطلب مبيدخلش التحضير غير لما الفلوس توصل (أو يختار يدفع كاش). العميل اللي يوافق بيوصله على الواتساب إن طلبه اتستلم وجاهز وفي الطريق، ومفيش رسايل دعاية. كل طلب مدفوع بيطلع له إيصال إلكتروني لمصلحة الضرائب، والطلب المدفوع اللي يتلغي بيطلع له إيصال مرتجع. ولو عندك متجر على Shopify أو WooCommerce أو سلة، المنتجات بتتنشر متاحة أو خلصانة (مش بالكمية)، وطلبات المتجر بتدخل لوحة الطلبات زي أي طلب. الأحجام والإضافات مش بتتزامن مع المتجر.'],
                'list' => ['صفحة رئيسية متحركة وقايمة طلب بسلة', 'أحجام وإضافات للمنتجات', 'كود QR لكل ترابيزة وصفحة تتبع الطلب', 'لوحة طلبات حية مع تنبيه صوتي', 'شاشة كاشير وطباعة تذكرة 80 مم', 'دفع أونلاين وقت الطلب', 'رسايل واتساب بحالة الطلب للعميل اللي يوافق', 'إيصال إلكتروني (ETA) بكود QR على التذكرة وإيصال مرتجع عند الإلغاء', 'ربط بمتجر Shopify أو WooCommerce أو سلة', 'مناطق التوصيل وأكواد الخصم', 'المنيو والتقييمات وحسابات الموظفين'],
                'faq' => [['q' => 'في توصيل؟', 'a' => 'أيوه، النظام فيه مناطق توصيل بأسعارها.'], ['q' => 'الطلب من الترابيزة إزاي؟', 'a' => 'كل ترابيزة ليها كود QR بيفتح المنيو مباشرة.'], ['q' => 'ينفع أربطه بمتجري أونلاين؟', 'a' => 'أيوه، Shopify أو WooCommerce أو سلة. المشروبات بتتعمل بالطلب، فالمنتج بيتنشر متاح أو خلصان مش بكمية، وطلب المتجر بيدخل لوحة الطلبات بتذكرة جديدة. الإضافات اللي العميل يختارها في المتجر مش بتتقري. الربط متجرب على ردود تجريبية بس، ومتجربش على متجر حقيقي، فجرّبه على متجر تجريبي الأول.'], SystemsCatalogOnline::etaFaq('ar')],
                'included' => SystemsCatalogOnline::included('ar', ['payments', 'whatsapp', 'eta', 'store']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'للإيصالات الإلكترونية ومزامنة المتجر ومتابعة الدفع'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('ar', 'تمن طلبه وهو بيطلب (من الموقع أو من الترابيزة)'),
                'cta_title' => 'جاهز تستقبل طلباتك أونلاين؟',
            ],
            'en' => [
                'kicker' => 'Coffee shops and small restaurants', 'headline' => 'Your coffee shop takes orders from phones and tables, and the barista sees them instantly.',
                'subheadline' => 'An animated website with a menu, cart and online payment, a QR code per table, and a live orders board that alerts you to every new order.',
                'who' => 'Owners of coffee shops and small shops who want phone orders, tables and delivery.',
                'problems_title' => 'Why do orders get messy in the rush?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'Spoken orders', 'body' => 'The waiter writes and the barista hears it wrong.'],
                    ['icon' => 'users', 'title' => 'No ordering from the table', 'body' => 'The customer has to wait for someone to take the order.'],
                    ['icon' => 'chart', 'title' => 'Weak order tracking', 'body' => 'Nobody knows where an order is right now.'],
                ],
                'showcase_title' => 'From the menu to the printer', 'showcase' => [
                    ['title' => 'The order menu', 'body' => 'Sizes, extras and a cart, from a phone or the table.'],
                    ['title' => 'Live orders board', 'body' => 'Refreshes every 6 seconds and beeps for a new order.'],
                    ['title' => 'Ticket and e-receipt', 'body' => 'An 80 mm thermal ticket with the QR code of the ETA e-receipt.'],
                ],
                'pillars_title' => 'Four things that run the shop',
                'pillars' => [
                    ['icon' => 'phone', 'title' => 'QR ordering', 'body' => 'A code per table opens the menu directly.'],
                    ['icon' => 'send', 'title' => 'Delivery', 'body' => 'Delivery areas and their prices.'],
                    ['icon' => 'check', 'title' => 'Cashier screen', 'body' => 'A quick new order and promo codes.'],
                    ['icon' => 'message', 'title' => 'Online payment and WhatsApp', 'body' => 'Customers pay as they order and hear when the order is received, ready and on its way.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the coffee shop system',
                    'before' => [['b' => 'Ordering', 't' => 'Spoken and forgotten.'], ['b' => 'Tables', 't' => 'The customer waits for the waiter.'], ['b' => 'Tracking', 't' => 'No orders board.']],
                    'after' => [['b' => 'Ordering', 't' => 'From a phone or a QR code.'], ['b' => 'Tables', 't' => 'The customer orders alone.'], ['b' => 'Tracking', 't' => 'A live board that alerts on every order.']]],
                'metrics' => [['value' => '6s', 'label' => 'Orders board refresh'], ['value' => '173', 'label' => 'Automated tests in the code, plus browser tests'], ['value' => 'QR', 'label' => 'A code per table']],
                'paragraphs' => ['The system is an animated coffee shop website with an ordering system. Customers see the menu, choose size and extras and order dine-in, take-away or delivery, and follow their order with a code.', 'Staff have a live orders board, a cashier screen and ticket printing, and the owner manages the menu, tables and QR codes, delivery areas, promo codes, reviews and staff.', 'If you connect your accounts: customers pay online as they order, and the order only goes to the kitchen once the money arrives (or they choose to pay cash). Customers who agree get WhatsApp messages when the order is received, ready and on its way, and there are no marketing messages. Every paid order gets an e-receipt to the tax authority, and a paid order that is cancelled gets a return receipt. If you also sell on Shopify, WooCommerce or Salla, items are published as available or sold out (not by quantity), and store orders join the orders board like any order. Sizes and extras are not synced with the store.'],
                'list' => ['An animated home page and an order menu with a cart', 'Sizes and extras for products', 'A QR code per table and an order tracking page', 'Live orders board with a sound alert', 'Cashier screen and 80 mm ticket printing', 'Online payment at checkout', 'WhatsApp order updates for customers who agree', 'ETA e-receipt with a QR code on the ticket, and a return receipt on cancellation', 'Sync with a Shopify, WooCommerce or Salla store', 'Delivery areas and promo codes', 'Menu, reviews and staff accounts'],
                'faq' => [['q' => 'Is there delivery?', 'a' => 'Yes. The system has delivery areas with their prices.'], ['q' => 'How does ordering from the table work?', 'a' => 'Every table has a QR code that opens the menu directly.'], ['q' => 'Can I connect it to my online store?', 'a' => 'Yes: Shopify, WooCommerce or Salla. Drinks are made to order, so an item is published as available or sold out, not with a stock count, and a store order joins the orders board with a new ticket. Extras chosen in the store are not read. The connection is tested against simulated answers only, not against a real store, so try it on a test store first.'], SystemsCatalogOnline::etaFaq('en')],
                'included' => SystemsCatalogOnline::included('en', ['payments', 'whatsapp', 'eta', 'store']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for e-receipts, online-store sync and payment checks'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('en', 'for the order while ordering (on the website or at the table)'),
                'cta_title' => 'Ready to take orders online?',
            ],
        ];
    }

    private static function gym(): array
    {
        return [
            'slug' => 'gym-system', 'colors' => ['accent' => '#dc2626', 'ink' => '#1a0b0b'],
            'name_en' => 'Gym Management System', 'name_ar' => 'نظام إدارة الجيم',
            'tagline_en' => 'Membership management, check-in and a public website for a gym or fitness club.',
            'tagline_ar' => 'إدارة الاشتراكات وتسجيل الحضور وموقع للجيم أو النادي الرياضي.',
            'ar' => [
                'kicker' => 'الجيمات والنوادي الرياضية', 'headline' => 'اشتراكات الجيم والحضور والتجديدات في مكان واحد.',
                'subheadline' => 'الحضور بسكانر باركود، والأعضاء ليهم بوابة يحجزوا منها الحصص، وتذكير التجديد والحصص بيوصل العضو على الواتساب مع رابط يدفع منه الباقي.',
                'who' => 'أصحاب الجيمات والنوادي اللي عايزين يتابعوا الاشتراكات والحضور والتجديدات.',
                'problems_title' => 'ليه إدارة الجيم بتتعب؟',
                'problems' => [
                    ['icon' => 'users', 'title' => 'اشتراكات في دفاتر', 'body' => 'صعب تعرف مين اشتراكه خلص.'],
                    ['icon' => 'clock', 'title' => 'الحضور يدوي', 'body' => 'طابور عند الاستقبال وأخطاء في التسجيل.'],
                    ['icon' => 'message', 'title' => 'تجديدات بتفوت', 'body' => 'مفيش تذكير قبل ما الاشتراك ينتهي.'],
                ],
                'showcase_title' => 'من الاستقبال للتجديد', 'showcase' => [
                    ['title' => 'تسجيل الحضور', 'body' => 'بسكانر باركود USB أو بالكود أو التليفون.'],
                    ['title' => 'الاشتراكات', 'body' => 'بيع وتجديد وترقية وتقسيط وتجميد وإيصال، ورابط دفع أونلاين للباقي.'],
                    ['title' => 'بوابة العضو', 'body' => 'العضو يشوف اشتراكه ويحجز ويلغي حصصه.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم الجيم',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'تسجيل الحضور', 'body' => 'سريع بباركود أو كود أو تليفون.'],
                    ['icon' => 'file', 'title' => 'الاشتراكات', 'body' => 'خطط بمدة أو باقة حصص أو يوم واحد.'],
                    ['icon' => 'users', 'title' => 'الحصص والكوتشات', 'body' => 'قايمة حضور وقايمة انتظار.'],
                    ['icon' => 'message', 'title' => 'التجديدات', 'body' => 'تذكير واتساب قبل الانتهاء، ورابط دفع لو عليه باقي.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الجيم',
                    'before' => [['b' => 'الحضور', 't' => 'تسجيل يدوي.'], ['b' => 'الاشتراكات', 't' => 'دفاتر متفرقة.'], ['b' => 'التجديد', 't' => 'بيفوت من غير تذكير.']],
                    'after' => [['b' => 'الحضور', 't' => 'بالباركود في ثواني.'], ['b' => 'الاشتراكات', 't' => 'كلها في النظام.'], ['b' => 'التجديد', 't' => 'تذكير بالواتساب.']]],
                'metrics' => [['value' => 'USB', 'label' => 'سكانر باركود للحضور'], ['value' => 'QR', 'label' => 'كارت عضوية مطبوع'], ['value' => '138', 'label' => 'اختبار آلي في الكود، واختبارات متصفح']],
                'paragraphs' => ['النظام موقع للجيم مع نظام إدارة الاشتراكات. الزائر يسجل اهتمامه، والعضو يدخل بوابته بكوده وتليفونه عشان يشوف اشتراكه ويحجز الحصص أو يدخل قايمة الانتظار.', 'الموظفين بيسجلوا الحضور ويبيعوا ويجددوا الاشتراكات ويطبعوا كارت العضو، والمالك يدير الخطط والكوتشات وأكواد الخصم والتقييمات. فيه صلاحيتين: المالك والموظف.', 'لو ربطت حساب واتساب Business، العضو اللي اشتراكه قرب يخلص بيوصله تذكير لوحده، ولو عليه باقي الرسالة فيها رابط يدفعه أونلاين، وبيوصله تذكير قبل الحصة اللي حاجزها ويرد بـ 2 عشان يلغي مكانه. الموظف يقدر يعمل رابط دفع لأي باقي على الاشتراك ويبعته للعضو. الدفع أونلاين بالروابط دي بس، مش من بوابة العضو.'],
                'list' => ['صفحة رئيسية وتسجيل اهتمام (Lead)', 'بوابة العضو: الاشتراك والحصص وقايمة الانتظار', 'تسجيل الحضور بسكانر باركود', 'كارت عضوية مطبوع بكود QR', 'بيع وتجديد وترقية وتقسيط وتجميد الاشتراكات', 'رابط دفع أونلاين لباقي الاشتراك أو القسط', 'واتساب: تذكير التجديد والباقي برابط دفع وتذكير الحصص', 'الحصص وقايمة الحضور والانتظار', 'التجديدات والمهتمين ولوحة المؤشرات'],
                'faq' => [['q' => 'الحضور بيتسجل إزاي؟', 'a' => 'بسكانر باركود USB، أو بكتابة الكود أو رقم التليفون.'], ['q' => 'العضو يحجز بنفسه؟', 'a' => 'أيوه، من بوابة العضو بكوده وتليفونه.'], ['q' => 'التذكيرات بتتبعت على الواتساب لوحدها؟', 'a' => 'أيوه، لو ربطت حساب واتساب Business بتاعك: تذكير قبل نهاية الاشتراك (مع رابط دفع لو فيه باقي)، وتذكير قبل كل حصة محجوزة. من غير حساب، الزراير بتفتح رسالة جاهزة على واتساب. الربط متجرب على ردود تجريبية بس، مش على خوادم Meta.']],
                'included' => SystemsCatalogOnline::included('ar', ['payments', 'whatsapp']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'لتذكيرات الواتساب ومتابعة الدفع وإنهاء التجميد'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('ar', 'باقي اشتراكه أو القسط'),
                'cta_title' => 'جاهز تنظم جيمك؟',
            ],
            'en' => [
                'kicker' => 'Gyms and fitness clubs', 'headline' => 'Gym memberships, attendance and renewals in one place.',
                'subheadline' => 'Check-in with a barcode scanner, a member portal to book classes, and renewal and class reminders that reach the member on WhatsApp with a link to pay what is left.',
                'who' => 'Owners of gyms and clubs who want to follow memberships, attendance and renewals.',
                'problems_title' => 'Why is running a gym tiring?',
                'problems' => [
                    ['icon' => 'users', 'title' => 'Memberships in notebooks', 'body' => 'Hard to know whose membership ended.'],
                    ['icon' => 'clock', 'title' => 'Manual check-in', 'body' => 'A queue at reception and entry mistakes.'],
                    ['icon' => 'message', 'title' => 'Missed renewals', 'body' => 'No reminder before a membership ends.'],
                ],
                'showcase_title' => 'From reception to renewal', 'showcase' => [
                    ['title' => 'Check-in', 'body' => 'With a USB barcode scanner, a code or a phone number.'],
                    ['title' => 'Memberships', 'body' => 'Sell, renew, upgrade, instalments, freeze and a receipt, plus an online payment link for the balance.'],
                    ['title' => 'Member portal', 'body' => 'Members see their membership and book or cancel classes.'],
                ],
                'pillars_title' => 'Four things that organize the gym',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'Check-in', 'body' => 'Fast with a barcode, a code or a phone.'],
                    ['icon' => 'file', 'title' => 'Memberships', 'body' => 'Plans by period, class pack or day pass.'],
                    ['icon' => 'users', 'title' => 'Classes and coaches', 'body' => 'Class roster and a waitlist.'],
                    ['icon' => 'message', 'title' => 'Renewals', 'body' => 'A WhatsApp reminder before expiry, with a payment link if money is owed.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the gym system',
                    'before' => [['b' => 'Check-in', 't' => 'Manual entry.'], ['b' => 'Memberships', 't' => 'Scattered notebooks.'], ['b' => 'Renewals', 't' => 'Missed with no reminder.']],
                    'after' => [['b' => 'Check-in', 't' => 'By barcode in seconds.'], ['b' => 'Memberships', 't' => 'All in the system.'], ['b' => 'Renewals', 't' => 'A WhatsApp reminder.']]],
                'metrics' => [['value' => 'USB', 'label' => 'Barcode scanner check-in'], ['value' => 'QR', 'label' => 'Printed member card'], ['value' => '138', 'label' => 'Automated tests in the code, plus browser tests']],
                'paragraphs' => ['The system is a gym website with a membership management system. Visitors leave their interest, and members sign in to their portal with their code and phone to see their membership and book classes or join the waitlist.', 'Staff record check-ins, sell and renew memberships and print member cards, and the owner manages plans, coaches, promo codes and reviews. It has two roles: owner and staff.', 'If you connect a WhatsApp Business account, a member whose membership is ending gets a reminder by itself, with a link to pay online if money is still owed, and gets a reminder before a booked class, where replying 2 cancels the place. Staff can make a payment link for any balance on a membership and send it to the member. Online payment works through these links only, not from the member portal.'],
                'list' => ['Home page and an interest form (lead)', 'Member portal: membership, classes and waitlist', 'Check-in with a barcode scanner', 'Printed member card with a QR code', 'Sell, renew, upgrade, instalments and freeze memberships', 'Online payment link for a membership balance or instalment', 'WhatsApp: renewal and balance reminders with a payment link, and class reminders', 'Classes with a roster and a waitlist', 'Renewals, leads and a dashboard'],
                'faq' => [['q' => 'How is check-in recorded?', 'a' => 'With a USB barcode scanner, or by typing the code or phone number.'], ['q' => 'Can a member book alone?', 'a' => 'Yes, from the member portal with their code and phone.'], ['q' => 'Are WhatsApp reminders sent by themselves?', 'a' => 'Yes, once you connect your own WhatsApp Business account: a reminder before a membership ends (with a payment link if money is owed), and a reminder before each booked class. Without an account, the buttons open a ready WhatsApp message. The connection is tested against simulated answers only, not against Meta\'s servers.']],
                'included' => SystemsCatalogOnline::included('en', ['payments', 'whatsapp']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for WhatsApp reminders, payment checks and ending freezes'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('en', 'what is left on a membership or an instalment'),
                'cta_title' => 'Ready to organize your gym?',
            ],
        ];
    }

    private static function mobileShop(): array
    {
        return [
            'slug' => 'mobile-shop-system', 'colors' => ['accent' => '#4f46e5', 'ink' => '#111827'],
            'name_en' => 'Mobile Shop Management System', 'name_ar' => 'نظام إدارة محلات الموبايل',
            'tagline_en' => 'Point of sale and back office for mobile phone shops, with IMEI tracking and branches.',
            'tagline_ar' => 'نقطة بيع وإدارة محلات الموبايل مع تتبع IMEI والفروع.',
            'ar' => [
                'kicker' => 'محلات الموبايل والإكسسوارات', 'headline' => 'تحكم كامل في المبيعات والصيانة والمخزون.',
                'subheadline' => 'تتبع كل جهاز بالـ IMEI، وبيع بالآجل وتابع ديون العملاء، وأدر الفروع والصيانة والمشتريات والإيصال الإلكتروني ومتجرك أونلاين من شاشة واحدة.',
                'who' => 'أصحاب محلات الموبايل والإكسسوارات، وللي عايز يقدم النظام لمحلات كتير كخدمة اشتراك.',
                'problems_title' => 'مشاكل بتواجه محلات الموبايل',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'حسابات ويومية بتلخبط', 'body' => 'فروقات نقدية غير واضحة.'],
                    ['icon' => 'box', 'title' => 'صعوبة حصر الأجهزة', 'body' => 'نواقص مخزن من غير تنبيهات.'],
                    ['icon' => 'wrench', 'title' => 'الصيانة معقدة', 'body' => 'بيانات استلام الأجهزة بتضيع.'],
                ],
                'showcase_title' => 'شاشة مصممة لسرعة العمل', 'showcase' => [
                    ['title' => 'بيع سريع', 'body' => 'مسح IMEI أو باركود وفاتورة في ثواني.'],
                    ['title' => 'آجل وديون', 'body' => 'بيع بالآجل ومتابعة المدفوعات وديون العملاء.'],
                    ['title' => 'صيانة بعربون', 'body' => 'استلام جهاز وعربون وحالات الإصلاح.'],
                ],
                'pillars_title' => 'أربع ركائز لإدارة المحل',
                'pillars' => [
                    ['icon' => 'phone', 'title' => 'المخزون بالـ IMEI', 'body' => 'تتبع كل جهاز، والإكسسوارات بالكمية.'],
                    ['icon' => 'send', 'title' => 'الفروع والتحويلات', 'body' => 'مخزون الفروع وتحويلات بينها.'],
                    ['icon' => 'wrench', 'title' => 'الصيانة', 'body' => 'استلام وعربون وخطوات الحالة.'],
                    ['icon' => 'file', 'title' => 'الإيصال الإلكتروني والمتجر', 'body' => 'إيصال ETA لكل فرع، والإكسسوارات بتتزامن مع Shopify وWooCommerce وسلة.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع النظام',
                    'before' => [['b' => 'الدفاتر', 't' => 'ضياع فواتير وأرقام.'], ['b' => 'الجرد', 't' => 'يستغرق أيام.'], ['b' => 'المتابعة', 't' => 'صعب معرفة الربح الحقيقي.']],
                    'after' => [['b' => 'الفواتير', 't' => 'محفوظة لكل عملية.'], ['b' => 'الجرد', 't' => 'لحظي بالـ IMEI.'], ['b' => 'التقارير', 't' => 'لوحة محدثة باستمرار.']]],
                'metrics' => [['value' => 'IMEI', 'label' => 'تتبع كل جهاز'], ['value' => '161', 'label' => 'اختبار آلي في الكود، واختبارات متصفح'], ['value' => 'SaaS', 'label' => 'تجربة مجانية واشتراكات للعملاء']],
                'paragraphs' => ['النظام نقطة بيع ولوحة إدارة لمحلات الموبايل. الأجهزة بتتتبع بالـ IMEI والإكسسوارات بالكمية، وفيه فروع وتحويلات مخزون، وبيع بالآجل وديون العملاء، وصيانة بعربون، ومشتريات من الموردين، وتقارير.', 'النظام متعدد المحلات: صاحب المنصة يضيف العملاء ويسجل مدفوعات الاشتراك ويوقف العميل المتأخر، والعميل المنتهي أو الموقوف بيتمنع لكن بياناته بتفضل. فيه تسجيل ذاتي بتجربة مجانية (ممكن تقفله). التركيب على استضافة cPanel عادية من غير Redis، ومحتاج مهمة cron كل دقيقة للإيصالات الإلكترونية ومزامنة المتجر.', 'كل بيع من شاشة البيع بيطلع له إيصال إلكتروني لمصلحة الضرائب، وكل فرع ليه بيانات جهاز كاشير خاصة بيه، وكود QR بيتطبع على إيصال البيع. الصيانة مش بتتبعت للمصلحة، والنظام مفيهوش مرتجعات فمفيش إيصال مرتجع. ولو عندك متجر على Shopify أو WooCommerce أو سلة: مخزون وأسعار الإكسسوارات بتروح المتجر، وطلبات المتجر بترجع مبيعات للفرع. الموبايلات مش بتتزامن، لأن طلب المتجر مبيقولش أنهي IMEI هيخرج.'],
                'list' => ['مخزون الأجهزة بالـ IMEI والإكسسوارات بالكمية', 'فروع وتحويلات مخزون بينها', 'شاشة بيع وبيع بالآجل وديون العملاء', 'صيانة بعربون وخطوات الحالة', 'مشتريات من الموردين وتقارير', 'إيصال إلكتروني (ETA) لكل فرع بكود QR على الإيصال', 'ربط بمتجر Shopify أو WooCommerce أو سلة: الإكسسوارات بس', 'أدوار وصلاحيات بتبنيها بنفسك', 'طباعة حرارية 58 مم و80 مم وA4', 'طبقة اشتراكات: عملاء ومدفوعات وإيقاف'],
                'faq' => [['q' => 'ينفع أقدمه كخدمة لمحلات كتير؟', 'a' => 'أيوه، فيه طبقة متعددة المحلات: تضيف العملاء وتسجل مدفوعات الاشتراك وتوقف المتأخر.'], ['q' => 'لو اشتراك محل خلص؟', 'a' => 'بيتمنع من الدخول لكن بياناته بتفضل محفوظة.'], ['q' => 'الموبايلات بتتزامن مع المتجر أونلاين؟', 'a' => 'لا، الإكسسوارات بس. كل موبايل بيتباع بالـ IMEI بتاعه، وطلب المتجر مبيقولش أنهي جهاز هيخرج، فالطلب اللي فيه موبايل بيقف ويستناك تبيعه من شاشة البيع وتختار الـ IMEI. الربط متجرب على ردود تجريبية بس، ومتجربش على متجر حقيقي، فجرّبه على متجر تجريبي الأول.'], SystemsCatalogOnline::etaFaq('ar')],
                'included' => array_merge(SystemsCatalogOnline::included('ar', ['eta', 'store']), ['تسجيل الدفع كاش أو كارت أو تحويل أو محفظة أو آجل']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'للإيصالات الإلكترونية ومزامنة المتجر'),
                'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا. مفيش ربط ببوابات دفع جوه النظام. البيع بيتسجل كاش أو كارت أو تحويل أو محفظة أو آجل، وطلب المتجر المدفوع أونلاين بيتسجل كارت، والدفع عند الاستلام بيتسجل آجل على العميل.'],
                'cta_title' => 'جاهز تدير محلك بنظام واحد؟',
            ],
            'en' => [
                'kicker' => 'Mobile phone and accessory shops', 'headline' => 'Full control of sales, repairs and stock.',
                'subheadline' => 'Track every phone by IMEI, sell on credit and follow customer debts, and run branches, repairs, purchases, e-receipts and your online store from one screen.',
                'who' => 'Owners of mobile phone and accessory shops, and anyone who wants to offer the system to many shops as a subscription.',
                'problems_title' => 'Problems mobile shops face',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'Messy accounts and daily close', 'body' => 'Unclear cash differences.'],
                    ['icon' => 'box', 'title' => 'Hard to count phones', 'body' => 'Stock shortages with no alerts.'],
                    ['icon' => 'wrench', 'title' => 'Complicated repairs', 'body' => 'Device intake details get lost.'],
                ],
                'showcase_title' => 'A screen made for fast work', 'showcase' => [
                    ['title' => 'Quick sale', 'body' => 'Scan an IMEI or barcode and get an invoice in seconds.'],
                    ['title' => 'Credit and debts', 'body' => 'Sell on credit and follow payments and customer debts.'],
                    ['title' => 'Repairs with a deposit', 'body' => 'Device intake, deposit and repair status steps.'],
                ],
                'pillars_title' => 'Four pillars of running the shop',
                'pillars' => [
                    ['icon' => 'phone', 'title' => 'Stock by IMEI', 'body' => 'Track each phone, and accessories by quantity.'],
                    ['icon' => 'send', 'title' => 'Branches and transfers', 'body' => 'Branch stock and transfers between them.'],
                    ['icon' => 'wrench', 'title' => 'Repairs', 'body' => 'Intake, deposit and status steps.'],
                    ['icon' => 'file', 'title' => 'E-receipts and online store', 'body' => 'An ETA e-receipt per branch, and accessories synced with Shopify, WooCommerce and Salla.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the system',
                    'before' => [['b' => 'Notebooks', 't' => 'Lost invoices and numbers.'], ['b' => 'Stock count', 't' => 'Takes days.'], ['b' => 'Follow-up', 't' => 'Hard to know the real profit.']],
                    'after' => [['b' => 'Invoices', 't' => 'Saved for every sale.'], ['b' => 'Stock count', 't' => 'Instant by IMEI.'], ['b' => 'Reports', 't' => 'A dashboard always up to date.']]],
                'metrics' => [['value' => 'IMEI', 'label' => 'Track every phone'], ['value' => '161', 'label' => 'Automated tests in the code, plus browser tests'], ['value' => 'SaaS', 'label' => 'Free trial and subscriptions for clients']],
                'paragraphs' => ['The system is a point of sale and back office for mobile phone shops. Phones are tracked by IMEI and accessories by quantity, with branches and stock transfers, credit sales and customer debts, repairs with deposits, supplier purchases and reports.', 'It is multi-shop: the platform owner adds clients, records subscription payments and suspends a late client, and an expired or suspended client is blocked but keeps their data. It has self-service signup with a free trial (which can be turned off). It installs on normal cPanel hosting with no Redis, and needs a cron job every minute for e-receipts and online-store sync.', 'Every sale from the sell screen gets an e-receipt to the tax authority, each branch has its own cash register details, and the QR code prints on the sale receipt. Repairs are not reported, and the system has no returns, so there are no return receipts. If you sell on Shopify, WooCommerce or Salla: accessory stock and prices go to the store, and store orders come back as sales of a branch. Phones are not synced, because a store order cannot say which IMEI leaves the shelf.'],
                'list' => ['Phone stock by IMEI and accessories by quantity', 'Branches and stock transfers between them', 'A sell screen, credit sales and customer debts', 'Repairs with a deposit and status steps', 'Supplier purchases and reports', 'ETA e-receipt per branch with a QR code on the receipt', 'Sync with a Shopify, WooCommerce or Salla store: accessories only', 'Roles and permissions you build yourself', 'Thermal 58 mm, 80 mm and A4 printing', 'A subscription layer: clients, payments and suspension'],
                'faq' => [['q' => 'Can I offer it as a service to many shops?', 'a' => 'Yes. It has a multi-shop layer: you add clients, record subscription payments and suspend late ones.'], ['q' => 'What if a shop subscription ends?', 'a' => 'The shop is blocked from signing in, but its data stays saved.'], ['q' => 'Are phones synced with the online store?', 'a' => 'No, only accessories. Each phone is sold by its own IMEI, and a store order cannot say which unit leaves, so an order with a phone stops and waits for you to sell it from the sell screen and pick the IMEI. The connection is tested against simulated answers only, not against a real store, so try it on a test store first.'], SystemsCatalogOnline::etaFaq('en')],
                'included' => array_merge(SystemsCatalogOnline::included('en', ['eta', 'store']), ['Payments recorded as cash, card, transfer, wallet or credit']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for e-receipts and online-store sync'),
                'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => 'No. There is no payment gateway inside the system. A sale is recorded as cash, card, transfer, wallet or credit; a store order paid online is recorded as card, and cash on delivery as credit on the customer.'],
                'cta_title' => 'Ready to run your shop with one system?',
            ],
        ];
    }

    private static function salon(): array
    {
        return [
            'slug' => 'salon-system', 'colors' => ['accent' => '#be185d', 'ink' => '#1f0a16'],
            'name_en' => 'Salon Booking System', 'name_ar' => 'نظام حجز وإدارة الصالون',
            'tagline_en' => 'A booking website and staff dashboard for a salon, barber shop or spa.',
            'tagline_ar' => 'موقع حجز ولوحة تحكم للموظفين لصالون أو حلاق أو سبا.',
            'ar' => [
                'kicker' => 'الصالونات والحلاقين والسبا', 'headline' => 'حجوزات الصالون من غير تداخل، وكل موظف ليه جدوله.',
                'subheadline' => 'العميل يختار الخدمة والشخص (أو أي متاح) واليوم والموعد ويقدر يدفع عربون أونلاين، والنظام يمنع الحجز المزدوج، والكاشير يحسب الولاء والبقشيش والعربون.',
                'who' => 'أصحاب الصالونات ومحلات الحلاقة والسبا اللي عايزين حجز أونلاين وجدول لكل موظف.',
                'problems_title' => 'ليه حجوزات الصالون بتتداخل؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'حجز بالتليفون', 'body' => 'مواعيد بتتداخل أو بتتنسي.'],
                    ['icon' => 'users', 'title' => 'جدول كل موظف', 'body' => 'مفيش معرفة مين فاضي ومتى.'],
                    ['icon' => 'message', 'title' => 'عملاء بينسوا', 'body' => 'مفيش تذكير بالموعد.'],
                ],
                'showcase_title' => 'من الحجز للكاشير', 'showcase' => [
                    ['title' => 'حجز في خطوات', 'body' => 'خدمة، ثم الشخص أو أي متاح، ثم اليوم والموعد، ثم التأكيد.'],
                    ['title' => 'تقويم كل موظف', 'body' => 'جدول لكل شخص مع أوقات العمل والراحة والإجازات.'],
                    ['title' => 'كاشير', 'body' => 'ولاء وبقشيش وأكواد خصم وخصم العربون، وإيصال 80 مم عليه كود QR بتاع الإيصال الإلكتروني.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم الصالون',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'حجز أونلاين', 'body' => 'صفحة حجز، وصفحة للعميل يغير أو يلغي أو يقيّم.'],
                    ['icon' => 'users', 'title' => 'الفريق', 'body' => 'أوقات العمل والراحة والإجازات لكل شخص.'],
                    ['icon' => 'shield', 'title' => 'منع الحجز المزدوج', 'body' => 'محرك مواعيد متاح اتختبر بسباق عمليات متزامنة.'],
                    ['icon' => 'message', 'title' => 'التذكيرات', 'body' => 'تذكير على الواتساب قبل الموعد، والعميل يرد بـ 1 للتأكيد أو 2 للتغيير.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الصالون',
                    'before' => [['b' => 'الحجز', 't' => 'تليفونات وتداخل.'], ['b' => 'الجدول', 't' => 'دفتر مواعيد.'], ['b' => 'العملاء', 't' => 'ينسوا مواعيدهم.']],
                    'after' => [['b' => 'الحجز', 't' => 'أونلاين بدون تداخل.'], ['b' => 'الجدول', 't' => 'تقويم لكل موظف.'], ['b' => 'العملاء', 't' => 'تذكير بالواتساب.']]],
                'metrics' => [['value' => '5', 'label' => 'خطوات في معالج الحجز'], ['value' => '80mm', 'label' => 'إيصال حراري'], ['value' => '166', 'label' => 'اختبار آلي في الكود، واختبارات متصفح']],
                'paragraphs' => ['النظام موقع حجز للصالون مع لوحة للموظفين. العميل يختار الخدمة والشخص أو أي متاح، واليوم والموعد، وبعدها يقدر يغير أو يلغي أو يقيّم من صفحة خاصة بكود الحجز، ويتابع نقاط الولاء.', 'الموظفين عندهم تقويم لكل شخص وحجز للزيارات المباشرة وكاشير وعملاء وتذكيرات، والمالك يدير الخدمات والفريق وأكواد الخصم والتقييمات. فيه صلاحيتين: المالك والاستقبال.', 'لو ربطت حساباتك: العميل يقدر يدفع عربون أونلاين من صفحة الحجز (اختياري، والحجز مبيتلغيش لو مدفعش)، والعربون بيتخصم في الكاشير، ولو الحجز اتلغى بيترد، ولو العميل مجاش بيفضل. وتذكير الموعد بيتبعت على الواتساب لوحده قبلها بساعات. ولما الزيارة تتدفع في الكاشير بيطلع إيصال إلكتروني واحد لمصلحة الضرائب بالمبلغ كله (العربون جواه)، والبقشيش مش عليه. الصالون مفيهوش مرتجعات، فمفيش إيصال مرتجع.'],
                'list' => ['صفحة رئيسية ومعالج حجز', 'تقويم لكل موظف مع أوقات العمل والراحة والإجازات', 'حجز الزيارات المباشرة والتليفون', 'كاشير: ولاء وبقشيش وأكواد خصم وإيصال 80 مم', 'عربون أونلاين اختياري بيترد لو الحجز اتلغى', 'العملاء وتذكيرات الواتساب والرد بـ 1 أو 2', 'إيصال إلكتروني (ETA) بكود QR على إيصال الكاشير', 'منع الحجز المزدوج باختبار تزامن', 'الخدمات والتقييمات ولوحة المؤشرات'],
                'faq' => [['q' => 'ممكن العميل يختار أي موظف متاح؟', 'a' => 'أيوه، يختار شخص معين أو "أي متاح".'], ['q' => 'بيمنع الحجز المزدوج؟', 'a' => 'أيوه، وفيه اختبار تزامن بيجرب 8 عمليات في نفس الوقت.'], ['q' => 'العربون بيرجع لو الحجز اتلغى؟', 'a' => 'أيوه، الحجز الملغي بيظهر عليه "رد العربون" والمالك بيرده بضغطة. العميل اللي مجاش بيفضل عربونه، وبيظهر في لوحة المؤشرات لوحده بره الإيراد. العربون اختياري، ونسبته بتحددها انت (أو تقفله).'], ['q' => 'بيبعت تذكير بالموعد على الواتساب لوحده؟', 'a' => 'أيوه، لو ربطت حساب واتساب Business بتاعك: كل ساعة بيبعت تذكير للمواعيد اللي جاية، والعميل يرد بـ 1 للتأكيد أو 2 عشان ياخد رابط يغير منه الموعد. من غير حساب، صفحة التذكيرات بتفتح رسالة جاهزة على واتساب. الربط متجرب على ردود تجريبية بس، مش على خوادم Meta.'], SystemsCatalogOnline::etaFaq('ar')],
                'included' => SystemsCatalogOnline::included('ar', ['payments', 'whatsapp', 'eta']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'للإيصالات الإلكترونية وتذكيرات الواتساب ومتابعة الدفع'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('ar', 'عربون الحجز أو جزء من سعره'),
                'cta_title' => 'جاهز تنظم صالونك؟',
            ],
            'en' => [
                'kicker' => 'Salons, barber shops and spas', 'headline' => 'Salon bookings with no overlap, and a schedule for every person.',
                'subheadline' => 'The customer picks the service, the person (or anyone available), the day and the time, and can pay a deposit online. The system prevents double booking, and the cashier handles loyalty, tips and the deposit.',
                'who' => 'Owners of salons, barber shops and spas who want online booking and a schedule per staff member.',
                'problems_title' => 'Why do salon bookings overlap?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'Booking by phone', 'body' => 'Appointments overlap or are forgotten.'],
                    ['icon' => 'users', 'title' => 'Each person schedule', 'body' => 'No idea who is free and when.'],
                    ['icon' => 'message', 'title' => 'Customers forget', 'body' => 'No reminder for the appointment.'],
                ],
                'showcase_title' => 'From booking to the cashier', 'showcase' => [
                    ['title' => 'Booking in steps', 'body' => 'Service, then the person or anyone available, then day and time, then confirm.'],
                    ['title' => 'Calendar per person', 'body' => 'A schedule for each person with working hours, breaks and days off.'],
                    ['title' => 'Cashier', 'body' => 'Loyalty, tips, promo codes and the deposit taken off, with an 80 mm receipt carrying the e-receipt QR code.'],
                ],
                'pillars_title' => 'Four things that organize the salon',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'Online booking', 'body' => 'A booking page, and a page for the customer to move, cancel or rate.'],
                    ['icon' => 'users', 'title' => 'The team', 'body' => 'Working hours, breaks and days off for each person.'],
                    ['icon' => 'shield', 'title' => 'No double booking', 'body' => 'An availability engine tested with a race of simultaneous requests.'],
                    ['icon' => 'message', 'title' => 'Reminders', 'body' => 'A WhatsApp reminder before the appointment; the customer replies 1 to confirm or 2 to change.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the salon system',
                    'before' => [['b' => 'Booking', 't' => 'Calls and overlaps.'], ['b' => 'Schedule', 't' => 'An appointment book.'], ['b' => 'Customers', 't' => 'They forget their appointments.']],
                    'after' => [['b' => 'Booking', 't' => 'Online with no overlap.'], ['b' => 'Schedule', 't' => 'A calendar per person.'], ['b' => 'Customers', 't' => 'A WhatsApp reminder.']]],
                'metrics' => [['value' => '5', 'label' => 'Steps in the booking wizard'], ['value' => '80mm', 'label' => 'Thermal receipt'], ['value' => '166', 'label' => 'Automated tests in the code, plus browser tests']],
                'paragraphs' => ['The system is a salon booking website with a staff dashboard. The customer picks the service, the person or anyone available, the day and the time, and can later move, cancel or rate from a page tied to the booking code, and follow loyalty points.', 'Staff have a calendar per person, walk-in booking, a cashier, customers and reminders, and the owner manages services, the team, promo codes and reviews. It has two roles: owner and reception.', 'If you connect your accounts: the customer can pay a deposit online from the booking page (optional, and an unpaid deposit never cancels the booking). The cashier takes it off the bill, a cancelled booking gets it back, and a no-show keeps it. Appointment reminders go out on WhatsApp by themselves a few hours before. When the visit is paid at the cashier, one e-receipt goes to the tax authority for the whole total (deposit included), without the tip. The salon has no refunds, so there are no return receipts.'],
                'list' => ['Home page and a booking wizard', 'A calendar per person with working hours, breaks and days off', 'Walk-in and phone booking', 'Cashier: loyalty, tips, promo codes and an 80 mm receipt', 'Optional online deposit, refunded if the booking is cancelled', 'Customers and WhatsApp reminders with 1 or 2 replies', 'ETA e-receipt with a QR code on the cashier receipt', 'Double-booking protection with a concurrency test', 'Services, reviews and a dashboard'],
                'faq' => [['q' => 'Can the customer choose any available person?', 'a' => 'Yes. They can pick a specific person or "anyone available".'], ['q' => 'Does it prevent double booking?', 'a' => 'Yes, and there is a concurrency test that tries 8 simultaneous requests.'], ['q' => 'Is the deposit returned if the booking is cancelled?', 'a' => 'Yes. A cancelled booking shows "refund the deposit" and the owner refunds it in one click. A no-show keeps the deposit, and the dashboard shows it on its own, outside revenue. The deposit is optional, and you set its share (or turn it off).'], ['q' => 'Does it send WhatsApp appointment reminders by itself?', 'a' => 'Yes, once you connect your own WhatsApp Business account: every hour it sends a reminder for the coming appointments, and the customer replies 1 to confirm or 2 to get a link to change the time. Without an account, the reminders page opens a ready WhatsApp message. The connection is tested against simulated answers only, not against Meta\'s servers.'], SystemsCatalogOnline::etaFaq('en')],
                'included' => SystemsCatalogOnline::included('en', ['payments', 'whatsapp', 'eta']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for e-receipts, WhatsApp reminders and payment checks'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('en', 'a deposit or part of the booking price'),
                'cta_title' => 'Ready to organize your salon?',
            ],
        ];
    }
}
