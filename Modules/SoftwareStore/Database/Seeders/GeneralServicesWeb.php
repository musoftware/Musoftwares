<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Website services: company website, landing page, online store, custom web app, website care.
 * Plans and prices follow the web market research of Oct 2026 (Egyptian agency ranges, low to middle part).
 */
class GeneralServicesWeb
{
    private const ONCE = ['en' => 'one payment', 'ar' => 'دفعة واحدة'];

    private const MONTHLY = ['en' => 'per month', 'ar' => 'في الشهر'];

    /** @return array<int, array<string, mixed>> */
    public static function services(): array
    {
        return [self::companyWebsite(), self::landingPage(), self::onlineStore(), self::customApp(), self::websiteCare()];
    }

    private static function companyWebsite(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'company-website-design', 'colors' => ['accent' => '#2563eb', 'ink' => '#0b1730'], 'billing' => 'once', 'period' => self::ONCE,
            'name_en' => 'Company Website Design', 'name_ar' => 'تصميم موقع شركة',
            'tagline_en' => 'A fast company website in Arabic and English, built on WordPress, with a fixed price.',
            'tagline_ar' => 'موقع شركة سريع بالعربي والإنجليزي على ووردبريس، بسعر ثابت.',
            'plans' => [
                $plan('starter', ['Starter', 'الأساسية'], 9000, ['Up to 5 pages from a template made to fit your brand.', 'حتى 5 صفحات من قالب متفصل على هويتك.'],
                    ['Up to 5 pages in one language', 'Contact form and WhatsApp button', 'SSL setup and basic on-page SEO', '2 revision rounds', 'About 2 weeks', '30 days of fixes after handover'],
                    ['حتى 5 صفحات بلغة واحدة', 'فورم تواصل وزرار واتساب', 'تركيب SSL وSEO أساسي للصفحات', 'جولتين تعديل', 'حوالي أسبوعين', '30 يوم إصلاحات بعد التسليم']),
                $plan('business', ['Business', 'الأعمال'], 22000, ['Up to 10 pages in Arabic and English, with right-to-left done properly.', 'حتى 10 صفحات بالعربي والإنجليزي بدعم صح للاتجاه من اليمين.'],
                    ['Up to 10 pages in Arabic and English', 'Blog and a content editor you can use', 'Google Analytics and Search Console setup', 'One hour of training for your team', '2 revision rounds', '3 to 4 weeks', '30 days of fixes after handover'],
                    ['حتى 10 صفحات بالعربي والإنجليزي', 'مدونة ولوحة تحرير تقدر تستخدمها', 'ربط Google Analytics وSearch Console', 'ساعة تدريب لفريقك', 'جولتين تعديل', 'من 3 لـ 4 أسابيع', '30 يوم إصلاحات بعد التسليم']),
                $plan('premium', ['Premium', 'المتميزة'], 45000, ['A custom design made from scratch, up to 15 pages.', 'تصميم مخصوص من الصفر حتى 15 صفحة.'],
                    ['Custom design, up to 15 pages', 'Arabic and English', 'Speed target checked before handover', 'Structured data for search engines', '3 revision rounds', '5 to 6 weeks', '30 days of fixes after handover'],
                    ['تصميم مخصوص حتى 15 صفحة', 'عربي وإنجليزي', 'فحص السرعة قبل التسليم', 'بيانات منظمة لمحركات البحث', '3 جولات تعديل', 'من 5 لـ 6 أسابيع', '30 يوم إصلاحات بعد التسليم']),
            ],
            'en' => [
                'kicker' => 'Company websites in Egypt',
                'headline' => 'Company website design in Arabic and English, at a fixed price.',
                'subheadline' => 'We design and build your company website on WordPress. You see the price and the scope before you pay, and you own the site when it is done.',
                'who' => 'Companies, clinics, offices and shops that have no website, or have an old one that looks wrong on phones.',
                'need' => 'Your logo, your texts and photos (or tell us you need help with them), and your domain name if you already have one.',
                'problems_title' => 'Why does a company need a proper website?',
                'problems' => [
                    ['icon' => 'search', 'title' => 'Customers cannot find you', 'body' => 'Without a site, people who search for your service or your name find your competitors first.'],
                    ['icon' => 'phone', 'title' => 'The old site breaks on phones', 'body' => 'Most visitors use a phone. A site that is slow or cut off on a phone loses them in seconds.'],
                    ['icon' => 'file', 'title' => 'Arabic looks wrong', 'body' => 'Cheap templates break the right-to-left layout and the Arabic fonts.'],
                ],
                'pillars_title' => 'What we build for you',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'A design that fits your brand', 'body' => 'Your colors, your logo, your tone. Not a copy of another site.'],
                    ['icon' => 'message', 'title' => 'Arabic and English done right', 'body' => 'Right-to-left layout, proper Arabic fonts and a language switch.'],
                    ['icon' => 'zap', 'title' => 'Fast and mobile first', 'body' => 'Light pages, compressed images and a layout planned for phones first.'],
                    ['icon' => 'lock', 'title' => 'You own it', 'body' => 'Ownership passes to you when you pay in full. You can edit your texts yourself.'],
                ],
                'paragraphs' => [
                    'We build company websites on WordPress, because you can edit your own texts and photos later without a developer. Each plan has a fixed number of pages, languages and revision rounds, written in your scope before we start.',
                    'The Starter plan fits a small business that needs a clean presence. The Business plan adds both languages, a blog and tracking. The Premium plan is a custom design for a company that wants to stand out.',
                ],
                'list' => ['Pages as listed in your plan', 'Mobile layout checked on real phone sizes', 'Contact form with email delivery', 'WhatsApp and call buttons', 'SSL certificate setup', 'Basic on-page SEO: titles, descriptions and headings', 'Handover of the files and the admin login'],
                'excludes' => 'Not included: writing your texts, photos and logo, domain and hosting fees and their yearly renewal, paid plugins or fonts, and pages beyond the plan.',
                'faq' => [
                    ['q' => 'Do I need to buy hosting and a domain?', 'a' => 'Yes, they are paid to the hosting company, not to us, and they renew every year. We help you choose and set them up, and we write the costs in the scope before you decide.'],
                    ['q' => 'Can I edit the site myself?', 'a' => 'Yes. WordPress lets you change texts, photos and posts. The Business plan includes one hour of training.'],
                    ['q' => 'Will my site be first on Google?', 'a' => 'We cannot promise a ranking. We set up the basics that search engines need: clear titles, fast pages and a mobile layout. Ranking also depends on your competitors and on time.'],
                ],
                'cta_title' => 'Ready for a company website that works on every phone?',
            ],
            'ar' => [
                'kicker' => 'مواقع الشركات في مصر',
                'headline' => 'تصميم موقع شركة بالعربي والإنجليزي بسعر ثابت.',
                'subheadline' => 'بنصمم ونبني موقع شركتك على ووردبريس. بتشوف السعر ونطاق الشغل قبل ما تدفع، والموقع بيبقى ملكك بعد التسليم.',
                'who' => 'الشركات والعيادات والمكاتب والمحلات اللي معندهاش موقع، أو عندها موقع قديم بيبوظ على الموبايل.',
                'need' => 'اللوجو والنصوص والصور (أو قولنا إنك محتاج مساعدة فيهم)، واسم الدومين لو عندك واحد.',
                'problems_title' => 'ليه الشركة محتاجة موقع محترم؟',
                'problems' => [
                    ['icon' => 'search', 'title' => 'العملاء مش بيلاقوك', 'body' => 'من غير موقع، اللي بيدور على خدمتك أو اسمك بيلاقي منافسيك الأول.'],
                    ['icon' => 'phone', 'title' => 'الموقع القديم بيبوظ على الموبايل', 'body' => 'أغلب الزوار بيستخدموا الموبايل. موقع بطيء أو مقصوص بيضيعهم في ثواني.'],
                    ['icon' => 'file', 'title' => 'العربي بيظهر غلط', 'body' => 'القوالب الرخيصة بتبوظ الاتجاه من اليمين لليسار وخطوط العربي.'],
                ],
                'pillars_title' => 'اللي بنبنيه لك',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'تصميم على هويتك', 'body' => 'ألوانك ولوجو وأسلوبك. مش نسخة من موقع تاني.'],
                    ['icon' => 'message', 'title' => 'عربي وإنجليزي صح', 'body' => 'اتجاه من اليمين وخطوط عربي سليمة وزرار تغيير لغة.'],
                    ['icon' => 'zap', 'title' => 'سريع ومناسب للموبايل', 'body' => 'صفحات خفيفة وصور مضغوطة وتصميم متخطط للموبايل الأول.'],
                    ['icon' => 'lock', 'title' => 'ملكك انت', 'body' => 'الملكية بتنتقلك لما تدفع كامل المبلغ، وتقدر تعدل نصوصك بنفسك.'],
                ],
                'paragraphs' => [
                    'بنبني مواقع الشركات على ووردبريس، لأنك بعد كده تقدر تعدل النصوص والصور من غير مبرمج. كل باقة ليها عدد صفحات ولغات وجولات تعديل ثابتة، ومكتوبة في نطاق الشغل قبل ما نبدأ.',
                    'الباقة الأساسية مناسبة لنشاط صغير محتاج وجود نضيف. باقة الأعمال بتضيف اللغتين ومدونة وتتبع الزوار. والباقة المتميزة تصميم مخصوص لشركة عايزة تبان مختلفة.',
                ],
                'list' => ['الصفحات على حسب باقتك', 'مراجعة التصميم على مقاسات موبايل حقيقية', 'فورم تواصل بيوصل على الإيميل', 'أزرار واتساب واتصال', 'تركيب شهادة SSL', 'SEO أساسي للصفحات: عناوين ووصف وعناوين فرعية', 'تسليم الملفات ودخول لوحة التحكم'],
                'excludes' => 'غير مشمول: كتابة النصوص والصور واللوجو، ورسوم الدومين والاستضافة وتجديدها كل سنة، والإضافات أو الخطوط المدفوعة، والصفحات الزيادة عن الباقة.',
                'faq' => [
                    ['q' => 'لازم أشتري استضافة ودومين؟', 'a' => 'أيوه، وبيتدفعوا لشركة الاستضافة مش لنا، وبيتجددوا كل سنة. بنساعدك تختار وتركّب، ونكتب التكلفة في نطاق الشغل قبل ما تقرر.'],
                    ['q' => 'أقدر أعدل الموقع بنفسي؟', 'a' => 'أيوه. ووردبريس بيخليك تغيّر النصوص والصور والمقالات. باقة الأعمال فيها ساعة تدريب.'],
                    ['q' => 'موقعي هيبقى الأول في جوجل؟', 'a' => 'مش بنقدر نوعد بترتيب. بنجهز الأساسيات اللي محركات البحث محتاجاها: عناوين واضحة وصفحات سريعة وتصميم موبايل. الترتيب كمان بيعتمد على منافسينك وعلى الوقت.'],
                ],
                'cta_title' => 'جاهز لموقع شركة بيشتغل على أي موبايل؟',
            ],
        ];
    }

    private static function landingPage(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'landing-page-design', 'colors' => ['accent' => '#7c3aed', 'ink' => '#160b2e'], 'billing' => 'once', 'period' => self::ONCE,
            'name_en' => 'Landing Page Design', 'name_ar' => 'تصميم صفحة هبوط',
            'tagline_en' => 'One focused page that turns a visit into a message, a call or an order.',
            'tagline_ar' => 'صفحة واحدة مركزة تحوّل الزيارة لرسالة أو مكالمة أو طلب.',
            'plans' => [
                $plan('starter', ['Starter', 'الأساسية'], 4000, ['One page with a form and a WhatsApp button.', 'صفحة واحدة بفورم وزرار واتساب.'],
                    ['1 page, Arabic or English', 'Lead form with email delivery', 'WhatsApp button', 'Ad pixel installed', '2 revision rounds', 'About 5 days'],
                    ['صفحة واحدة، عربي أو إنجليزي', 'فورم بيانات بيوصل على الإيميل', 'زرار واتساب', 'تركيب بيكسل الإعلانات', 'جولتين تعديل', 'حوالي 5 أيام']),
                $plan('business', ['Business', 'الأعمال'], 8000, ['A page with a copy review and tracking of every click that matters.', 'صفحة بمراجعة للنص وتتبع لكل نقرة مهمة.'],
                    ['1 page with two versions to compare', 'Review and tightening of your copy', 'Conversion tracking events', 'Speed check before handover', '2 revision rounds', '7 to 10 days'],
                    ['صفحة بنسختين للمقارنة', 'مراجعة وتحسين نص الصفحة', 'تتبع أحداث التحويل', 'فحص السرعة قبل التسليم', 'جولتين تعديل', 'من 7 لـ 10 أيام']),
                $plan('premium', ['Premium', 'المتميزة'], 16000, ['A custom page with motion and a link to your CRM or email tool.', 'صفحة مخصوصة بحركة وربط بنظام العملاء أو الإيميل.'],
                    ['Custom design with motion', '2 versions to compare', 'Leads sent to your CRM or email tool', 'Conversion tracking events', '3 revision rounds', '10 to 14 days'],
                    ['تصميم مخصوص بحركة', 'نسختين للمقارنة', 'إرسال البيانات لنظام العملاء أو أداة الإيميل', 'تتبع أحداث التحويل', '3 جولات تعديل', 'من 10 لـ 14 يوم']),
            ],
            'en' => [
                'kicker' => 'Landing pages for ads and campaigns',
                'headline' => 'Landing page design that turns visitors into messages and orders.',
                'subheadline' => 'One page, one goal. We build a fast page for your ad or your offer, with a form, a WhatsApp button and tracking.',
                'who' => 'Anyone who runs ads, sells one main offer, or needs a simple page for a campaign or an event.',
                'need' => 'Your offer, your texts (or a short description and we draft them with you), your logo and photos, and access to your ad account if you want the pixel installed.',
                'problems_title' => 'Why do ads send people to the wrong place?',
                'problems' => [
                    ['icon' => 'chart', 'title' => 'Ads send people to a busy homepage', 'body' => 'A visitor who has to look around for the offer usually leaves.'],
                    ['icon' => 'phone', 'title' => 'The page is slow on phones', 'body' => 'Every second of waiting loses visitors you paid for.'],
                    ['icon' => 'search', 'title' => 'You cannot tell what works', 'body' => 'Without tracking you do not know which button or version brings the messages.'],
                ],
                'pillars_title' => 'What the page includes',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'One clear goal', 'body' => 'Everything on the page leads to one action: a message, a call or a form.'],
                    ['icon' => 'zap', 'title' => 'Fast on phones', 'body' => 'Light code and compressed images so the page opens quickly.'],
                    ['icon' => 'message', 'title' => 'Form and WhatsApp', 'body' => 'Leads reach your email and your WhatsApp the moment they ask.'],
                    ['icon' => 'chart', 'title' => 'Tracking', 'body' => 'Your ad pixel and conversion events, so you can see what works.'],
                ],
                'paragraphs' => [
                    'A landing page is a single page built for one goal. We keep it short: a headline that says the offer, proof, the details and one clear button.',
                    'The Business and Premium plans give you two versions of the page so you can compare them with real visitors. Which one wins depends on your audience, and we do not promise a conversion rate.',
                ],
                'list' => ['One page built for one goal', 'Mobile layout checked on real phone sizes', 'Lead form with email delivery', 'WhatsApp button', 'Ad pixel installation', 'Handover of the files and the page login'],
                'excludes' => 'Not included: ad spend, photos and video shoots, domain and hosting fees, and long copywriting. We can draft short texts with you.',
                'faq' => [
                    ['q' => 'Can I run the page on my own domain?', 'a' => 'Yes. We connect it to your domain or to a subdomain, such as offer.yourbrand.com.'],
                    ['q' => 'Do you run my ads?', 'a' => 'Not in this service. If you want us to run your ads, see our paid ads management service.'],
                    ['q' => 'How many sales will I get?', 'a' => 'We cannot promise a number. We build the page and the tracking so you can measure it. Results depend on your offer, your price and your ads.'],
                ],
                'cta_title' => 'Ready for a landing page that works for your ads?',
            ],
            'ar' => [
                'kicker' => 'صفحات هبوط للإعلانات والحملات',
                'headline' => 'تصميم صفحة هبوط تحوّل الزوار لرسائل وطلبات.',
                'subheadline' => 'صفحة واحدة وهدف واحد. بنبني صفحة سريعة لإعلانك أو عرضك، فيها فورم وزرار واتساب وتتبع.',
                'who' => 'أي حد بيشغل إعلانات، أو بيبيع عرض رئيسي، أو محتاج صفحة بسيطة لحملة أو فعالية.',
                'need' => 'عرضك، ونصوصك (أو وصف قصير ونكتبها معاك)، واللوجو والصور، وصلاحية على حساب الإعلانات لو عايز نركّب البيكسل.',
                'problems_title' => 'ليه الإعلانات بتودّي الناس مكان غلط؟',
                'problems' => [
                    ['icon' => 'chart', 'title' => 'الإعلان بيودّي لصفحة رئيسية زحمة', 'body' => 'الزائر اللي بيدور على العرض في الموقع غالبا بيمشي.'],
                    ['icon' => 'phone', 'title' => 'الصفحة بطيئة على الموبايل', 'body' => 'كل ثانية انتظار بتضيّع زوار انت دفعت عليهم.'],
                    ['icon' => 'search', 'title' => 'مش عارف إيه اللي بينجح', 'body' => 'من غير تتبع مش هتعرف أنهي زرار أو نسخة بتجيب رسايل.'],
                ],
                'pillars_title' => 'الصفحة فيها إيه',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'هدف واحد واضح', 'body' => 'كل حاجة في الصفحة بتوصل لخطوة واحدة: رسالة أو مكالمة أو فورم.'],
                    ['icon' => 'zap', 'title' => 'سريعة على الموبايل', 'body' => 'كود خفيف وصور مضغوطة عشان الصفحة تفتح بسرعة.'],
                    ['icon' => 'message', 'title' => 'فورم وواتساب', 'body' => 'البيانات بتوصل على إيميلك وواتسابك في لحظة الطلب.'],
                    ['icon' => 'chart', 'title' => 'تتبع', 'body' => 'بيكسل الإعلانات وأحداث التحويل، عشان تشوف إيه اللي بينجح.'],
                ],
                'paragraphs' => [
                    'صفحة الهبوط صفحة واحدة مبنية لهدف واحد. بنخليها قصيرة: عنوان بيقول العرض، وإثبات، والتفاصيل، وزرار واحد واضح.',
                    'باقتي الأعمال والمتميزة بتديك نسختين من الصفحة تقارن بينهم على زوار حقيقيين. مين يكسب بيعتمد على جمهورك، ومش بنوعد بنسبة تحويل.',
                ],
                'list' => ['صفحة واحدة لهدف واحد', 'مراجعة التصميم على مقاسات موبايل حقيقية', 'فورم بيانات بيوصل على الإيميل', 'زرار واتساب', 'تركيب بيكسل الإعلانات', 'تسليم الملفات ودخول الصفحة'],
                'excludes' => 'غير مشمول: ميزانية الإعلانات، وتصوير الصور والفيديو، ورسوم الدومين والاستضافة، وكتابة النصوص الطويلة. بنقدر نكتب معاك نصوص قصيرة.',
                'faq' => [
                    ['q' => 'أقدر أشغل الصفحة على الدومين بتاعي؟', 'a' => 'أيوه. بنربطها بالدومين أو بدومين فرعي زي offer.yourbrand.com.'],
                    ['q' => 'بتديروا إعلاناتي؟', 'a' => 'مش في الخدمة دي. لو عايزنا نشغل إعلاناتك، شوف خدمة إدارة الحملات الإعلانية.'],
                    ['q' => 'هجيب كام عملية بيع؟', 'a' => 'مش بنقدر نوعد برقم. بنبني الصفحة والتتبع عشان تقيس بنفسك. النتيجة بتعتمد على عرضك وسعرك وإعلاناتك.'],
                ],
                'cta_title' => 'جاهز لصفحة هبوط تشتغل مع إعلاناتك؟',
            ],
        ];
    }

    private static function onlineStore(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'online-store-development', 'colors' => ['accent' => '#059669', 'ink' => '#06231a'], 'billing' => 'once', 'period' => self::ONCE,
            'name_en' => 'Online Store Development', 'name_ar' => 'تصميم متجر إلكتروني',
            'tagline_en' => 'A ready-to-sell online store on Salla, Zid or WooCommerce, with payment and shipping set up.',
            'tagline_ar' => 'متجر إلكتروني جاهز للبيع على سلة أو زد أو ووكومرس، بالدفع والشحن متظبطين.',
            'plans' => [
                $plan('starter', ['Starter: Salla or Zid', 'الأساسية: سلة أو زد'], 15000, ['A store on Salla or Zid, set up and ready to sell.', 'متجر على سلة أو زد، متجهز وجاهز للبيع.'],
                    ['Store on Salla or Zid, your choice', 'Theme set up with your brand', 'Up to 30 products entered', 'Payment and shipping set up', '2 revision rounds', 'About 7 days'],
                    ['متجر على سلة أو زد، حسب اختيارك', 'تجهيز القالب على هويتك', 'إدخال حتى 30 منتج', 'ضبط الدفع والشحن', 'جولتين تعديل', 'حوالي 7 أيام']),
                $plan('business', ['Business: WooCommerce', 'الأعمال: ووكومرس'], 35000, ['A WooCommerce store in Arabic and English that you fully own.', 'متجر ووكومرس بالعربي والإنجليزي ملكك بالكامل.'],
                    ['WooCommerce store, Arabic and English', 'Up to 100 products entered', 'Payment gateway and shipping set up', 'Order emails and WhatsApp order button', 'One hour of training', '2 revision rounds', '3 to 4 weeks'],
                    ['متجر ووكومرس عربي وإنجليزي', 'إدخال حتى 100 منتج', 'ضبط بوابة الدفع والشحن', 'إيميلات الطلبات وزرار طلب واتساب', 'ساعة تدريب', 'جولتين تعديل', 'من 3 لـ 4 أسابيع']),
                $plan('premium', ['Premium: custom WooCommerce', 'المتميزة: ووكومرس مخصوص'], 75000, ['A store with a custom design and the extras your business needs.', 'متجر بتصميم مخصوص وإضافات حسب نشاطك.'],
                    ['Custom store design', 'Up to 500 products imported', 'Product filters and variations', 'Speed work before handover', 'One integration you choose, such as shipping or accounting', '3 revision rounds', '5 to 6 weeks'],
                    ['تصميم مخصوص للمتجر', 'استيراد حتى 500 منتج', 'فلاتر المنتجات والاختيارات', 'تحسين السرعة قبل التسليم', 'ربط واحد من اختيارك، زي الشحن أو المحاسبة', '3 جولات تعديل', 'من 5 لـ 6 أسابيع']),
            ],
            'en' => [
                'kicker' => 'Online stores for Egypt and the Gulf',
                'headline' => 'Online store design on Salla, Zid or WooCommerce, ready to sell.',
                'subheadline' => 'We set up your store, your products, your payment and your shipping, so your first order can arrive in days, not months.',
                'who' => 'Shops and brands that sell products and want to take orders online, in Egypt or in the Gulf.',
                'need' => 'Your logo, your product names, prices and photos, and the shipping company you plan to use.',
                'problems_title' => 'What stops a shop from selling online?',
                'problems' => [
                    ['icon' => 'box', 'title' => 'Orders by messages only', 'body' => 'Taking orders in chat is slow, and you lose track of who ordered what.'],
                    ['icon' => 'lock', 'title' => 'Payment and shipping are confusing', 'body' => 'Choosing a gateway and a shipping company and connecting them takes time.'],
                    ['icon' => 'phone', 'title' => 'A store that is hard to use on phones', 'body' => 'Most buyers shop on a phone. A hard checkout loses the sale.'],
                ],
                'pillars_title' => 'What we set up',
                'pillars' => [
                    ['icon' => 'box', 'title' => 'Products and categories', 'body' => 'Your products entered with prices, photos and categories.'],
                    ['icon' => 'lock', 'title' => 'Payment', 'body' => 'A payment gateway connected and tested with a real order.'],
                    ['icon' => 'send', 'title' => 'Shipping', 'body' => 'Shipping zones and prices set the way your shipping company charges.'],
                    ['icon' => 'message', 'title' => 'Order messages', 'body' => 'Order emails, and a WhatsApp button for questions before buying.'],
                ],
                'paragraphs' => [
                    'Salla and Zid are good when you want to start fast and pay a monthly platform plan. WooCommerce fits you when you want to own everything and avoid platform lock-in. We help you pick, and we write the choice in your scope.',
                    'We test a full order before handover: add to cart, pay, receive the order email and see it in your dashboard.',
                ],
                'list' => ['Store on the platform of your choice', 'Theme set up with your brand', 'Products entered as listed in your plan', 'Payment gateway connection and a test order', 'Shipping zones and prices', 'Order emails', 'Handover of the logins'],
                'excludes' => 'Not included: the platform subscription (Salla or Zid), hosting and domain, payment gateway fees and commissions, shipping contracts, product photos and extra products beyond the plan.',
                'faq' => [
                    ['q' => 'Salla, Zid or WooCommerce?', 'a' => 'Salla and Zid are quick to start and common in Saudi Arabia, with a monthly plan paid to the platform. WooCommerce has no platform fee but needs hosting. Tell us about your shop and we advise you before you pay.'],
                    ['q' => 'Who pays the gateway and platform fees?', 'a' => 'You do, directly to those companies. They are not part of our price.'],
                    ['q' => 'Can I add products later?', 'a' => 'Yes. We show you how, and the Business plan includes a training hour.'],
                ],
                'cta_title' => 'Ready to take your first online order?',
            ],
            'ar' => [
                'kicker' => 'متاجر إلكترونية لمصر والخليج',
                'headline' => 'تصميم متجر إلكتروني على سلة أو زد أو ووكومرس، جاهز للبيع.',
                'subheadline' => 'بنجهز متجرك ومنتجاتك والدفع والشحن، عشان أول طلب يوصلك في أيام مش شهور.',
                'who' => 'المحلات والبراندات اللي بتبيع منتجات وعايزة تاخد طلبات أونلاين، في مصر أو الخليج.',
                'need' => 'اللوجو، وأسماء المنتجات وأسعارها وصورها، وشركة الشحن اللي هتتعامل معاها.',
                'problems_title' => 'إيه اللي بيمنع المحل إنه يبيع أونلاين؟',
                'problems' => [
                    ['icon' => 'box', 'title' => 'الطلبات بالرسايل بس', 'body' => 'استلام الطلبات في الشات بطيء، وبتضيع منك مين طلب إيه.'],
                    ['icon' => 'lock', 'title' => 'الدفع والشحن معقدين', 'body' => 'اختيار بوابة دفع وشركة شحن وربطهم بياخد وقت.'],
                    ['icon' => 'phone', 'title' => 'متجر صعب على الموبايل', 'body' => 'أغلب المشترين على الموبايل. صفحة دفع صعبة بتضيع البيعة.'],
                ],
                'pillars_title' => 'اللي بنجهزه',
                'pillars' => [
                    ['icon' => 'box', 'title' => 'المنتجات والأقسام', 'body' => 'منتجاتك مدخلة بأسعارها وصورها وأقسامها.'],
                    ['icon' => 'lock', 'title' => 'الدفع', 'body' => 'بوابة دفع متوصلة ومجربة بطلب حقيقي.'],
                    ['icon' => 'send', 'title' => 'الشحن', 'body' => 'مناطق الشحن وأسعاره زي ما شركة الشحن بتحاسب.'],
                    ['icon' => 'message', 'title' => 'رسايل الطلبات', 'body' => 'إيميلات الطلبات وزرار واتساب للأسئلة قبل الشراء.'],
                ],
                'paragraphs' => [
                    'سلة وزد كويسين لو عايز تبدأ بسرعة وتدفع اشتراك شهري للمنصة. ووكومرس مناسب لو عايز تملك كل حاجة ومتتقيدش بمنصة. بنساعدك تختار ونكتب الاختيار في نطاق الشغل.',
                    'بنجرب طلب كامل قبل التسليم: إضافة للسلة، دفع، وصول إيميل الطلب، وظهوره في لوحة التحكم.',
                ],
                'list' => ['متجر على المنصة اللي تختارها', 'تجهيز القالب على هويتك', 'إدخال المنتجات حسب باقتك', 'توصيل بوابة الدفع وتجربة طلب', 'مناطق الشحن وأسعاره', 'إيميلات الطلبات', 'تسليم بيانات الدخول'],
                'excludes' => 'غير مشمول: اشتراك المنصة (سلة أو زد)، والاستضافة والدومين، ورسوم وعمولات بوابة الدفع، وعقود الشحن، وتصوير المنتجات، والمنتجات الزيادة عن الباقة.',
                'faq' => [
                    ['q' => 'سلة ولا زد ولا ووكومرس؟', 'a' => 'سلة وزد سريعين في البداية ومنتشرين في السعودية، وبتدفع لهم اشتراك شهري. ووكومرس من غير رسوم منصة لكن محتاج استضافة. احكيلنا عن محلك ونرشدك قبل ما تدفع.'],
                    ['q' => 'مين بيدفع رسوم البوابة والمنصة؟', 'a' => 'انت، مباشرة للشركات دي. مش جزء من سعرنا.'],
                    ['q' => 'أقدر أضيف منتجات بعدين؟', 'a' => 'أيوه. بنوريك إزاي، وباقة الأعمال فيها ساعة تدريب.'],
                ],
                'cta_title' => 'جاهز تاخد أول طلب أونلاين؟',
            ],
        ];
    }

    private static function customApp(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'custom-web-app-development', 'colors' => ['accent' => '#0891b2', 'ink' => '#06202a'], 'billing' => 'once',
            'period' => ['en' => 'first payment', 'ar' => 'الدفعة الأولى'],
            'name_en' => 'Custom Web App and Dashboard', 'name_ar' => 'تطوير تطبيق ويب ولوحة تحكم',
            'tagline_en' => 'A web application or admin dashboard built for your business with Laravel and React.',
            'tagline_ar' => 'تطبيق ويب أو لوحة تحكم مبنية لشغلك بلارافيل وريأكت.',
            'plans' => [
                $plan('discovery', ['Discovery sprint', 'أسبوع الاكتشاف'], 7500, ['Five working days to define what to build, with a written offer at the end.', 'خمسة أيام عمل لتحديد المطلوب، وبعدها عرض مكتوب.'],
                    ['Workshop on your process and goals', 'List of screens and features', 'Simple sketches of the main screens', 'A written offer with a fixed price and dates', 'The fee is deducted from the build price if you continue with us', '5 working days'],
                    ['ورشة على طريقة شغلك وأهدافك', 'قايمة الشاشات والمميزات', 'رسومات بسيطة للشاشات الرئيسية', 'عرض مكتوب بسعر ثابت ومواعيد', 'الرسوم بتتخصم من سعر التنفيذ لو كملت معانا', '5 أيام عمل']),
                $plan('dashboard-first-half', ['Admin dashboard: first 50%', 'لوحة تحكم: أول 50%'], 30000, ['The first half of a 60,000 EGP admin dashboard. The second half is paid before go-live.', 'النصف الأول من لوحة تحكم بـ 60,000 جنيه. النصف التاني قبل التشغيل.'],
                    ['Total price 60,000 EGP, paid in two halves', 'Login and user roles', 'Up to 6 screens or modules', 'Tables, forms, filters and export to Excel', 'Arabic and English, right-to-left', 'About 6 to 8 weeks', '30 days of fixes after handover'],
                    ['السعر الكلي 60,000 جنيه على دفعتين', 'تسجيل دخول وصلاحيات مستخدمين', 'حتى 6 شاشات أو وحدات', 'جداول وفورمات وفلاتر وتصدير لإكسل', 'عربي وإنجليزي بالاتجاه من اليمين', 'حوالي 6 لـ 8 أسابيع', '30 يوم إصلاحات بعد التسليم']),
                $plan('business-app-first-half', ['Business app: first 50%', 'تطبيق أعمال: أول 50%'], 55000, ['The first half of a 110,000 EGP business web application. The second half is paid before go-live.', 'النصف الأول من تطبيق ويب للأعمال بـ 110,000 جنيه. النصف التاني قبل التشغيل.'],
                    ['Total price 110,000 EGP, paid in two halves', 'Customer area and admin area', 'Online payment or one integration you choose', 'Up to 12 screens or modules', 'Automated tests for the main flows', 'About 10 to 14 weeks', '30 days of fixes after handover'],
                    ['السعر الكلي 110,000 جنيه على دفعتين', 'منطقة عملاء ولوحة إدارة', 'دفع أونلاين أو ربط واحد من اختيارك', 'حتى 12 شاشة أو وحدة', 'اختبارات آلية للمسارات الرئيسية', 'حوالي 10 لـ 14 أسبوع', '30 يوم إصلاحات بعد التسليم']),
            ],
            'en' => [
                'kicker' => 'Custom software for your business',
                'headline' => 'Custom web app and dashboard development, planned before it is priced.',
                'subheadline' => 'Start with a short discovery sprint. You get a written offer with a fixed price. Then we build it in stages, and you see a working version at each one.',
                'who' => 'Businesses that run on spreadsheets and chat messages, or need a customer area, a booking system or an admin dashboard that no ready product fits.',
                'need' => 'A short description of how you work today, who will use the app, and examples of the sheets or tools you use now.',
                'problems_title' => 'When is a ready product not enough?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'Work lives in spreadsheets', 'body' => 'Data is repeated in many files, and nobody sees the whole picture.'],
                    ['icon' => 'users', 'title' => 'Your process is unique', 'body' => 'Ready tools force you to change how you work to fit them.'],
                    ['icon' => 'database', 'title' => 'Systems do not talk to each other', 'body' => 'Orders, customers and payments sit in different places.'],
                ],
                'pillars_title' => 'How the work goes',
                'pillars' => [
                    ['icon' => 'search', 'title' => 'Discovery first', 'body' => 'We define the screens and features before any price is fixed.'],
                    ['icon' => 'check', 'title' => 'Fixed price in writing', 'body' => 'You get an offer with the scope, the price and the dates.'],
                    ['icon' => 'refresh', 'title' => 'Built in stages', 'body' => 'You see a working version at each stage and approve it in writing.'],
                    ['icon' => 'shield', 'title' => 'Tested and yours', 'body' => 'Automated tests on the main flows, and the source code is handed to you after full payment.'],
                ],
                'paragraphs' => [
                    'We build with Laravel and React, the same tools behind our own products. Because every app is different, this service is not sold as one fixed package. You pay for the discovery sprint, then half of the build price to start, and the other half before go-live.',
                    'The two build plans here show common sizes: an admin dashboard and a business application. If your idea is bigger or smaller, the discovery sprint gives you the exact price. Message us on WhatsApp if you want to talk before you pay.',
                ],
                'list' => ['A written scope and a fixed price', 'Staged delivery with a working version each time', 'User login and roles', 'Arabic and English interface', 'Tables, forms, filters and Excel export', 'Source code handed over after full payment', 'Hosting setup help'],
                'excludes' => 'Not included: hosting and domain fees, fees of third-party services such as payment gateways or SMS, content entry, and features outside the written scope (quoted before we build them).',
                'faq' => [
                    ['q' => 'Why do I pay half first?', 'a' => 'The first half starts the work and books the team. The second half is paid before go-live, once you have seen the working app.'],
                    ['q' => 'What if my app is bigger than the plans?', 'a' => 'Start with the discovery sprint. It ends with a written offer for your exact scope.'],
                    ['q' => 'Who owns the code?', 'a' => 'You do, after you have paid in full. Reusable parts that we use in all our projects stay ours, and you get the right to use them in your app.'],
                ],
                'cta_title' => 'Ready to plan the software your business needs?',
            ],
            'ar' => [
                'kicker' => 'برمجيات مخصوصة لشغلك',
                'headline' => 'تطوير تطبيق ويب ولوحة تحكم مخصوصة، بنخطط الأول وبعدين نسعّر.',
                'subheadline' => 'بنبدأ بأسبوع اكتشاف قصير. بتاخد عرض مكتوب بسعر ثابت. وبعدين بنبني على مراحل، وفي كل مرحلة بتشوف نسخة شغالة.',
                'who' => 'الشركات اللي شغلها على شيتات ورسايل، أو محتاجة منطقة عملاء أو نظام حجز أو لوحة تحكم مفيش منتج جاهز مناسبها.',
                'need' => 'وصف قصير لطريقة شغلك دلوقتي، ومين هيستخدم التطبيق، وأمثلة من الشيتات أو الأدوات اللي بتستخدمها.',
                'problems_title' => 'إمتى المنتج الجاهز مش كفاية؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'الشغل على شيتات', 'body' => 'البيانات متكررة في ملفات كتير ومحدش شايف الصورة كاملة.'],
                    ['icon' => 'users', 'title' => 'طريقة شغلك مختلفة', 'body' => 'الأدوات الجاهزة بتجبرك تغيّر شغلك عشان يناسبها.'],
                    ['icon' => 'database', 'title' => 'الأنظمة مش بتكلم بعض', 'body' => 'الطلبات والعملاء والمدفوعات في أماكن مختلفة.'],
                ],
                'pillars_title' => 'الشغل بيمشي إزاي',
                'pillars' => [
                    ['icon' => 'search', 'title' => 'اكتشاف الأول', 'body' => 'بنحدد الشاشات والمميزات قبل ما نثبّت أي سعر.'],
                    ['icon' => 'check', 'title' => 'سعر ثابت مكتوب', 'body' => 'بتاخد عرض فيه النطاق والسعر والمواعيد.'],
                    ['icon' => 'refresh', 'title' => 'بناء على مراحل', 'body' => 'في كل مرحلة بتشوف نسخة شغالة وبتوافق عليها كتابة.'],
                    ['icon' => 'shield', 'title' => 'مختبر وملكك', 'body' => 'اختبارات آلية للمسارات الرئيسية، والكود المصدري بيتسلمك بعد الدفع الكامل.'],
                ],
                'paragraphs' => [
                    'بنبني بلارافيل وريأكت، نفس الأدوات اللي ورا منتجاتنا. وعشان كل تطبيق مختلف، الخدمة دي مش بتتباع كباقة ثابتة واحدة. بتدفع أسبوع الاكتشاف، وبعدين نص سعر التنفيذ للبداية، والنص التاني قبل التشغيل.',
                    'باقتي التنفيذ هنا بيوضحوا مقاسين شائعين: لوحة تحكم وتطبيق أعمال. لو فكرتك أكبر أو أصغر، أسبوع الاكتشاف بيديك السعر بالظبط. كلمنا على الواتساب لو عايز تتكلم قبل ما تدفع.'],
                'list' => ['نطاق شغل مكتوب وسعر ثابت', 'تسليم على مراحل بنسخة شغالة كل مرة', 'تسجيل دخول وصلاحيات', 'واجهة عربي وإنجليزي', 'جداول وفورمات وفلاتر وتصدير لإكسل', 'تسليم الكود المصدري بعد الدفع الكامل', 'مساعدة في تجهيز الاستضافة'],
                'excludes' => 'غير مشمول: رسوم الاستضافة والدومين، ورسوم خدمات الأطراف الأخرى زي بوابات الدفع والرسايل، وإدخال المحتوى، وأي مميزات برة النطاق المكتوب (بنقولك سعرها قبل ما نبنيها).',
                'faq' => [
                    ['q' => 'ليه بدفع النص الأول؟', 'a' => 'النص الأول بيبدأ الشغل ويحجز الفريق. والنص التاني بيتدفع قبل التشغيل، بعد ما تشوف التطبيق شغال.'],
                    ['q' => 'لو تطبيقي أكبر من الباقات؟', 'a' => 'ابدأ بأسبوع الاكتشاف. بينتهي بعرض مكتوب على نطاقك بالظبط.'],
                    ['q' => 'مين يملك الكود؟', 'a' => 'انت، بعد ما تدفع كامل المبلغ. الأجزاء القابلة لإعادة الاستخدام اللي بنستخدمها في كل مشاريعنا بتفضل ملكنا، وليك حق استخدامها في تطبيقك.'],
                ],
                'cta_title' => 'جاهز تخطط البرنامج اللي شغلك محتاجه؟',
            ],
        ];
    }

    private static function websiteCare(): array
    {
        $plan = [GeneralServicesCommon::class, 'plan'];

        return [
            'slug' => 'website-care-plans', 'colors' => ['accent' => '#475569', 'ink' => '#0f172a'], 'billing' => 'monthly', 'period' => self::MONTHLY,
            'name_en' => 'Website Care Plan', 'name_ar' => 'صيانة ومتابعة المواقع',
            'tagline_en' => 'Monthly updates, backups and monitoring so your website stays safe and online.',
            'tagline_ar' => 'تحديثات ونسخ احتياطي ومراقبة شهرية عشان موقعك يفضل آمن وشغال.',
            'plans' => [
                $plan('starter', ['Starter', 'الأساسية'], 1000, ['The basics that keep a website alive.', 'الأساسيات اللي بتخلي الموقع عايش.'],
                    ['WordPress core, theme and plugin updates', 'Weekly backups kept for 30 days', 'Uptime monitoring with an alert to us', 'End of month report'],
                    ['تحديثات ووردبريس والقالب والإضافات', 'نسخ احتياطي أسبوعي محفوظ 30 يوم', 'مراقبة تشغيل الموقع بتنبيه لينا', 'تقرير آخر الشهر']),
                $plan('business', ['Business', 'الأعمال'], 3000, ['Updates, security checks and two hours of small edits.', 'تحديثات وفحص أمان وساعتين تعديلات صغيرة.'],
                    ['Everything in Starter', 'Daily backups kept for 30 days', 'Security scan and cleanup of simple problems', 'Up to 2 hours of small edits each month', 'Replies within 1 working day'],
                    ['كل حاجة في الأساسية', 'نسخ احتياطي يومي محفوظ 30 يوم', 'فحص أمان وإصلاح المشاكل البسيطة', 'حتى ساعتين تعديلات صغيرة كل شهر', 'رد خلال يوم عمل']),
                $plan('premium', ['Premium', 'المتميزة'], 6500, ['Priority replies, a monthly speed check and four hours of edits.', 'ردود بأولوية وفحص سرعة شهري و4 ساعات تعديلات.'],
                    ['Everything in Business', 'Monthly speed and Core Web Vitals check with fixes', 'Up to 4 hours of edits each month', 'Replies within 4 working hours', 'A short monthly call if you want one'],
                    ['كل حاجة في الأعمال', 'فحص شهري للسرعة وCore Web Vitals مع الإصلاح', 'حتى 4 ساعات تعديلات كل شهر', 'رد خلال 4 ساعات عمل', 'مكالمة شهرية قصيرة لو حبيت']),
            ],
            'en' => [
                'kicker' => 'WordPress website maintenance',
                'headline' => 'Website maintenance and care plans, paid month by month.',
                'subheadline' => 'We keep your website updated, backed up and watched, and fix small things before they become big ones.',
                'who' => 'Owners of WordPress websites who do not have a developer on call, or whose website was built a while ago and never updated.',
                'need' => 'The address of your website and a login with admin rights. We also ask where your hosting is.',
                'problems_title' => 'What goes wrong when nobody looks after a website?',
                'problems' => [
                    ['icon' => 'shield', 'title' => 'Old plugins get hacked', 'body' => 'Out of date plugins are a common way for sites to be attacked.'],
                    ['icon' => 'database', 'title' => 'No backup when something breaks', 'body' => 'An update or a mistake can break the site, and without a backup it is hard to return.'],
                    ['icon' => 'clock', 'title' => 'You notice the site is down too late', 'body' => 'Customers find out before you do.'],
                ],
                'pillars_title' => 'What we do every month',
                'pillars' => [
                    ['icon' => 'refresh', 'title' => 'Updates', 'body' => 'WordPress, the theme and the plugins, tested before and after.'],
                    ['icon' => 'database', 'title' => 'Backups', 'body' => 'Copies you can restore from, kept for 30 days.'],
                    ['icon' => 'search', 'title' => 'Monitoring', 'body' => 'We are alerted when your site stops answering.'],
                    ['icon' => 'wrench', 'title' => 'Small edits', 'body' => 'Text, photo and small layout changes, as hours in your plan.'],
                ],
                'paragraphs' => [
                    'Care plans are for sites on WordPress. You pay month by month and can stop before the next month starts. Each month you get a short report of what we updated and what we found.',
                    'We cannot promise a site will never go down or never be attacked. We reduce the common risks, keep backups and react quickly when something breaks.',
                ],
                'list' => ['Updates of WordPress, themes and plugins', 'Backups as listed in your plan', 'Uptime monitoring', 'Security scan in Business and Premium', 'Hours of small edits in Business and Premium', 'Monthly report'],
                'excludes' => 'Not included: hosting and domain fees, paid plugin licenses, new pages or new features (quoted separately), recovery of a site that was already badly hacked before you joined (quoted after we look).',
                'faq' => [
                    ['q' => 'Do you take over a site someone else built?', 'a' => 'Yes, if it runs on WordPress. We look at it first and tell you honestly if it needs repair before the plan starts.'],
                    ['q' => 'What happens to unused edit hours?', 'a' => 'They do not carry over to the next month.'],
                    ['q' => 'Is hosting included?', 'a' => 'No. You keep your hosting account with your hosting company, and we work inside it.'],
                ],
                'cta_title' => 'Ready to stop worrying about your website?',
            ],
            'ar' => [
                'kicker' => 'صيانة مواقع ووردبريس',
                'headline' => 'صيانة مواقع ووردبريس وباقات متابعة شهرية.',
                'subheadline' => 'بنحافظ على موقعك محدّث ومتأخد له نسخة احتياطية ومراقب، ونصلح الحاجات الصغيرة قبل ما تكبر.',
                'who' => 'أصحاب مواقع ووردبريس اللي معندهمش مبرمج متاح، أو موقعهم اتبني من فترة ومتحدثش.',
                'need' => 'رابط موقعك ودخول بصلاحية مدير. وبنسأل كمان فين الاستضافة.',
                'problems_title' => 'إيه اللي بيحصل لما محدش بيهتم بالموقع؟',
                'problems' => [
                    ['icon' => 'shield', 'title' => 'الإضافات القديمة بتتهكر', 'body' => 'الإضافات القديمة طريقة شائعة لاختراق المواقع.'],
                    ['icon' => 'database', 'title' => 'مفيش نسخة احتياطية وقت العطل', 'body' => 'تحديث أو غلطة ممكن يبوظ الموقع، ومن غير نسخة الرجوع صعب.'],
                    ['icon' => 'clock', 'title' => 'بتعرف إن الموقع واقع متأخر', 'body' => 'العملاء بيعرفوا قبلك.'],
                ],
                'pillars_title' => 'اللي بنعمله كل شهر',
                'pillars' => [
                    ['icon' => 'refresh', 'title' => 'تحديثات', 'body' => 'ووردبريس والقالب والإضافات، مع تجربة قبل وبعد.'],
                    ['icon' => 'database', 'title' => 'نسخ احتياطي', 'body' => 'نسخ تقدر ترجع منها، محفوظة 30 يوم.'],
                    ['icon' => 'search', 'title' => 'مراقبة', 'body' => 'بيجيلنا تنبيه لما موقعك يبطل يرد.'],
                    ['icon' => 'wrench', 'title' => 'تعديلات صغيرة', 'body' => 'نص وصورة وتغييرات بسيطة في التصميم، كساعات في باقتك.'],
                ],
                'paragraphs' => [
                    'باقات الصيانة للمواقع على ووردبريس. بتدفع شهر بشهر وتقدر توقف قبل الشهر الجاي. كل شهر بتاخد تقرير قصير بإيه اللي حدثناه وإيه اللي لقيناه.',
                    'مش بنقدر نوعد إن الموقع عمره ما يقع أو يتهاجم. بنقلل المخاطر الشائعة، وبنحتفظ بنسخ احتياطية، وبنتحرك بسرعة لما حاجة تبوظ.',
                ],
                'list' => ['تحديث ووردبريس والقوالب والإضافات', 'نسخ احتياطي حسب باقتك', 'مراقبة تشغيل الموقع', 'فحص أمان في باقتي الأعمال والمتميزة', 'ساعات تعديلات صغيرة في الأعمال والمتميزة', 'تقرير شهري'],
                'excludes' => 'غير مشمول: رسوم الاستضافة والدومين، وتراخيص الإضافات المدفوعة، والصفحات أو المميزات الجديدة (بنسعّرها لوحدها)، وإصلاح موقع كان مخترق بشدة قبل ما تشترك (بنسعّره بعد ما نشوفه).',
                'faq' => [
                    ['q' => 'بتستلموا موقع حد تاني عمله؟', 'a' => 'أيوه لو على ووردبريس. بنشوفه الأول ونقولك بصراحة لو محتاج إصلاح قبل ما الباقة تبدأ.'],
                    ['q' => 'ساعات التعديل اللي مااستخدمتهاش؟', 'a' => 'مش بتتنقل للشهر الجاي.'],
                    ['q' => 'الاستضافة مشمولة؟', 'a' => 'لا. حساب الاستضافة بيفضل معاك عند شركة الاستضافة، وإحنا بنشتغل جواه.'],
                ],
                'cta_title' => 'جاهز تبطل تقلق على موقعك؟',
            ],
        ];
    }
}
