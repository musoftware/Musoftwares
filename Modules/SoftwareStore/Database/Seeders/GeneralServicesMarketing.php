<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Marketing and AI services: social media management, paid ads management, branding, chatbot and WhatsApp automation.
 * Plans and prices follow the marketing research of Oct 2026 (Egyptian agency rate cards where they exist).
 * The chatbot prices have no Egyptian agency evidence, so the owner must check them against real quotes.
 */
class GeneralServicesMarketing
{
    private const ONCE = ['en' => 'one payment', 'ar' => 'دفعة واحدة'];

    private const MONTHLY = ['en' => 'per month', 'ar' => 'في الشهر'];

    /** @return array<int, array<string, mixed>> */
    public static function services(): array
    {
        return [self::socialMedia(), self::paidAds(), self::branding(), self::chatbot()];
    }

    private static function socialMedia(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'social-media-management', 'colors' => ['accent' => '#db2777', 'ink' => '#2a0a1c'], 'billing' => 'monthly', 'period' => self::MONTHLY,
            'name_en' => 'Social Media Management', 'name_ar' => 'إدارة صفحات السوشيال ميديا',
            'tagline_en' => 'Monthly content, posting and replies for your Facebook, Instagram and TikTok pages.',
            'tagline_ar' => 'محتوى ونشر وردود شهرية لصفحاتك على فيسبوك وانستجرام وتيك توك.',
            'plans' => [
                $plan('starter', ['Starter', 'الأساسية'], 5000, ['One platform with a steady posting plan.', 'منصة واحدة بخطة نشر منتظمة.'],
                    ['1 platform', '8 designed posts a month', '2 sets of stories a month', 'Replies to comments and messages in business hours', 'End of month report'],
                    ['منصة واحدة', '8 بوستات مصممة في الشهر', 'مجموعتين ستوري في الشهر', 'رد على التعليقات والرسايل في ساعات العمل', 'تقرير آخر الشهر']),
                $plan('business', ['Business', 'الأعمال'], 9000, ['Facebook and Instagram with reels and a content plan.', 'فيسبوك وانستجرام مع ريلز وخطة محتوى.'],
                    ['Facebook and Instagram', '12 designed posts a month', '4 edited reels a month', 'A written content plan before the month starts', 'Replies in business hours', 'End of month report'],
                    ['فيسبوك وانستجرام', '12 بوست مصمم في الشهر', '4 ريلز بمونتاج في الشهر', 'خطة محتوى مكتوبة قبل بداية الشهر', 'رد في ساعات العمل', 'تقرير آخر الشهر']),
                $plan('premium', ['Premium', 'المتميزة'], 15000, ['Three platforms including TikTok, with a monthly strategy call.', 'ثلاث منصات منها تيك توك مع مكالمة استراتيجية شهرية.'],
                    ['Facebook, Instagram and TikTok', '16 designed posts a month', '8 edited reels a month', 'A monthly strategy call', 'Weekly short report', 'Replies in business hours'],
                    ['فيسبوك وانستجرام وتيك توك', '16 بوست مصمم في الشهر', '8 ريلز بمونتاج في الشهر', 'مكالمة استراتيجية شهرية', 'تقرير أسبوعي قصير', 'رد في ساعات العمل']),
            ],
            'en' => [
                'kicker' => 'Social media for Egyptian businesses',
                'headline' => 'Social media page management in Egypt, with a clear monthly plan.',
                'subheadline' => 'We plan, design and post your content, and answer your followers. You see the plan before the month starts and a report at the end.',
                'who' => 'Shops, clinics, restaurants and companies that want an active page but have no time or team for it.',
                'need' => 'Your logo and brand colors, photos or videos of your products or place if you have them, and admin access to your pages.',
                'problems_title' => 'Why do business pages go quiet?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'No time to post', 'body' => 'Posting stops when the owner gets busy.'],
                    ['icon' => 'message', 'title' => 'Messages wait for hours', 'body' => 'Customers who do not get a reply move to a competitor.'],
                    ['icon' => 'file', 'title' => 'No plan', 'body' => 'Random posts, no pattern, and no way to see what works.'],
                ],
                'pillars_title' => 'What we do every month',
                'pillars' => [
                    ['icon' => 'file', 'title' => 'Content plan', 'body' => 'A written plan of topics and dates, agreed with you.'],
                    ['icon' => 'check', 'title' => 'Designs and reels', 'body' => 'Posts, stories and edited reels in your brand style.'],
                    ['icon' => 'message', 'title' => 'Replies', 'body' => 'We answer comments and messages in business hours.'],
                    ['icon' => 'chart', 'title' => 'Report', 'body' => 'A simple report of what was posted and how it did.'],
                ],
                'paragraphs' => [
                    'Each plan lists how many posts and reels you get every month. We agree the plan with you in writing before the month starts, and you approve the posts before they go out if you want to.',
                    'We manage organic content. Paid ads are a separate service, and we never hold your ad money. We do not promise a number of followers or sales, because they depend on your product, your price and your market.',
                ],
                'list' => ['A written monthly content plan', 'Designed posts as listed in your plan', 'Stories and edited reels as listed in your plan', 'Posting on the agreed days', 'Replies to comments and messages in business hours', 'End of month report'],
                'excludes' => 'Not included: ad spend and ad management, photo and video shoots, paid stock photos and music licenses, and influencer fees.',
                'faq' => [
                    ['q' => 'Do you shoot photos and videos?', 'a' => 'Not in these plans. We design and edit with the material you give us. A shoot day can be quoted separately.'],
                    ['q' => 'Can I approve posts first?', 'a' => 'Yes. You can approve each post before it goes out, or let us post the agreed plan.'],
                    ['q' => 'Is there a minimum period?', 'a' => 'No. It is month by month. Social media needs months to build, so we suggest at least three, but the choice is yours.'],
                ],
                'cta_title' => 'Ready to keep your pages active every month?',
            ],
            'ar' => [
                'kicker' => 'سوشيال ميديا للأنشطة المصرية',
                'headline' => 'إدارة صفحات السوشيال ميديا في مصر بخطة شهرية واضحة.',
                'subheadline' => 'بنخطط ونصمم وننشر محتواك، ونرد على متابعينك. بتشوف الخطة قبل ما الشهر يبدأ وتاخد تقرير في آخره.',
                'who' => 'المحلات والعيادات والمطاعم والشركات اللي عايزة صفحة نشطة ومعندهاش وقت أو فريق.',
                'need' => 'اللوجو وألوان الهوية، وصور أو فيديوهات منتجاتك أو مكانك لو متاحة، وصلاحية مدير على صفحاتك.',
                'problems_title' => 'ليه صفحات الأنشطة بتسكت؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'مفيش وقت للنشر', 'body' => 'النشر بيقف لما صاحب النشاط ينشغل.'],
                    ['icon' => 'message', 'title' => 'الرسايل بتستنى ساعات', 'body' => 'العميل اللي مش بياخد رد بيروح للمنافس.'],
                    ['icon' => 'file', 'title' => 'مفيش خطة', 'body' => 'بوستات عشوائية من غير نمط، ومفيش طريقة تشوف إيه اللي نجح.'],
                ],
                'pillars_title' => 'اللي بنعمله كل شهر',
                'pillars' => [
                    ['icon' => 'file', 'title' => 'خطة محتوى', 'body' => 'خطة مكتوبة بالمواضيع والمواعيد، متفق عليها معاك.'],
                    ['icon' => 'check', 'title' => 'تصميمات وريلز', 'body' => 'بوستات وستوري وريلز بمونتاج على أسلوب هويتك.'],
                    ['icon' => 'message', 'title' => 'ردود', 'body' => 'بنرد على التعليقات والرسايل في ساعات العمل.'],
                    ['icon' => 'chart', 'title' => 'تقرير', 'body' => 'تقرير بسيط بالمنشور ونتيجته.'],
                ],
                'paragraphs' => [
                    'كل باقة بتحدد عدد البوستات والريلز كل شهر. بنتفق معاك على الخطة كتابة قبل بداية الشهر، وتقدر توافق على البوستات قبل نزولها لو حبيت.',
                    'بندير المحتوى العضوي. الإعلانات المدفوعة خدمة منفصلة، ومش بنمسك فلوس إعلاناتك. ومش بنوعد بعدد متابعين أو مبيعات، لأنها بتعتمد على منتجك وسعرك وسوقك.',
                ],
                'list' => ['خطة محتوى شهرية مكتوبة', 'بوستات مصممة حسب باقتك', 'ستوري وريلز بمونتاج حسب باقتك', 'النشر في الأيام المتفق عليها', 'رد على التعليقات والرسايل في ساعات العمل', 'تقرير آخر الشهر'],
                'excludes' => 'غير مشمول: ميزانية الإعلانات وإدارتها، وتصوير الصور والفيديو، وصور وموسيقى مدفوعة الترخيص، وأجور المؤثرين.',
                'faq' => [
                    ['q' => 'بتصوروا صور وفيديوهات؟', 'a' => 'مش في الباقات دي. بنصمم ونعمل مونتاج على المواد اللي بتديهالنا. ويوم تصوير ممكن نسعّره لوحده.'],
                    ['q' => 'أقدر أوافق على البوستات الأول؟', 'a' => 'أيوه. تقدر توافق على كل بوست قبل نزوله، أو تسيبنا ننشر الخطة المتفق عليها.'],
                    ['q' => 'في حد أدنى للمدة؟', 'a' => 'لا. شهر بشهر. السوشيال ميديا بتحتاج شهور عشان تتبني فبننصح بتلاتة على الأقل، لكن القرار ليك.'],
                ],
                'cta_title' => 'جاهز تخلي صفحاتك نشطة كل شهر؟',
            ],
        ];
    }

    private static function paidAds(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'paid-ads-management', 'colors' => ['accent' => '#ea580c', 'ink' => '#220d03'], 'billing' => 'monthly', 'period' => self::MONTHLY,
            'name_en' => 'Paid Ads Management', 'name_ar' => 'إدارة الحملات الإعلانية المدفوعة',
            'tagline_en' => 'We set up and run your Meta and Google ads for a fixed monthly fee. Ad spend is paid to the platform.',
            'tagline_ar' => 'بنجهز ونشغل إعلانات ميتا وجوجل بأتعاب شهرية ثابتة. ميزانية الإعلان بتتدفع للمنصة.',
            'plans' => [
                $plan('starter', ['Starter', 'الأساسية'], 5500, ['One platform for an ad budget up to 10,000 EGP a month.', 'منصة واحدة لميزانية إعلان حتى 10,000 جنيه في الشهر.'],
                    ['1 platform: Meta or Google', 'Ad budget up to 10,000 EGP a month', '2 campaigns', 'Pixel and conversion tracking setup', 'End of month report'],
                    ['منصة واحدة: ميتا أو جوجل', 'ميزانية إعلان حتى 10,000 جنيه في الشهر', 'حملتين', 'تركيب البيكسل وتتبع التحويلات', 'تقرير آخر الشهر']),
                $plan('business', ['Business', 'الأعمال'], 10000, ['Meta and Google together, with retargeting.', 'ميتا وجوجل مع إعادة استهداف.'],
                    ['Meta and Google', 'Ad budget up to 50,000 EGP a month', 'Retargeting campaigns', 'Pixel and conversion tracking setup', 'Weekly short report', 'End of month report'],
                    ['ميتا وجوجل', 'ميزانية إعلان حتى 50,000 جنيه في الشهر', 'حملات إعادة استهداف', 'تركيب البيكسل وتتبع التحويلات', 'تقرير أسبوعي قصير', 'تقرير آخر الشهر']),
                $plan('premium', ['Premium', 'المتميزة'], 16000, ['Three platforms with a testing plan and a monthly strategy call.', 'ثلاث منصات بخطة اختبار ومكالمة استراتيجية شهرية.'],
                    ['Three platforms of your choice', 'Ad budget from 50,000 to 100,000 EGP a month', 'A testing plan for audiences and ads', 'A monthly strategy call', 'Weekly short report', 'End of month report'],
                    ['ثلاث منصات من اختيارك', 'ميزانية إعلان من 50,000 لـ 100,000 جنيه في الشهر', 'خطة اختبار للجمهور والإعلانات', 'مكالمة استراتيجية شهرية', 'تقرير أسبوعي قصير', 'تقرير آخر الشهر']),
            ],
            'en' => [
                'kicker' => 'Facebook, Instagram and Google ads',
                'headline' => 'Paid ads management for Facebook, Instagram and Google ads, for a fixed monthly fee.',
                'subheadline' => 'We build the campaigns, watch them and adjust them every week. You pay the ad platform directly for the ads, and us for the work.',
                'who' => 'Businesses that already have an offer and a way to answer customers, and want to reach more people with paid ads.',
                'need' => 'Your offer and prices, ready ad images or videos (or tell us you need them quoted), admin access to your page and ad account, and your ad budget paid to the platform.',
                'problems_title' => 'Why do ad budgets get wasted?',
                'problems' => [
                    ['icon' => 'chart', 'title' => 'Boosting posts with no plan', 'body' => 'Money goes to people who will never buy.'],
                    ['icon' => 'search', 'title' => 'No tracking', 'body' => 'You do not know which ad brought a message or a sale.'],
                    ['icon' => 'clock', 'title' => 'Nobody watches the campaign', 'body' => 'A weak ad keeps spending until someone notices.'],
                ],
                'pillars_title' => 'What we do',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'Setup', 'body' => 'Account, pixel, audiences and campaign structure built properly.'],
                    ['icon' => 'chart', 'title' => 'Tracking', 'body' => 'Conversions measured, so decisions use real numbers.'],
                    ['icon' => 'refresh', 'title' => 'Weekly optimization', 'body' => 'We pause weak ads, test new ones and move the budget to what works.'],
                    ['icon' => 'file', 'title' => 'Clear reports', 'body' => 'Spend, results and what we will change next.'],
                ],
                'paragraphs' => [
                    'Our fee is a flat monthly amount tied to a band of ad spend. The ad budget itself goes straight from your card to Meta or Google. We never hold your ad money.',
                    'We do not promise a return on ad spend. Results depend on your offer, your price, your page and your market. What we promise is clean setup, tracking and regular work, with a report you can read.',
                ],
                'list' => ['Ad account and pixel setup', 'Campaign structure and audiences', 'Conversion tracking', 'Weekly check and optimization', 'Reports as listed in your plan', 'Advice on your offer and landing page'],
                'excludes' => 'Not included: the ad budget itself (paid to the platform), design of ad images and videos, landing pages, and any tax or fee the ad platform charges.',
                'faq' => [
                    ['q' => 'Who pays for the ads?', 'a' => 'You do, directly to Meta or Google from your own card. Our fee is only for managing them.'],
                    ['q' => 'Can you make the ad images?', 'a' => 'Not inside these plans. We can quote them, or you can combine this service with our social media or branding service.'],
                    ['q' => 'What if my budget changes?', 'a' => 'Choose the plan for the budget band you expect. If your budget moves to a higher band, we move you to the next plan from the following month.'],
                ],
                'cta_title' => 'Ready to run ads that you can measure?',
            ],
            'ar' => [
                'kicker' => 'إعلانات فيسبوك وانستجرام وجوجل',
                'headline' => 'إدارة الحملات الإعلانية المدفوعة على فيسبوك وانستجرام وجوجل بأتعاب شهرية ثابتة.',
                'subheadline' => 'بنبني الحملات ونراقبها ونعدلها كل أسبوع. بتدفع منصة الإعلانات مباشرة تمن الإعلان، وبتدفعلنا تمن الشغل.',
                'who' => 'الأنشطة اللي عندها عرض وطريقة للرد على العملاء، وعايزة توصل لناس أكتر بإعلانات مدفوعة.',
                'need' => 'عرضك وأسعارك، وصور أو فيديوهات إعلانات جاهزة (أو قولنا إنك محتاج تسعيرها)، وصلاحية مدير على صفحتك وحساب الإعلانات، وميزانية الإعلان مدفوعة للمنصة.',
                'problems_title' => 'ليه ميزانيات الإعلانات بتضيع؟',
                'problems' => [
                    ['icon' => 'chart', 'title' => 'تعزيز بوستات من غير خطة', 'body' => 'الفلوس بتروح لناس عمرهم ما هيشتروا.'],
                    ['icon' => 'search', 'title' => 'مفيش تتبع', 'body' => 'مش عارف أنهي إعلان جاب رسالة أو بيعة.'],
                    ['icon' => 'clock', 'title' => 'محدش بيراقب الحملة', 'body' => 'الإعلان الضعيف بيفضل يصرف لحد ما حد ياخد باله.'],
                ],
                'pillars_title' => 'اللي بنعمله',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'التجهيز', 'body' => 'الحساب والبيكسل والجماهير وهيكل الحملات متبنيين صح.'],
                    ['icon' => 'chart', 'title' => 'التتبع', 'body' => 'قياس التحويلات عشان القرارات تتاخد على أرقام حقيقية.'],
                    ['icon' => 'refresh', 'title' => 'تحسين أسبوعي', 'body' => 'بنوقف الإعلان الضعيف، وبنجرب جديد، وننقل الميزانية للي بينجح.'],
                    ['icon' => 'file', 'title' => 'تقارير واضحة', 'body' => 'الصرف والنتايج وإيه اللي هنغيره بعد كده.'],
                ],
                'paragraphs' => [
                    'أتعابنا مبلغ شهري ثابت مرتبط بشريحة ميزانية الإعلان. ميزانية الإعلان نفسها بتروح من كارتك لميتا أو جوجل مباشرة. ومش بنمسك فلوس إعلاناتك أبدا.',
                    'مش بنوعد بعائد على الإعلان. النتيجة بتعتمد على عرضك وسعرك وصفحتك وسوقك. اللي بنوعد بيه تجهيز نضيف وتتبع وشغل منتظم وتقرير تقدر تفهمه.',
                ],
                'list' => ['تجهيز حساب الإعلانات والبيكسل', 'هيكل الحملات والجماهير', 'تتبع التحويلات', 'مراجعة وتحسين أسبوعي', 'تقارير حسب باقتك', 'نصايح على عرضك وصفحة الهبوط'],
                'excludes' => 'غير مشمول: ميزانية الإعلان نفسها (بتتدفع للمنصة)، وتصميم صور وفيديوهات الإعلانات، وصفحات الهبوط، وأي ضريبة أو رسوم بتاخدها منصة الإعلانات.',
                'faq' => [
                    ['q' => 'مين بيدفع تمن الإعلانات؟', 'a' => 'انت، مباشرة لميتا أو جوجل من كارتك. أتعابنا بس على إدارتها.'],
                    ['q' => 'بتعملوا صور الإعلانات؟', 'a' => 'مش داخل الباقات دي. بنسعّرها لوحدها، أو تدمج الخدمة دي مع خدمة السوشيال ميديا أو الهوية البصرية.'],
                    ['q' => 'لو ميزانيتي اتغيرت؟', 'a' => 'اختار الباقة على الشريحة المتوقعة. لو ميزانيتك راحت لشريحة أعلى بننقلك للباقة اللي بعدها من الشهر اللي بعده.'],
                ],
                'cta_title' => 'جاهز تشغّل إعلانات تقدر تقيسها؟',
            ],
        ];
    }

    private static function branding(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'logo-and-brand-identity', 'colors' => ['accent' => '#d97706', 'ink' => '#231503'], 'billing' => 'once', 'period' => self::ONCE,
            'name_en' => 'Logo and Brand Identity', 'name_ar' => 'تصميم لوجو وهوية بصرية',
            'tagline_en' => 'A logo, colors and fonts, from a simple logo to a full brand guide.',
            'tagline_ar' => 'لوجو وألوان وخطوط، من لوجو بسيط لدليل هوية كامل.',
            'plans' => [
                $plan('starter', ['Starter: Logo', 'الأساسية: لوجو'], 3500, ['A logo with colors and fonts and the source files.', 'لوجو بألوان وخطوط والملفات المصدرية.'],
                    ['2 logo concepts', '2 revision rounds on the chosen concept', 'Colors and fonts chosen', 'Final files for print and screen', 'Source files', '5 to 7 days'],
                    ['فكرتين للوجو', 'جولتين تعديل على الفكرة المختارة', 'اختيار الألوان والخطوط', 'ملفات نهائية للطباعة والشاشة', 'الملفات المصدرية', 'من 5 لـ 7 أيام']),
                $plan('business', ['Business: Identity', 'الأعمال: هوية'], 10000, ['A logo, a short brand guide and the first pieces you need.', 'لوجو ودليل هوية قصير وأول القطع اللي محتاجها.'],
                    ['3 logo concepts', '3 revision rounds on the chosen concept', 'A 12-page brand guide', 'Business card and social media kit', 'Final files and source files', '2 to 3 weeks'],
                    ['3 أفكار للوجو', '3 جولات تعديل على الفكرة المختارة', 'دليل هوية من 12 صفحة', 'كارت شخصي وطقم سوشيال ميديا', 'الملفات النهائية والمصدرية', 'من أسبوعين لـ 3']),
                $plan('premium', ['Premium: Full brand', 'المتميزة: هوية كاملة'], 24000, ['A naming workshop, full brand guide and applications.', 'ورشة تسمية ودليل هوية كامل وتطبيقاتها.'],
                    ['Naming workshop with a shortlist', 'Logo and a full brand guide', 'Stationery and packaging mockups', 'Social media templates', '3 revision rounds', '4 to 6 weeks'],
                    ['ورشة تسمية بقايمة مختصرة', 'لوجو ودليل هوية كامل', 'مطبوعات ونماذج تغليف', 'قوالب سوشيال ميديا', '3 جولات تعديل', 'من 4 لـ 6 أسابيع']),
            ],
            'en' => [
                'kicker' => 'Logo design and visual identity',
                'headline' => 'Logo design and brand identity for your business.',
                'subheadline' => 'We design a logo and a brand system you can use everywhere, and hand you all the files so you never depend on us to use them.',
                'who' => 'New businesses that need a first logo, and older ones whose look is inconsistent or out of date.',
                'need' => 'Your business name, what you sell, who your customers are, a few examples of looks you like, and any old logo you have.',
                'problems_title' => 'What goes wrong with a weak brand look?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'Every piece looks different', 'body' => 'Different colors and fonts on the sign, the page and the invoice make you look unsure.'],
                    ['icon' => 'users', 'title' => 'Customers cannot remember you', 'body' => 'A forgettable logo does not stay in the mind.'],
                    ['icon' => 'box', 'title' => 'You do not have the files', 'body' => 'A logo that only exists as a photo cannot be printed well.'],
                ],
                'pillars_title' => 'What you get',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'A logo with a reason', 'body' => 'Concepts based on your business and customers, not a template.'],
                    ['icon' => 'file', 'title' => 'Colors and fonts', 'body' => 'A small set you can use everywhere, with Arabic fonts that read well.'],
                    ['icon' => 'box', 'title' => 'All the files', 'body' => 'Print and screen files plus the source files.'],
                    ['icon' => 'lock', 'title' => 'Ownership', 'body' => 'The logo is yours when you pay in full.'],
                ],
                'paragraphs' => [
                    'Starter is a logo with colors and fonts. Business adds a short brand guide and the first pieces, a business card and a social media kit. Premium starts with a naming workshop and covers a full guide, stationery and packaging mockups.',
                    'We show the concepts, you choose one, and we refine it in the revision rounds listed in your plan. We do not copy other logos, and we check that the design is not a stock template.',
                ],
                'list' => ['Logo concepts as listed in your plan', 'Revision rounds on the chosen concept', 'Colors and fonts', 'Final files for print and screen', 'Source files', 'A brand guide in Business and Premium'],
                'excludes' => 'Not included: printing, trademark registration, paid fonts and stock photos beyond the license we tell you about, and website design.',
                'faq' => [
                    ['q' => 'Will I get the source files?', 'a' => 'Yes, in every plan. They are handed over when you have paid in full.'],
                    ['q' => 'Do you register the trademark?', 'a' => 'No. Registration is done with the official authority, and we suggest you speak to a trademark agent.'],
                    ['q' => 'What if I do not like any concept?', 'a' => 'Tell us what is wrong in one message. Revision rounds apply to the chosen concept, and the written scope explains what counts as a new concept.'],
                ],
                'cta_title' => 'Ready for a look people remember?',
            ],
            'ar' => [
                'kicker' => 'تصميم لوجو وهوية بصرية',
                'headline' => 'تصميم لوجو وهوية بصرية لنشاطك.',
                'subheadline' => 'بنصمم لوجو ونظام هوية تستخدمه في كل حتة، وبنسلمك كل الملفات عشان متعتمدش علينا لما تستخدمها.',
                'who' => 'الأنشطة الجديدة المحتاجة أول لوجو، والقديمة اللي شكلها مش موحد أو قديم.',
                'need' => 'اسم نشاطك، وبتبيع إيه، وعملاؤك مين، وأمثلة لأشكال بتعجبك، وأي لوجو قديم عندك.',
                'problems_title' => 'إيه اللي بيحصل مع هوية ضعيفة؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'كل قطعة بشكل مختلف', 'body' => 'ألوان وخطوط مختلفة على اللافتة والصفحة والفاتورة بتخليك تبان متردد.'],
                    ['icon' => 'users', 'title' => 'العملاء مش بيفتكروك', 'body' => 'لوجو عادي مش بيفضل في الذاكرة.'],
                    ['icon' => 'box', 'title' => 'مفيش عندك الملفات', 'body' => 'لوجو موجود كصورة بس مينفعش يتطبع كويس.'],
                ],
                'pillars_title' => 'هتحصل على إيه',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'لوجو وراه سبب', 'body' => 'أفكار مبنية على نشاطك وعملائك، مش قالب جاهز.'],
                    ['icon' => 'file', 'title' => 'ألوان وخطوط', 'body' => 'مجموعة صغيرة تستخدمها في كل حتة، بخطوط عربي مقروءة.'],
                    ['icon' => 'box', 'title' => 'كل الملفات', 'body' => 'ملفات للطباعة والشاشة والملفات المصدرية.'],
                    ['icon' => 'lock', 'title' => 'الملكية', 'body' => 'اللوجو ملكك لما تدفع كامل المبلغ.'],
                ],
                'paragraphs' => [
                    'الباقة الأساسية لوجو بألوان وخطوط. باقة الأعمال بتضيف دليل هوية قصير وأول القطع، كارت شخصي وطقم سوشيال ميديا. والمتميزة بتبدأ بورشة تسمية وتغطي دليل كامل ومطبوعات ونماذج تغليف.',
                    'بنعرض الأفكار، وتختار واحدة، وبنحسنها في جولات التعديل المكتوبة في باقتك. مش بننسخ لوجوهات غيرنا، وبنتأكد إن التصميم مش قالب جاهز.',
                ],
                'list' => ['أفكار اللوجو حسب باقتك', 'جولات تعديل على الفكرة المختارة', 'الألوان والخطوط', 'ملفات نهائية للطباعة والشاشة', 'الملفات المصدرية', 'دليل هوية في باقتي الأعمال والمتميزة'],
                'excludes' => 'غير مشمول: الطباعة، وتسجيل العلامة التجارية، والخطوط والصور المدفوعة خارج الترخيص اللي بنقولك عليه، وتصميم المواقع.',
                'faq' => [
                    ['q' => 'هاخد الملفات المصدرية؟', 'a' => 'أيوه، في كل الباقات. بتتسلم لما تدفع كامل المبلغ.'],
                    ['q' => 'بتسجلوا العلامة التجارية؟', 'a' => 'لا. التسجيل بيتم مع الجهة الرسمية، وننصحك تكلم وكيل علامات تجارية.'],
                    ['q' => 'لو معجبنيش أي فكرة؟', 'a' => 'قولنا إيه الغلط في رسالة واحدة. جولات التعديل بتتطبق على الفكرة المختارة، ونطاق الشغل المكتوب بيوضح إيه اللي يتحسب فكرة جديدة.'],
                ],
                'cta_title' => 'جاهز لشكل الناس تفتكره؟',
            ],
        ];
    }

    private static function chatbot(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'ai-chatbot-whatsapp-automation', 'colors' => ['accent' => '#16a34a', 'ink' => '#05200f'], 'billing' => 'once', 'period' => ['en' => 'setup, one payment', 'ar' => 'تجهيز، دفعة واحدة'],
            'name_en' => 'AI Chatbot and WhatsApp Automation', 'name_ar' => 'شات بوت واتساب بالذكاء الاصطناعي',
            'tagline_en' => 'A chatbot that answers your customers on your website and on WhatsApp, with a handoff to your team.',
            'tagline_ar' => 'شات بوت بيرد على عملائك على موقعك وعلى واتساب، وبيحوّل لفريقك لما يحتاج.',
            'plans' => [
                $plan('starter', ['Starter: Website bot', 'الأساسية: بوت موقع'], 9000, ['A chatbot on your website that answers your common questions.', 'شات بوت على موقعك بيرد على الأسئلة الشائعة.'],
                    ['Website chat widget', 'Up to 30 questions and answers from your own material', 'Handoff to a person with your WhatsApp number', 'Optional monthly upkeep: 1,500 EGP, not included', '1 revision round', 'About 2 weeks'],
                    ['شات على موقعك', 'حتى 30 سؤال وإجابة من مادتك', 'تحويل لشخص برقم واتسابك', 'صيانة شهرية اختيارية: 1,500 جنيه، غير مشمولة', 'جولة تعديل واحدة', 'حوالي أسبوعين']),
                $plan('business', ['Business: WhatsApp bot', 'الأعمال: بوت واتساب'], 22000, ['A bot on the official WhatsApp Cloud API with an order or booking flow.', 'بوت على واتساب الرسمي Cloud API بمسار طلب أو حجز.'],
                    ['Official WhatsApp Cloud API bot', 'Up to 100 questions and answers', 'One order or booking flow', 'Leads sent to a sheet or your CRM', 'Optional monthly upkeep: 3,000 EGP, not included', '2 revision rounds', '3 to 4 weeks'],
                    ['بوت على واتساب الرسمي Cloud API', 'حتى 100 سؤال وإجابة', 'مسار واحد للطلب أو الحجز', 'إرسال البيانات لشيت أو نظام العملاء', 'صيانة شهرية اختيارية: 3,000 جنيه، غير مشمولة', 'جولتين تعديل', 'من 3 لـ 4 أسابيع']),
                $plan('premium', ['Premium: AI agent', 'المتميزة: وكيل ذكي'], 55000, ['An AI agent that uses your tools and works on more than one channel.', 'وكيل ذكاء اصطناعي بيستخدم أدواتك وبيشتغل على أكتر من قناة.'],
                    ['AI agent on WhatsApp and your website', 'Connects to up to 3 of your tools, such as a calendar, a sheet or a CRM', 'Test set of real questions before launch', 'Usage dashboard', 'Optional monthly upkeep: 6,000 EGP, not included', '3 revision rounds', '6 to 8 weeks'],
                    ['وكيل ذكي على واتساب وموقعك', 'ربط بحد أقصى 3 من أدواتك، زي التقويم أو شيت أو نظام عملاء', 'مجموعة اختبار بأسئلة حقيقية قبل التشغيل', 'لوحة متابعة الاستخدام', 'صيانة شهرية اختيارية: 6,000 جنيه، غير مشمولة', '3 جولات تعديل', 'من 6 لـ 8 أسابيع']),
            ],
            'en' => [
                'kicker' => 'Chatbots and WhatsApp automation',
                'headline' => 'AI chatbot and WhatsApp automation for businesses that get the same questions all day.',
                'subheadline' => 'We build a bot from your own answers, connect it to your website or to the official WhatsApp API, and hand the hard questions to your team.',
                'who' => 'Shops, clinics, schools and companies whose team answers the same questions about prices, hours, orders and bookings again and again.',
                'need' => 'Your common questions and the right answers, your price list or services, and access to your WhatsApp Business account if the plan includes WhatsApp.',
                'problems_title' => 'What does answering by hand cost you?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'The same questions all day', 'body' => 'Your team repeats the same answers instead of doing other work.'],
                    ['icon' => 'message', 'title' => 'Customers wait or leave', 'body' => 'Messages that arrive at night wait until morning.'],
                    ['icon' => 'file', 'title' => 'Orders get lost in chat', 'body' => 'Details are scattered across long conversations.'],
                ],
                'pillars_title' => 'What the bot does',
                'pillars' => [
                    ['icon' => 'message', 'title' => 'Answers from your material', 'body' => 'It answers from the questions and answers you approved, not from guesses.'],
                    ['icon' => 'users', 'title' => 'Hands over to your team', 'body' => 'When it does not know, it tells the customer and passes the chat to a person.'],
                    ['icon' => 'file', 'title' => 'Collects orders and bookings', 'body' => 'It asks the details and sends them to your sheet or CRM.'],
                    ['icon' => 'shield', 'title' => 'Official WhatsApp', 'body' => 'We use the official WhatsApp Cloud API, not unofficial tools that can get your number blocked.'],
                ],
                'paragraphs' => [
                    'A chatbot is only as good as the answers behind it. We start from your own material, test the bot with real questions before launch, and set it to hand over to a person when it is unsure.',
                    'The price is for the setup. Meta charges per message on WhatsApp and AI providers charge for usage. These are paid by you to those companies and are not in our price. Monthly upkeep is optional and quoted per plan.',
                ],
                'list' => ['A bot built from your approved questions and answers', 'Handoff to a person', 'Website widget in Starter', 'Official WhatsApp Cloud API in Business and Premium', 'Order or booking flow in Business', 'Connections to your tools in Premium', 'A test with real questions before launch'],
                'excludes' => 'Not included: WhatsApp message fees charged by Meta, AI provider usage fees, business number verification, hosting fees, and the optional monthly upkeep.',
                'faq' => [
                    ['q' => 'Can the bot say something wrong?', 'a' => 'It can. That is why it answers from your approved material, is tested before launch and hands over to a person when it is unsure. We cannot promise it is never wrong, and you should read the first conversations.'],
                    ['q' => 'Do you use unofficial WhatsApp tools?', 'a' => 'No. The WhatsApp plans use the official Cloud API from Meta. Unofficial tools risk getting your number blocked.'],
                    ['q' => 'Who pays Meta and the AI provider?', 'a' => 'You do, directly. We help you estimate the cost for your message volume before you start.'],
                ],
                'cta_title' => 'Ready to stop answering the same questions by hand?',
            ],
            'ar' => [
                'kicker' => 'شات بوت وأتمتة واتساب',
                'headline' => 'شات بوت ذكاء اصطناعي وأتمتة واتساب للأنشطة اللي بتاخد نفس الأسئلة طول اليوم.',
                'subheadline' => 'بنبني بوت من إجاباتك انت، وبنوصله بموقعك أو بواتساب الرسمي، وبنحوّل الأسئلة الصعبة لفريقك.',
                'who' => 'المحلات والعيادات والمدارس والشركات اللي فريقها بيرد على نفس أسئلة الأسعار والمواعيد والطلبات والحجز كل يوم.',
                'need' => 'أسئلتك الشائعة وإجاباتها الصح، وقايمة أسعارك أو خدماتك، وصلاحية على حساب واتساب للأعمال لو الباقة فيها واتساب.',
                'problems_title' => 'الرد باليد بيكلفك إيه؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'نفس الأسئلة طول اليوم', 'body' => 'فريقك بيكرر نفس الإجابات بدل ما يعمل شغل تاني.'],
                    ['icon' => 'message', 'title' => 'العميل بيستنى أو بيمشي', 'body' => 'الرسايل اللي بتيجي بالليل بتستنى للصبح.'],
                    ['icon' => 'file', 'title' => 'الطلبات بتضيع في الشات', 'body' => 'التفاصيل متفرقة في محادثات طويلة.'],
                ],
                'pillars_title' => 'البوت بيعمل إيه',
                'pillars' => [
                    ['icon' => 'message', 'title' => 'بيرد من مادتك', 'body' => 'بيرد من الأسئلة والإجابات اللي وافقت عليها، مش بالتخمين.'],
                    ['icon' => 'users', 'title' => 'بيحوّل لفريقك', 'body' => 'لما مش بيعرف، بيقول للعميل وبيحول المحادثة لشخص.'],
                    ['icon' => 'file', 'title' => 'بيجمع الطلبات والحجز', 'body' => 'بيسأل عن التفاصيل وبيبعتها لشيتك أو نظام العملاء.'],
                    ['icon' => 'shield', 'title' => 'واتساب الرسمي', 'body' => 'بنستخدم واتساب Cloud API الرسمي، مش أدوات غير رسمية ممكن تخلي رقمك يتحظر.'],
                ],
                'paragraphs' => [
                    'البوت بجودة الإجابات اللي وراه. بنبدأ من مادتك انت، ونجرب البوت بأسئلة حقيقية قبل التشغيل، وبنظبطه يحوّل لشخص لما يتردد.',
                    'السعر للتجهيز. ميتا بتاخد رسوم على كل رسالة واتساب، ومزودو الذكاء الاصطناعي بياخدوا على الاستخدام. دي بتتدفع منك للشركات دي وهي مش في سعرنا. الصيانة الشهرية اختيارية ومتسعّرة لكل باقة.',
                ],
                'list' => ['بوت مبني من أسئلتك وإجاباتك المعتمدة', 'تحويل لشخص', 'شات على الموقع في الباقة الأساسية', 'واتساب Cloud API الرسمي في الأعمال والمتميزة', 'مسار طلب أو حجز في باقة الأعمال', 'ربط بأدواتك في المتميزة', 'اختبار بأسئلة حقيقية قبل التشغيل'],
                'excludes' => 'غير مشمول: رسوم رسايل واتساب اللي بتاخدها ميتا، ورسوم استخدام مزود الذكاء الاصطناعي، وتوثيق رقم النشاط، ورسوم الاستضافة، والصيانة الشهرية الاختيارية.',
                'faq' => [
                    ['q' => 'ممكن البوت يقول حاجة غلط؟', 'a' => 'ممكن. عشان كده بيرد من مادتك المعتمدة، وبيتجرب قبل التشغيل، وبيحوّل لشخص لما يتردد. مش بنقدر نوعد إنه مش هيغلط أبدا، والأحسن تقرا أول المحادثات.'],
                    ['q' => 'بتستخدموا أدوات واتساب غير رسمية؟', 'a' => 'لا. باقات واتساب بتستخدم Cloud API الرسمي من ميتا. الأدوات غير الرسمية فيها خطر حظر رقمك.'],
                    ['q' => 'مين بيدفع لميتا ومزود الذكاء الاصطناعي؟', 'a' => 'انت، مباشرة. بنساعدك تقدّر التكلفة على حجم رسايلك قبل ما تبدأ.'],
                ],
                'cta_title' => 'جاهز تبطل ترد على نفس الأسئلة باليد؟',
            ],
        ];
    }
}
