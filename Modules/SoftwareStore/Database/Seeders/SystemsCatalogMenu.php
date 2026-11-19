<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * QR menu platform (SaaS) for restaurants. Same shape as the systems in SystemsCatalog, but built with
 * Node.js and MySQL, so it brings its own stack, hosting text, install answer and language answer.
 * The facts come from the system's SETUP.md, release notes, schema and test suite (49 tests).
 * Limits are stated on purpose: Arabic only, no online payment gateway yet, the guest pays at the restaurant,
 * needs a VPS (not cPanel), no demo data, the plan prices inside are only a starting point.
 */
class SystemsCatalogMenu
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'qr-menu-platform', 'stack' => 'Node.js', 'demo_data' => false, 'colors' => ['accent' => '#ea580c', 'ink' => '#1f1208'],
            'name_en' => 'QR Menu Platform for Restaurants', 'name_ar' => 'منصة المنيو الرقمي للمطاعم',
            'tagline_en' => 'Your own QR menu platform: restaurants sign up, build their menu, take table orders and pay you a monthly subscription.',
            'tagline_ar' => 'منصة منيو QR خاصة بيك: المطاعم بتسجل وتبني المنيو وتستلم طلبات الترابيزات وتدفعلك اشتراك شهري.',
            'ar' => self::arabic(),
            'en' => self::english(),
        ];
    }

    private static function arabic(): array
    {
        return [
            'kicker' => 'منصة اشتراكات للمطاعم والكافيهات', 'headline' => 'ابدأ منصة منيو رقمي وخلي المطاعم تدفعلك كل شهر.',
            'subheadline' => 'المطعم بيسجل بنفسه ويبني المنيو ويطبع QR لكل ترابيزة. الزبون بيطلب من موبايله، والطلب بيظهر على شاشة المطبخ في نفس اللحظة. وانت بتتحكم في الاشتراكات من لوحة الإدارة.',
            'who' => 'اللي عايز يبدأ مشروع منيو رقمي بالاشتراك للمطاعم والكافيهات، أو سلسلة مطاعم عايزة منصتها الخاصة.',
            'problems_title' => 'ليه المنيو الورق والطلب بالجرسون بيتعبوا المطعم؟',
            'problems' => [
                ['icon' => 'file', 'title' => 'منيو ورق بيتغير', 'body' => 'كل تغيير في سعر أو صنف محتاج طباعة جديدة، والصنف اللي خلص بيفضل مكتوب.'],
                ['icon' => 'clock', 'title' => 'زبون مستني الجرسون', 'body' => 'الزبون بيستنى حد ياخد الطلب، والطلب بيتنقل للمطبخ بالكلام وبيتلخبط.'],
                ['icon' => 'calculator', 'title' => 'مفيش أرقام واضحة', 'body' => 'صاحب المطعم مش عارف باع كام النهارده ولا إيه أكتر صنف بيتطلب.'],
            ],
            'showcase_title' => 'الزبون والمطبخ وصاحب المطعم وانت', 'showcase' => [
                ['title' => 'منيو الزبون', 'body' => 'الزبون بيمسح QR الترابيزة، يختار الأصناف ويبعت الطلب من غير تطبيق ولا تسجيل، ويتابع حالة طلبه لحظة بلحظة.'],
                ['title' => 'شاشة المطبخ', 'body' => 'الطلبات الجديدة بتظهر لحظيًا بصوت تنبيه، والمطبخ بيحول الطلب من جديد لتحضير لجاهز لتم التقديم.'],
                ['title' => 'لوحة مدير المنصة', 'body' => 'كل المطاعم، تسجيل دفعة، تغيير الباقة، إيقاف وتفعيل، كلمة سر جديدة لصاحب المطعم، والباقات والإعدادات.'],
            ],
            'pillars_title' => 'أربع حاجات بتخلي المنصة مشروع بيكسب',
            'pillars' => [
                ['icon' => 'users', 'title' => 'تسجيل ذاتي وفترة تجريبية', 'body' => 'المطعم بيسجل بنفسه وياخد فترة تجريبية انت بتحدد عدد أيامها، وبعدها لازم يشترك.'],
                ['icon' => 'calculator', 'title' => 'باقات بحدود', 'body' => 'باقتين بأسعار وعدد فروع وترابيزات بتتعدل من لوحة الإدارة.'],
                ['icon' => 'shield', 'title' => 'فصل كامل بين المطاعم', 'body' => 'كل مطعم بيشوف بياناته بس، مع تحديد محاولات الدخول وحماية من الإغراق بالطلبات.'],
                ['icon' => 'clock', 'title' => 'عدة فروع', 'body' => 'حسب الباقة، كل فرع بمنيو وترابيزات وطلبات وتقارير لوحده.'],
            ],
            'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد المنصة', 'before_title' => 'المنيو الورق', 'after_title' => 'مع المنيو الرقمي',
                'before' => [['b' => 'المنيو', 't' => 'طباعة جديدة مع كل تغيير.'], ['b' => 'الطلب', 't' => 'جرسون وورقة وكلام للمطبخ.'], ['b' => 'المبيعات', 't' => 'مفيش تقرير.']],
                'after' => [['b' => 'المنيو', 't' => 'تعديل السعر أو إخفاء الصنف في ثانية.'], ['b' => 'الطلب', 't' => 'من موبايل الزبون لشاشة المطبخ مباشرة.'], ['b' => 'المبيعات', 't' => 'تقرير يومي وتصدير للإكسل.']]],
            'metrics' => [['value' => '3', 'label' => 'واجهات: الزبون والمطعم ومدير المنصة'], ['value' => '2', 'label' => 'باقات اشتراك بتتعدل'], ['value' => '49', 'label' => 'اختبار آلي في الكود']],
            'paragraphs' => ['المنصة بتشغل مطاعم كتير على سيرفر واحد. كل مطعم بيسجل بنفسه ويضيف فروعه، وكل فرع ليه منيو بأقسام وأصناف وصور وأسعار، وترابيزات بكود QR لكل واحدة، وشاشة مطبخ وطلبات وتقارير.', 'الاشتراك يدوي: لما المطعم يدفعلك بفودافون كاش أو إنستا باي، بتسجل الدفعة من لوحة الإدارة والاشتراك بيتمدد. لما الاشتراك يخلص، المنيو والطلبات بيقفوا لحد التجديد وبيانات المطعم بتفضل محفوظة. ربط بوابة دفع ممكن في باقة التعديلات.'],
            'list' => ['تسجيل المطاعم بنفسها مع فترة تجريبية', 'منيو بالأقسام والأصناف والصور والأسعار وإخفاء الصنف لما يخلص', 'ترابيزات بكود QR لكل واحدة، وطباعة كل الأكواد مرة واحدة', 'طلب من موبايل الزبون من غير تطبيق ومتابعة حالة الطلب', 'شاشة مطبخ لحظية بصوت تنبيه', 'صفحة طلبات وتقرير يومي مع تصدير للإكسل وطباعة', 'عدة فروع للمطعم حسب الباقة', 'لوحة مدير المنصة للمطاعم والدفعات والباقات والإعدادات', 'ملفات تشغيل nginx و systemd ونسخ احتياطي يومي جاهز', 'دليل استخدام بالصور ونسخة PDF'],
            'faq' => [
                ['q' => 'الزبون بيدفع أونلاين من المنيو؟', 'a' => 'لا. الزبون بيبعت الطلب من موبايله وبيدفع في المطعم زي العادي.'],
                ['q' => 'المطاعم بتدفعلي الاشتراك إزاي؟', 'a' => 'بالطريقة اللي انت تكتبها في الإعدادات (فودافون كاش أو إنستا باي مثلًا)، وبعدها بتسجل الدفعة من لوحة الإدارة. مفيش بوابة دفع أونلاين في النسخة دي.'],
                ['q' => 'أسعار الباقات ثابتة؟', 'a' => 'لا. الأسعار اللي جوه النظام (149 و399 جنيه في الشهر) أرقام مبدئية، وانت بتغيرها مع عدد الفروع والترابيزات من لوحة الإدارة.'],
            ],
            'cta_title' => 'جاهز تبدأ منصتك؟',
            'included' => ['واجهة عربية بالكامل من اليمين لليسار، على الموبايل والتابلت والكمبيوتر', 'اشتراك المطاعم بيتسجل يدوي (من غير بوابة دفع)'],
            'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا. لا الزبون بيدفع أونلاين ولا المطعم بيدفع الاشتراك أونلاين. الدفعات بتتسجل يدوي، وربط Kashier أو Paymob ممكن في باقة التعديلات.'],
            'need_body' => 'سيرفر لينكس VPS (حوالي 5 دولار في الشهر) عليه Node.js 20 أو أحدث وقاعدة بيانات MySQL 8، ودومين بشهادة HTTPS. مش بيشتغل على استضافة cPanel العادية.',
            'install_faq' => ['q' => 'التركيب صعب؟', 'a' => 'الخطوات مكتوبة في دليل مرفق: إنشاء قاعدة البيانات، ملف الإعدادات، تنزيل المكتبات، وتشغيله بملفات systemd و nginx الجاهزة. الجداول بتتعمل لوحدها أول تشغيل.'],
            'language_faq' => ['q' => 'النظام بيدعم اللغتين؟', 'a' => 'لا، الواجهة عربي بس من اليمين لليسار. إضافة الإنجليزي ممكنة في باقة التعديلات.'],
        ];
    }

    private static function english(): array
    {
        return [
            'kicker' => 'A subscription platform for restaurants and cafes', 'headline' => 'Start a digital menu platform and let restaurants pay you every month.',
            'subheadline' => 'A restaurant signs up by itself, builds its menu and prints a QR code for each table. Guests order from their phones and the order shows on the kitchen screen at once. You control the subscriptions from the admin panel.',
            'who' => 'Anyone who wants to start a digital menu business with monthly subscriptions for restaurants and cafes, or a restaurant chain that wants its own platform.',
            'problems_title' => 'Why do paper menus and waiter orders tire a restaurant?',
            'problems' => [
                ['icon' => 'file', 'title' => 'Paper menus that change', 'body' => 'Every price or item change needs a new print, and sold-out items stay on the page.'],
                ['icon' => 'clock', 'title' => 'Guests wait for the waiter', 'body' => 'Guests wait for someone to take the order, and it reaches the kitchen by word of mouth and gets mixed up.'],
                ['icon' => 'calculator', 'title' => 'No clear numbers', 'body' => 'The owner does not know today\'s sales or the most ordered item.'],
            ],
            'showcase_title' => 'The guest, the kitchen, the owner and you', 'showcase' => [
                ['title' => 'The guest menu', 'body' => 'The guest scans the table QR, picks items and sends the order with no app and no sign-up, then follows the order status live.'],
                ['title' => 'The kitchen screen', 'body' => 'New orders appear live with a sound alert, and the kitchen moves each order from new to preparing to ready to served.'],
                ['title' => 'The platform admin panel', 'body' => 'All restaurants, record a payment, change the plan, suspend or activate, a new password for an owner, plans and settings.'],
            ],
            'pillars_title' => 'Four things that make the platform a business',
            'pillars' => [
                ['icon' => 'users', 'title' => 'Self sign-up and a free trial', 'body' => 'A restaurant signs up by itself and gets a trial whose length you set, then it must subscribe.'],
                ['icon' => 'calculator', 'title' => 'Plans with limits', 'body' => 'Two plans with prices, branch limits and table limits that you edit from the admin panel.'],
                ['icon' => 'shield', 'title' => 'Restaurants kept apart', 'body' => 'Each restaurant sees only its own data, with login attempt limits and protection against order flooding.'],
                ['icon' => 'clock', 'title' => 'Many branches', 'body' => 'Depending on the plan, each branch has its own menu, tables, orders and reports.'],
            ],
            'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the platform', 'before_title' => 'The paper menu', 'after_title' => 'With the digital menu',
                'before' => [['b' => 'Menu', 't' => 'A new print with every change.'], ['b' => 'Ordering', 't' => 'A waiter, a paper and words to the kitchen.'], ['b' => 'Sales', 't' => 'No report.']],
                'after' => [['b' => 'Menu', 't' => 'Change a price or hide an item in a second.'], ['b' => 'Ordering', 't' => 'From the guest\'s phone straight to the kitchen screen.'], ['b' => 'Sales', 't' => 'A daily report and export to Excel.']]],
            'metrics' => [['value' => '3', 'label' => 'Screens: guest, restaurant and platform admin'], ['value' => '2', 'label' => 'Subscription plans you can edit'], ['value' => '49', 'label' => 'Automated tests in the code']],
            'paragraphs' => ['The platform runs many restaurants on one server. Each restaurant signs up by itself and adds its branches. Each branch has a menu with sections, items, photos and prices, tables with a QR code each, a kitchen screen, orders and reports.', 'Subscriptions are manual: when a restaurant pays you by Vodafone Cash or InstaPay, you record the payment in the admin panel and the subscription is extended. When a subscription ends, the menu and orders stop until renewal and the restaurant\'s data is kept. Connecting a payment gateway is possible in the plan with changes.'],
            'list' => ['Restaurants sign up by themselves with a free trial', 'A menu with sections, items, photos and prices, and hiding sold-out items', 'Tables with a QR code each, and printing all codes at once', 'Ordering from the guest\'s phone with no app, and live order status', 'A live kitchen screen with a sound alert', 'An orders page and a daily report with Excel export and printing', 'Many branches per restaurant, depending on the plan', 'A platform admin panel for restaurants, payments, plans and settings', 'Ready nginx and systemd files and a daily backup script', 'A user guide with pictures and a PDF copy'],
            'faq' => [
                ['q' => 'Does the guest pay online from the menu?', 'a' => 'No. The guest sends the order from their phone and pays at the restaurant as usual.'],
                ['q' => 'How do restaurants pay me the subscription?', 'a' => 'By the method you write in the settings (Vodafone Cash or InstaPay, for example), then you record the payment in the admin panel. There is no online payment gateway in this version.'],
                ['q' => 'Are the plan prices fixed?', 'a' => 'No. The prices inside the system (149 and 399 EGP a month) are only a starting point. You change them, with the branch and table limits, from the admin panel.'],
            ],
            'cta_title' => 'Ready to start your platform?',
            'included' => ['A fully Arabic right-to-left interface for phone, tablet and computer', 'Restaurant subscriptions are recorded by hand (no payment gateway)'],
            'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => 'No. Guests do not pay online and restaurants do not pay the subscription online. Payments are recorded by hand, and connecting Kashier or Paymob is possible in the plan with changes.'],
            'need_body' => 'A Linux VPS (about 5 dollars a month) with Node.js 20 or newer and a MySQL 8 database, plus a domain with HTTPS. It does not run on normal cPanel shared hosting.',
            'install_faq' => ['q' => 'Is it hard to install?', 'a' => 'The steps are in an included guide: create the database, fill the settings file, install the packages, and run it with the ready systemd and nginx files. The tables are created by themselves on first start.'],
            'language_faq' => ['q' => 'Does it support both languages?', 'a' => 'No. The interface is Arabic only, right to left. Adding English is possible in the plan with changes.'],
        ];
    }
}
