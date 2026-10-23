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

        return [
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
            'plans' => self::plans(),
            'theme' => [
                'colors' => $system['colors'],
                'sections' => ['problems', 'showcase', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery', 'plans'],
            ],
            'landing' => $landing,
        ];
    }

    private static function plans(): array
    {
        return [
            ['key' => 'source', 'name' => 'Source code', 'name_ar' => 'الكود المصدري', 'price' => self::SOURCE_PRICE,
                'description' => 'Full Laravel source code, install guide and demo data, as it is. No modifications. You host it yourself.',
                'description_ar' => 'كود Laravel كامل مع دليل التركيب وبيانات تجريبية، زي ما هو من غير أي تعديلات. أنت اللي بتستضيفه.'],
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
                'subheadline' => 'العميل يحجز أونلاين في 3 خطوات ويتابع عربيته برقم اللوحة. وانت تشوف لوحة الورشة لحظة بلحظة وتطلع أوامر الشغل والإيصالات.',
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
                    ['title' => 'أوامر شغل وإيصالات', 'body' => 'تقدير وإيصال بطباعة حرارية 80 مم أو A4.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم الورشة',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'حجز أونلاين', 'body' => 'صفحة حجز وتتبع بالعربية برقم اللوحة والتليفون.'],
                    ['icon' => 'wrench', 'title' => 'أوامر الشغل', 'body' => 'تقديرات وإيصالات وفنيين ولوحة حية.'],
                    ['icon' => 'box', 'title' => 'قطع الغيار', 'body' => 'مخزون القطع وتنبيه النقص.'],
                    ['icon' => 'message', 'title' => 'تذكيرات الصيانة', 'body' => 'روابط واتساب جاهزة لتذكير العملاء.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الورشة',
                    'before' => [['b' => 'الحجز', 't' => 'مكالمات وتداخل مواعيد.'], ['b' => 'الشغل', 't' => 'أوراق وتقديرات ضايعة.'], ['b' => 'العملاء', 't' => 'صيانة منسية.']],
                    'after' => [['b' => 'الحجز', 't' => 'أونلاين على حسب الأماكن الفاضية.'], ['b' => 'الشغل', 't' => 'أوامر شغل وإيصالات مطبوعة.'], ['b' => 'العملاء', 't' => 'تذكير بالواتساب.']]],
                'metrics' => [['value' => '3', 'label' => 'خطوات لحجز العميل'], ['value' => '7s', 'label' => 'تحديث لوحة الورشة'], ['value' => '68', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام موقع للورشة مع نظام إدارة كامل. العميل بيحجز خدمة ويختار اليوم والموعد، والنظام بيحسب المتاح من عدد أماكن الورشة (الأماكن) المتاحة.', 'جوه النظام: لوحة حية للورشة، وأوامر شغل بتقديرات وإيصالات، والعملاء والسيارات، وتذكيرات الصيانة بروابط واتساب، والمخزون، والفنيين، وأكواد الخصم. فيه صلاحيتين: المالك وموظف الاستقبال.'],
                'list' => ['صفحة رئيسية وصفحة حجز من 3 خطوات', 'تتبع السيارة برقم اللوحة والتليفون', 'لوحة ورشة حية', 'أوامر شغل وتقديرات وإيصالات 80 مم وA4', 'العملاء والسيارات وتذكيرات الصيانة', 'قطع الغيار والمخزون', 'الفنيين وأكواد الخصم ولوحة المؤشرات'],
                'faq' => [['q' => 'كام نوع مستخدم في النظام؟', 'a' => 'اتنين: المالك وموظف الاستقبال.'], ['q' => 'بيطبع إيصالات؟', 'a' => 'أيوه، بطابعة حرارية 80 مم أو على ورق A4.']],
                'cta_title' => 'جاهز تنظم ورشتك؟',
            ],
            'en' => [
                'kicker' => 'Car workshops and car washes', 'headline' => 'Your workshop on one screen, from booking to handing the car back.',
                'subheadline' => 'Customers book online in 3 steps and follow their car by plate number. You see the live workshop board and print job cards and receipts.',
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
                    ['title' => 'Job cards and receipts', 'body' => 'Estimate and receipt on an 80 mm thermal printer or A4.'],
                ],
                'pillars_title' => 'Four things that organize the workshop',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'Online booking', 'body' => 'A booking and tracking page by plate number and phone.'],
                    ['icon' => 'wrench', 'title' => 'Job cards', 'body' => 'Estimates, receipts, technicians and a live board.'],
                    ['icon' => 'box', 'title' => 'Spare parts', 'body' => 'Parts stock and low-stock alerts.'],
                    ['icon' => 'message', 'title' => 'Service reminders', 'body' => 'Ready WhatsApp links to remind customers.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the workshop system',
                    'before' => [['b' => 'Booking', 't' => 'Calls and overlapping times.'], ['b' => 'Work', 't' => 'Lost papers and estimates.'], ['b' => 'Customers', 't' => 'Forgotten services.']],
                    'after' => [['b' => 'Booking', 't' => 'Online, based on free bays.'], ['b' => 'Work', 't' => 'Job cards and printed receipts.'], ['b' => 'Customers', 't' => 'WhatsApp reminders.']]],
                'metrics' => [['value' => '3', 'label' => 'Steps for a customer to book'], ['value' => '7s', 'label' => 'Workshop board refresh'], ['value' => '68', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system is a website for the workshop with a full management system. Customers book a service and choose the day and time, and the system counts what is free from the number of workshop bays.', 'Inside: a live workshop board, job cards with estimates and receipts, customers and cars, service reminders with WhatsApp links, stock, technicians and promo codes. It has two roles: owner and reception.'],
                'list' => ['Home page and a 3-step booking page', 'Track the car by plate and phone', 'Live workshop board', 'Job cards, estimates and 80 mm and A4 receipts', 'Customers, cars and service reminders', 'Spare parts and stock', 'Technicians, promo codes and a dashboard'],
                'faq' => [['q' => 'How many user types are there?', 'a' => 'Two: owner and reception staff.'], ['q' => 'Does it print receipts?', 'a' => 'Yes, on an 80 mm thermal printer or on A4 paper.']],
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
                'subheadline' => 'المريض يحجز مع الدكتور واليوم والموعد، والاستقبال يدير الطابور، والدكتور يشوف مرضاه بس ويطبع الروشتة.',
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
                    ['icon' => 'users', 'title' => 'المرضى والفواتير', 'body' => 'ملفات المرضى والفواتير وتذكيرات المتابعة.'],
                    ['icon' => 'file', 'title' => 'ملف الدكتور', 'body' => 'كل دكتور يشوف مرضاه بس، مع سجل الزيارات والروشتات.'],
                    ['icon' => 'shield', 'title' => 'سجل المراجعة', 'body' => 'تعرف مين شاف إيه وفين.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام العيادة',
                    'before' => [['b' => 'الحجز', 't' => 'تليفونات وطابور غير منظم.'], ['b' => 'الملفات', 't' => 'ورق مبعثر.'], ['b' => 'الخصوصية', 't' => 'مفيش سجل مراجعة.']],
                    'after' => [['b' => 'الحجز', 't' => 'أونلاين وطابور منظم.'], ['b' => 'الملفات', 't' => 'ملف لكل مريض وروشتة مطبوعة.'], ['b' => 'الخصوصية', 't' => 'سجل مراجعة بمين شاف إيه.']]],
                'metrics' => [['value' => '3', 'label' => 'أنواع مستخدمين: المالك والاستقبال والدكتور'], ['value' => 'A5', 'label' => 'روشتة جاهزة للطباعة'], ['value' => '54', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام موقع للعيادة ونظام حجز وإدارة. المريض يحجز مع الدكتور المناسب ويتابع موعده من صفحة خاصة، والاستقبال يدير الطابور والحجز المباشر والفواتير، والدكتور يسجل الزيارة والأدوية والإجراءات ويطبع روشتة.', 'المالك عنده لوحة مؤشرات وقايمة أسعار وتقييمات وسجل بمين شاف إيه، وكل دكتور بيشوف مرضاه بس.'],
                'list' => ['صفحة رئيسية وحجز بالدكتور واليوم والموعد', 'صفحة "موعدي" برابط خاص ورقم التليفون', 'طابور الاستقبال وحجز الزيارات المباشرة', 'المرضى والفواتير وتذكيرات المتابعة', 'سجل الزيارات والأدوية والإجراءات وروشتة A5', 'قايمة الأسعار والتقييمات ولوحة المؤشرات', 'سجل مراجعة بمين شاف إيه'],
                'faq' => [['q' => 'الدكتور يشوف كل المرضى؟', 'a' => 'لا، كل دكتور يشوف مرضاه بس.'], ['q' => 'النظام بيحمي بيانات المرضى؟', 'a' => 'فيه سجل مراجعة بمين شاف إيه. مفيش ادعاء بشهادات امتثال، فراجع القوانين الطبية في بلدك قبل الاستخدام.']],
                'cta_title' => 'جاهز تنظم عيادتك؟',
            ],
            'en' => [
                'kicker' => 'Clinics and medical centers', 'headline' => 'Clinic appointments in order, from booking to prescription.',
                'subheadline' => 'The patient books a doctor, a day and a time, reception runs the queue, and each doctor sees only their own patients and prints the prescription.',
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
                    ['icon' => 'users', 'title' => 'Patients and billing', 'body' => 'Patient files, billing and follow-up reminders.'],
                    ['icon' => 'file', 'title' => 'The doctor file', 'body' => 'Each doctor sees only their patients, with visit and prescription records.'],
                    ['icon' => 'shield', 'title' => 'Audit log', 'body' => 'Know who looked at what and where.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the clinic system',
                    'before' => [['b' => 'Booking', 't' => 'Phone calls and a messy queue.'], ['b' => 'Files', 't' => 'Scattered paper.'], ['b' => 'Privacy', 't' => 'No audit log.']],
                    'after' => [['b' => 'Booking', 't' => 'Online with an organized queue.'], ['b' => 'Files', 't' => 'A file per patient and a printed prescription.'], ['b' => 'Privacy', 't' => 'An audit log of who saw what.']]],
                'metrics' => [['value' => '3', 'label' => 'User types: owner, reception and doctor'], ['value' => 'A5', 'label' => 'Prescription ready to print'], ['value' => '54', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system is a clinic website with a booking and management system. The patient books the right doctor and follows the appointment from a private page, reception runs the queue, walk-ins and billing, and the doctor records the visit, medicines and procedures and prints a prescription.', 'The owner has a dashboard, a price list, reviews and an audit log of who saw what, and every doctor sees only their own patients.'],
                'list' => ['Home page and booking by doctor, day and time', 'A "my appointment" page with a private link and phone', 'Reception queue and walk-in visits', 'Patients, billing and follow-up reminders', 'Visit records, medicines, procedures and an A5 prescription', 'Price list, reviews and a dashboard', 'An audit log of who saw what'],
                'faq' => [['q' => 'Does a doctor see all patients?', 'a' => 'No. Each doctor sees only their own patients.'], ['q' => 'Does it protect patient data?', 'a' => 'It has an audit log of who saw what. It makes no claim of compliance certificates, so check the medical data laws in your country before use.']],
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
                'subheadline' => 'موقع متحرك بقائمة طلب وسلة، وكود QR لكل ترابيزة، ولوحة طلبات حية بتنبه على الطلب الجديد.',
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
                    ['title' => 'تذكرة طلب', 'body' => 'طباعة تذكرة حرارية 80 مم.'],
                ],
                'pillars_title' => 'أربع حاجات تشغل الكافيه',
                'pillars' => [
                    ['icon' => 'phone', 'title' => 'طلب بـ QR', 'body' => 'كود لكل ترابيزة يفتح المنيو مباشرة.'],
                    ['icon' => 'send', 'title' => 'توصيل', 'body' => 'مناطق التوصيل وأسعارها.'],
                    ['icon' => 'check', 'title' => 'شاشة الكاشير', 'body' => 'طلب جديد بسرعة وأكواد خصم.'],
                    ['icon' => 'chart', 'title' => 'لوحة المالك', 'body' => 'المنيو والترابيزات والتقييمات والموظفين.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الكافيه',
                    'before' => [['b' => 'الطلب', 't' => 'شفوي وبيتنسي.'], ['b' => 'الترابيزة', 't' => 'العميل بيستنى الجرسون.'], ['b' => 'المتابعة', 't' => 'مفيش لوحة للطلبات.']],
                    'after' => [['b' => 'الطلب', 't' => 'من الموبايل أو QR.'], ['b' => 'الترابيزة', 't' => 'العميل بيطلب بنفسه.'], ['b' => 'المتابعة', 't' => 'لوحة حية بتنبه على كل طلب.']]],
                'metrics' => [['value' => '6s', 'label' => 'تحديث لوحة الطلبات'], ['value' => '80mm', 'label' => 'طباعة تذكرة حرارية'], ['value' => 'QR', 'label' => 'كود لكل ترابيزة']],
                'paragraphs' => ['النظام موقع متحرك للكافيه مع نظام طلبات. العميل يشوف المنيو ويختار الحجم والإضافات ويطلب داخل المكان أو تيك أواي أو توصيل، ويتابع طلبه بكود.', 'الموظفين عندهم لوحة طلبات حية وشاشة كاشير وطباعة تذكرة، والمالك يدير المنيو والترابيزات وأكواد QR ومناطق التوصيل والخصومات والتقييمات والموظفين.'],
                'list' => ['صفحة رئيسية متحركة وقايمة طلب بسلة', 'أحجام وإضافات للمنتجات', 'كود QR لكل ترابيزة وصفحة تتبع الطلب', 'لوحة طلبات حية مع تنبيه صوتي', 'شاشة كاشير وطباعة تذكرة 80 مم', 'مناطق التوصيل وأكواد الخصم', 'المنيو والتقييمات وحسابات الموظفين'],
                'faq' => [['q' => 'في توصيل؟', 'a' => 'أيوه، النظام فيه مناطق توصيل بأسعارها.'], ['q' => 'الطلب من الترابيزة إزاي؟', 'a' => 'كل ترابيزة ليها كود QR بيفتح المنيو مباشرة.']],
                'cta_title' => 'جاهز تستقبل طلباتك أونلاين؟',
            ],
            'en' => [
                'kicker' => 'Coffee shops and small restaurants', 'headline' => 'Your coffee shop takes orders from phones and tables, and the barista sees them instantly.',
                'subheadline' => 'An animated website with a menu and cart, a QR code per table, and a live orders board that alerts you to every new order.',
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
                    ['title' => 'Order ticket', 'body' => 'Prints an 80 mm thermal ticket.'],
                ],
                'pillars_title' => 'Four things that run the shop',
                'pillars' => [
                    ['icon' => 'phone', 'title' => 'QR ordering', 'body' => 'A code per table opens the menu directly.'],
                    ['icon' => 'send', 'title' => 'Delivery', 'body' => 'Delivery areas and their prices.'],
                    ['icon' => 'check', 'title' => 'Cashier screen', 'body' => 'A quick new order and promo codes.'],
                    ['icon' => 'chart', 'title' => 'Owner panel', 'body' => 'Menu, tables, reviews and staff.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the coffee shop system',
                    'before' => [['b' => 'Ordering', 't' => 'Spoken and forgotten.'], ['b' => 'Tables', 't' => 'The customer waits for the waiter.'], ['b' => 'Tracking', 't' => 'No orders board.']],
                    'after' => [['b' => 'Ordering', 't' => 'From a phone or a QR code.'], ['b' => 'Tables', 't' => 'The customer orders alone.'], ['b' => 'Tracking', 't' => 'A live board that alerts on every order.']]],
                'metrics' => [['value' => '6s', 'label' => 'Orders board refresh'], ['value' => '80mm', 'label' => 'Thermal ticket printing'], ['value' => 'QR', 'label' => 'A code per table']],
                'paragraphs' => ['The system is an animated coffee shop website with an ordering system. Customers see the menu, choose size and extras and order dine-in, take-away or delivery, and follow their order with a code.', 'Staff have a live orders board, a cashier screen and ticket printing, and the owner manages the menu, tables and QR codes, delivery areas, promo codes, reviews and staff.'],
                'list' => ['An animated home page and an order menu with a cart', 'Sizes and extras for products', 'A QR code per table and an order tracking page', 'Live orders board with a sound alert', 'Cashier screen and 80 mm ticket printing', 'Delivery areas and promo codes', 'Menu, reviews and staff accounts'],
                'faq' => [['q' => 'Is there delivery?', 'a' => 'Yes. The system has delivery areas with their prices.'], ['q' => 'How does ordering from the table work?', 'a' => 'Every table has a QR code that opens the menu directly.']],
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
                'subheadline' => 'الحضور بسكانر باركود، والأعضاء ليهم بوابة يحجزوا منها الحصص، والتجديدات بتوصلك بروابط واتساب جاهزة.',
                'who' => 'أصحاب الجيمات والنوادي اللي عايزين يتابعوا الاشتراكات والحضور والتجديدات.',
                'problems_title' => 'ليه إدارة الجيم بتتعب؟',
                'problems' => [
                    ['icon' => 'users', 'title' => 'اشتراكات في دفاتر', 'body' => 'صعب تعرف مين اشتراكه خلص.'],
                    ['icon' => 'clock', 'title' => 'الحضور يدوي', 'body' => 'طابور عند الاستقبال وأخطاء في التسجيل.'],
                    ['icon' => 'message', 'title' => 'تجديدات بتفوت', 'body' => 'مفيش تذكير قبل ما الاشتراك ينتهي.'],
                ],
                'showcase_title' => 'من الاستقبال للتجديد', 'showcase' => [
                    ['title' => 'تسجيل الحضور', 'body' => 'بسكانر باركود USB أو بالكود أو التليفون.'],
                    ['title' => 'الاشتراكات', 'body' => 'بيع وتجديد وترقية وتقسيط وتجميد وإيصال.'],
                    ['title' => 'بوابة العضو', 'body' => 'العضو يشوف اشتراكه ويحجز ويلغي حصصه.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم الجيم',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'تسجيل الحضور', 'body' => 'سريع بباركود أو كود أو تليفون.'],
                    ['icon' => 'file', 'title' => 'الاشتراكات', 'body' => 'خطط بمدة أو باقة حصص أو يوم واحد.'],
                    ['icon' => 'users', 'title' => 'الحصص والكوتشات', 'body' => 'قايمة حضور وقايمة انتظار.'],
                    ['icon' => 'message', 'title' => 'التجديدات', 'body' => 'روابط واتساب جاهزة قبل الانتهاء.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الجيم',
                    'before' => [['b' => 'الحضور', 't' => 'تسجيل يدوي.'], ['b' => 'الاشتراكات', 't' => 'دفاتر متفرقة.'], ['b' => 'التجديد', 't' => 'بيفوت من غير تذكير.']],
                    'after' => [['b' => 'الحضور', 't' => 'بالباركود في ثواني.'], ['b' => 'الاشتراكات', 't' => 'كلها في النظام.'], ['b' => 'التجديد', 't' => 'تذكير بالواتساب.']]],
                'metrics' => [['value' => 'USB', 'label' => 'سكانر باركود للحضور'], ['value' => 'QR', 'label' => 'كارت عضوية مطبوع'], ['value' => '2', 'label' => 'صلاحيات: المالك والموظف']],
                'paragraphs' => ['النظام موقع للجيم مع نظام إدارة الاشتراكات. الزائر يسجل اهتمامه، والعضو يدخل بوابته بكوده وتليفونه عشان يشوف اشتراكه ويحجز الحصص أو يدخل قايمة الانتظار.', 'الموظفين بيسجلوا الحضور ويبيعوا ويجددوا الاشتراكات ويطبعوا كارت العضو، والمالك يدير الخطط والكوتشات وأكواد الخصم والتقييمات.'],
                'list' => ['صفحة رئيسية وتسجيل اهتمام (Lead)', 'بوابة العضو: الاشتراك والحصص وقايمة الانتظار', 'تسجيل الحضور بسكانر باركود', 'كارت عضوية مطبوع بكود QR', 'بيع وتجديد وترقية وتقسيط وتجميد الاشتراكات', 'الحصص وقايمة الحضور والانتظار', 'التجديدات بروابط واتساب والمهتمين ولوحة المؤشرات'],
                'faq' => [['q' => 'الحضور بيتسجل إزاي؟', 'a' => 'بسكانر باركود USB، أو بكتابة الكود أو رقم التليفون.'], ['q' => 'العضو يحجز بنفسه؟', 'a' => 'أيوه، من بوابة العضو بكوده وتليفونه.']],
                'cta_title' => 'جاهز تنظم جيمك؟',
            ],
            'en' => [
                'kicker' => 'Gyms and fitness clubs', 'headline' => 'Gym memberships, attendance and renewals in one place.',
                'subheadline' => 'Check-in with a barcode scanner, a member portal to book classes, and renewals that reach you as ready WhatsApp links.',
                'who' => 'Owners of gyms and clubs who want to follow memberships, attendance and renewals.',
                'problems_title' => 'Why is running a gym tiring?',
                'problems' => [
                    ['icon' => 'users', 'title' => 'Memberships in notebooks', 'body' => 'Hard to know whose membership ended.'],
                    ['icon' => 'clock', 'title' => 'Manual check-in', 'body' => 'A queue at reception and entry mistakes.'],
                    ['icon' => 'message', 'title' => 'Missed renewals', 'body' => 'No reminder before a membership ends.'],
                ],
                'showcase_title' => 'From reception to renewal', 'showcase' => [
                    ['title' => 'Check-in', 'body' => 'With a USB barcode scanner, a code or a phone number.'],
                    ['title' => 'Memberships', 'body' => 'Sell, renew, upgrade, instalments, freeze and a receipt.'],
                    ['title' => 'Member portal', 'body' => 'Members see their membership and book or cancel classes.'],
                ],
                'pillars_title' => 'Four things that organize the gym',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'Check-in', 'body' => 'Fast with a barcode, a code or a phone.'],
                    ['icon' => 'file', 'title' => 'Memberships', 'body' => 'Plans by period, class pack or day pass.'],
                    ['icon' => 'users', 'title' => 'Classes and coaches', 'body' => 'Class roster and a waitlist.'],
                    ['icon' => 'message', 'title' => 'Renewals', 'body' => 'Ready WhatsApp links before expiry.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the gym system',
                    'before' => [['b' => 'Check-in', 't' => 'Manual entry.'], ['b' => 'Memberships', 't' => 'Scattered notebooks.'], ['b' => 'Renewals', 't' => 'Missed with no reminder.']],
                    'after' => [['b' => 'Check-in', 't' => 'By barcode in seconds.'], ['b' => 'Memberships', 't' => 'All in the system.'], ['b' => 'Renewals', 't' => 'A WhatsApp reminder.']]],
                'metrics' => [['value' => 'USB', 'label' => 'Barcode scanner check-in'], ['value' => 'QR', 'label' => 'Printed member card'], ['value' => '2', 'label' => 'Roles: owner and staff']],
                'paragraphs' => ['The system is a gym website with a membership management system. Visitors leave their interest, and members sign in to their portal with their code and phone to see their membership and book classes or join the waitlist.', 'Staff record check-ins, sell and renew memberships and print member cards, and the owner manages plans, coaches, promo codes and reviews.'],
                'list' => ['Home page and an interest form (lead)', 'Member portal: membership, classes and waitlist', 'Check-in with a barcode scanner', 'Printed member card with a QR code', 'Sell, renew, upgrade, instalments and freeze memberships', 'Classes with a roster and a waitlist', 'Renewals with WhatsApp links, leads and a dashboard'],
                'faq' => [['q' => 'How is check-in recorded?', 'a' => 'With a USB barcode scanner, or by typing the code or phone number.'], ['q' => 'Can a member book alone?', 'a' => 'Yes, from the member portal with their code and phone.']],
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
                'subheadline' => 'تتبع كل جهاز بالـ IMEI، وبيع بالآجل وتابع ديون العملاء، وأدر الفروع والصيانة والمشتريات من شاشة واحدة.',
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
                    ['icon' => 'users', 'title' => 'الصلاحيات', 'body' => 'تبني أدوار الموظفين من قايمة صلاحيات.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع النظام',
                    'before' => [['b' => 'الدفاتر', 't' => 'ضياع فواتير وأرقام.'], ['b' => 'الجرد', 't' => 'يستغرق أيام.'], ['b' => 'المتابعة', 't' => 'صعب معرفة الربح الحقيقي.']],
                    'after' => [['b' => 'الفواتير', 't' => 'محفوظة لكل عملية.'], ['b' => 'الجرد', 't' => 'لحظي بالـ IMEI.'], ['b' => 'التقارير', 't' => 'لوحة محدثة باستمرار.']]],
                'metrics' => [['value' => 'IMEI', 'label' => 'تتبع كل جهاز'], ['value' => '58/80', 'label' => 'طباعة حرارية 58 مم و80 مم وA4'], ['value' => 'SaaS', 'label' => 'تجربة مجانية واشتراكات للعملاء']],
                'paragraphs' => ['النظام نقطة بيع ولوحة إدارة لمحلات الموبايل. الأجهزة بتتتبع بالـ IMEI والإكسسوارات بالكمية، وفيه فروع وتحويلات مخزون، وبيع بالآجل وديون العملاء، وصيانة بعربون، ومشتريات من الموردين، وتقارير.', 'النظام متعدد المحلات: صاحب المنصة يضيف العملاء ويسجل مدفوعات الاشتراك ويوقف العميل المتأخر، والعميل المنتهي أو الموقوف بيتمنع لكن بياناته بتفضل. فيه تسجيل ذاتي بتجربة مجانية (ممكن تقفله). التركيب على استضافة cPanel عادية من غير Redis ولا cron.'],
                'list' => ['مخزون الأجهزة بالـ IMEI والإكسسوارات بالكمية', 'فروع وتحويلات مخزون بينها', 'شاشة بيع وبيع بالآجل وديون العملاء', 'صيانة بعربون وخطوات الحالة', 'مشتريات من الموردين وتقارير', 'أدوار وصلاحيات بتبنيها بنفسك', 'طباعة حرارية 58 مم و80 مم وA4', 'طبقة اشتراكات: عملاء ومدفوعات وإيقاف'],
                'faq' => [['q' => 'ينفع أقدمه كخدمة لمحلات كتير؟', 'a' => 'أيوه، فيه طبقة متعددة المحلات: تضيف العملاء وتسجل مدفوعات الاشتراك وتوقف المتأخر.'], ['q' => 'لو اشتراك محل خلص؟', 'a' => 'بيتمنع من الدخول لكن بياناته بتفضل محفوظة.']],
                'cta_title' => 'جاهز تدير محلك بنظام واحد؟',
            ],
            'en' => [
                'kicker' => 'Mobile phone and accessory shops', 'headline' => 'Full control of sales, repairs and stock.',
                'subheadline' => 'Track every phone by IMEI, sell on credit and follow customer debts, and run branches, repairs and purchases from one screen.',
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
                    ['icon' => 'users', 'title' => 'Permissions', 'body' => 'Build staff roles from a permission list.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the system',
                    'before' => [['b' => 'Notebooks', 't' => 'Lost invoices and numbers.'], ['b' => 'Stock count', 't' => 'Takes days.'], ['b' => 'Follow-up', 't' => 'Hard to know the real profit.']],
                    'after' => [['b' => 'Invoices', 't' => 'Saved for every sale.'], ['b' => 'Stock count', 't' => 'Instant by IMEI.'], ['b' => 'Reports', 't' => 'A dashboard always up to date.']]],
                'metrics' => [['value' => 'IMEI', 'label' => 'Track every phone'], ['value' => '58/80', 'label' => '58 mm, 80 mm thermal and A4 printing'], ['value' => 'SaaS', 'label' => 'Free trial and subscriptions for clients']],
                'paragraphs' => ['The system is a point of sale and back office for mobile phone shops. Phones are tracked by IMEI and accessories by quantity, with branches and stock transfers, credit sales and customer debts, repairs with deposits, supplier purchases and reports.', 'It is multi-shop: the platform owner adds clients, records subscription payments and suspends a late client, and an expired or suspended client is blocked but keeps their data. It has self-service signup with a free trial (which can be turned off). It installs on normal cPanel hosting with no Redis or cron.'],
                'list' => ['Phone stock by IMEI and accessories by quantity', 'Branches and stock transfers between them', 'A sell screen, credit sales and customer debts', 'Repairs with a deposit and status steps', 'Supplier purchases and reports', 'Roles and permissions you build yourself', 'Thermal 58 mm, 80 mm and A4 printing', 'A subscription layer: clients, payments and suspension'],
                'faq' => [['q' => 'Can I offer it as a service to many shops?', 'a' => 'Yes. It has a multi-shop layer: you add clients, record subscription payments and suspend late ones.'], ['q' => 'What if a shop subscription ends?', 'a' => 'The shop is blocked from signing in, but its data stays saved.']],
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
                'subheadline' => 'العميل يختار الخدمة والشخص (أو أي متاح) واليوم والموعد، والنظام يمنع الحجز المزدوج، والكاشير يحسب الولاء والبقشيش.',
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
                    ['title' => 'كاشير', 'body' => 'ولاء وبقشيش وأكواد خصم وإيصال 80 مم.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم الصالون',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'حجز أونلاين', 'body' => 'صفحة حجز، وصفحة للعميل يغير أو يلغي أو يقيّم.'],
                    ['icon' => 'users', 'title' => 'الفريق', 'body' => 'أوقات العمل والراحة والإجازات لكل شخص.'],
                    ['icon' => 'shield', 'title' => 'منع الحجز المزدوج', 'body' => 'محرك مواعيد متاح اتختبر بسباق عمليات متزامنة.'],
                    ['icon' => 'message', 'title' => 'التذكيرات', 'body' => 'روابط واتساب جاهزة لتذكير العملاء.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الصالون',
                    'before' => [['b' => 'الحجز', 't' => 'تليفونات وتداخل.'], ['b' => 'الجدول', 't' => 'دفتر مواعيد.'], ['b' => 'العملاء', 't' => 'ينسوا مواعيدهم.']],
                    'after' => [['b' => 'الحجز', 't' => 'أونلاين بدون تداخل.'], ['b' => 'الجدول', 't' => 'تقويم لكل موظف.'], ['b' => 'العملاء', 't' => 'تذكير بالواتساب.']]],
                'metrics' => [['value' => '5', 'label' => 'خطوات في معالج الحجز'], ['value' => '80mm', 'label' => 'إيصال حراري'], ['value' => '2', 'label' => 'صلاحيات: المالك والاستقبال']],
                'paragraphs' => ['النظام موقع حجز للصالون مع لوحة للموظفين. العميل يختار الخدمة والشخص أو أي متاح، واليوم والموعد، وبعدها يقدر يغير أو يلغي أو يقيّم من صفحة خاصة بكود الحجز، ويتابع نقاط الولاء.', 'الموظفين عندهم تقويم لكل شخص وحجز للزيارات المباشرة وكاشير وعملاء وتذكيرات، والمالك يدير الخدمات والفريق وأكواد الخصم والتقييمات.'],
                'list' => ['صفحة رئيسية ومعالج حجز', 'تقويم لكل موظف مع أوقات العمل والراحة والإجازات', 'حجز الزيارات المباشرة والتليفون', 'كاشير: ولاء وبقشيش وأكواد خصم وإيصال 80 مم', 'العملاء وتذكيرات الواتساب', 'منع الحجز المزدوج باختبار تزامن', 'الخدمات والتقييمات ولوحة المؤشرات'],
                'faq' => [['q' => 'ممكن العميل يختار أي موظف متاح؟', 'a' => 'أيوه، يختار شخص معين أو "أي متاح".'], ['q' => 'بيمنع الحجز المزدوج؟', 'a' => 'أيوه، وفيه اختبار تزامن بيجرب 8 عمليات في نفس الوقت.']],
                'cta_title' => 'جاهز تنظم صالونك؟',
            ],
            'en' => [
                'kicker' => 'Salons, barber shops and spas', 'headline' => 'Salon bookings with no overlap, and a schedule for every person.',
                'subheadline' => 'The customer picks the service, the person (or anyone available), the day and the time. The system prevents double booking, and the cashier handles loyalty and tips.',
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
                    ['title' => 'Cashier', 'body' => 'Loyalty, tips, promo codes and an 80 mm receipt.'],
                ],
                'pillars_title' => 'Four things that organize the salon',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'Online booking', 'body' => 'A booking page, and a page for the customer to move, cancel or rate.'],
                    ['icon' => 'users', 'title' => 'The team', 'body' => 'Working hours, breaks and days off for each person.'],
                    ['icon' => 'shield', 'title' => 'No double booking', 'body' => 'An availability engine tested with a race of simultaneous requests.'],
                    ['icon' => 'message', 'title' => 'Reminders', 'body' => 'Ready WhatsApp links to remind customers.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the salon system',
                    'before' => [['b' => 'Booking', 't' => 'Calls and overlaps.'], ['b' => 'Schedule', 't' => 'An appointment book.'], ['b' => 'Customers', 't' => 'They forget their appointments.']],
                    'after' => [['b' => 'Booking', 't' => 'Online with no overlap.'], ['b' => 'Schedule', 't' => 'A calendar per person.'], ['b' => 'Customers', 't' => 'A WhatsApp reminder.']]],
                'metrics' => [['value' => '5', 'label' => 'Steps in the booking wizard'], ['value' => '80mm', 'label' => 'Thermal receipt'], ['value' => '2', 'label' => 'Roles: owner and reception']],
                'paragraphs' => ['The system is a salon booking website with a staff dashboard. The customer picks the service, the person or anyone available, the day and the time, and can later move, cancel or rate from a page tied to the booking code, and follow loyalty points.', 'Staff have a calendar per person, walk-in booking, a cashier, customers and reminders, and the owner manages services, the team, promo codes and reviews.'],
                'list' => ['Home page and a booking wizard', 'A calendar per person with working hours, breaks and days off', 'Walk-in and phone booking', 'Cashier: loyalty, tips, promo codes and an 80 mm receipt', 'Customers and WhatsApp reminders', 'Double-booking protection with a concurrency test', 'Services, reviews and a dashboard'],
                'faq' => [['q' => 'Can the customer choose any available person?', 'a' => 'Yes. They can pick a specific person or "anyone available".'], ['q' => 'Does it prevent double booking?', 'a' => 'Yes, and there is a concurrency test that tries 8 simultaneous requests.']],
                'cta_title' => 'Ready to organize your salon?',
            ],
        ];
    }
}
