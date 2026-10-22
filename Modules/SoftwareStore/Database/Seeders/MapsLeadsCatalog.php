<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Maps Leads Pro: a desktop program that reads public business data from Google Maps results.
 * The facts come from the program README. One small live search worked, but Google changes its pages
 * and its terms limit automated use, so the copy promises no counts and names the risks.
 */
class MapsLeadsCatalog
{
    public static function program(): array
    {
        return [
            'slug' => 'maps-leads-pro',
            'sort_order' => 5,
            'image_path' => 'images/software/maps-leads-pro.svg',
            'name_en' => 'Maps Leads Pro',
            'name_ar' => 'مابس ليدز برو',
            'tagline_en' => 'Turn a Google Maps search into a clean list of businesses with phone, website and address.',
            'tagline_ar' => 'حوّل بحث خرائط جوجل لقائمة نضيفة بالأنشطة التجارية بالتليفون والموقع والعنوان.',
            'description_en' => 'Search a keyword and a place on Google Maps, read each business, remove duplicates and export to Excel or CSV.',
            'description_ar' => 'ابحث بكلمة ومكان في خرائط جوجل، واقرأ كل نشاط، وشيل المكرر، وصدّر لإكسل أو CSV.',
            'price' => 1500,
            'currency' => 'EGP',
            'theme' => [
                'colors' => ['accent' => '#16a34a', 'ink' => '#07210f'],
                'sections' => ['problems', 'stat', 'showcase', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery'],
            ],
            'landing' => ['ar' => self::arabic(), 'en' => self::english()],
        ];
    }

    private static function arabic(): array
    {
        return [
            'kicker' => 'قوائم أنشطة تجارية',
            'headline' => 'اكتب الكلمة والمكان، وخد قايمة الأنشطة بتليفوناتها من غير ما تنسخ ولا مكان.',
            'subheadline' => 'البرنامج بيفتح خرائط جوجل، بيدوّر، وبيفتح كل نشاط ويقرا اسمه وعنوانه وتليفونه وموقعه وتقييمه، وبيشيل المكرر، وتصدّر الكل لإكسل.',
            'overview' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                ['title' => 'مناسب لمين؟', 'body' => 'المسوقين والمبيعات اللي بيدوّروا على أنشطة في مدينة معينة (عيادات، مطاعم، معارض) ومحتاجين قايمة منظمة يشتغلوا عليها.'],
                ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11 (64 بت) ومعاه متصفح Chrome أو Edge، واتصال إنترنت.'],
                ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => 'بعد الدفع بيتعمل حسابك والترخيص تلقائي، ولو فتحت صفحة الشراء من نافذة البرنامج بيتفعل جهازك فورا. وبتوصلك رسالة واتساب جاهزة برقم طلبك.'],
            ]],
            'problems' => ['kicker' => 'مشاكل جمع الأنشطة', 'title' => 'ليه جمع قايمة أنشطة بياخد وقت؟', 'items' => [
                ['icon' => 'clock', 'title' => 'نسخ يدوي ساعات', 'body' => 'تفتح كل نشاط وتنسخ التليفون والعنوان واحد واحد.'],
                ['icon' => 'search', 'title' => 'نتايج مكررة', 'body' => 'نفس النشاط بيظهر أكتر من مرة وتدوخ في الترتيب.'],
                ['icon' => 'check', 'title' => 'بيانات ناقصة', 'body' => 'مفيش طريقة تعرف مين له تليفون ومين له موقع إلا لما تفتح كل واحد.'],
            ]],
            'stat' => ['kicker' => 'حد واضح', 'value' => '500', 'title' => 'أقصى عدد نشاط في البحث الواحد', 'body' => 'الافتراضي 120 نشاط، وتقدر ترفعه لحد 500. البرنامج بيشتغل ببطء عن قصد ومعاه زرار إيقاف، والنتايج اللي اتقرت قبل الإيقاف بتفضل محفوظة.'],
            'showcase' => ['kicker' => 'شاشات البرنامج', 'title' => 'من البحث للتصدير', 'items' => [
                ['title' => 'بحث بسيط', 'body' => 'كلمة ومكان وحد أقصى، وزرار ابدأ.'],
                ['title' => 'جدول نتايج', 'body' => 'فلترة بالاسم، ومن له تليفون، ومن له موقع.'],
                ['title' => 'سجل وتصدير', 'body' => 'كل بحث محفوظ تقدر تفتحه تاني وتصدّره إكسل أو CSV.'],
            ]],
            'pillars' => ['kicker' => 'قدرات البرنامج', 'title' => 'أربع ركائز لقايمة نضيفة', 'items' => [
                ['icon' => 'search', 'title' => 'قراءة كل نشاط', 'body' => 'الاسم والتصنيف والعنوان والتليفون والموقع والتقييم وعدد المراجعات ورابط الخريطة.'],
                ['icon' => 'refresh', 'title' => 'شيل المكرر', 'body' => 'النشاط بيتحسب مرة واحدة بالتليفون، أو بالاسم والعنوان لو مفيش تليفون.'],
                ['icon' => 'phone', 'title' => 'تلميح موبايل', 'body' => 'علامة (EG أو SA أو AE) جنب الرقم لو شكله موبايل. ده فحص لشكل الرقم بس، مش فحص واتساب.'],
                ['icon' => 'shield', 'title' => 'شغل مهذب', 'body' => 'انتظار عشوائي بين الأماكن، ولو جوجل طلب فحص بشري البحث بيقف ويقولك بوضوح.'],
            ]],
            'video' => ['kicker' => 'شوف بنفسك', 'title' => 'جولة سريعة جوه البرنامج'],
            'steps' => ['kicker' => 'خطوات الاستخدام', 'title' => 'من أول فتح للتصدير في 4 خطوات', 'items' => [
                ['title' => 'اكتب البحث', 'body' => 'الكلمة (مثلا طبيب أسنان) والمكان (مثلا القاهرة) والحد الأقصى.'],
                ['title' => 'ابدأ وسيبه', 'body' => 'هتفتح نافذة Chrome، متقفلهاش أثناء الشغل.'],
                ['title' => 'فلتر النتايج', 'body' => 'اعرض اللي له تليفون أو موقع بس.'],
                ['title' => 'صدّر', 'body' => 'ملف إكسل أو CSV بيفتح عربي سليم.'],
            ]],
            'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد البرنامج', 'before_title' => 'الجمع اليدوي', 'after_title' => 'مع مابس ليدز برو',
                'before' => [['b' => 'الشغل', 't' => 'فتح كل نشاط ونسخ بياناته.'], ['b' => 'التكرار', 't' => 'نفس النشاط أكتر من مرة.'], ['b' => 'الملف', 't' => 'جدول بتبنيه بإيدك.']],
                'after' => [['b' => 'الشغل', 't' => 'بحث واحد والبرنامج بيقرا الباقي.'], ['b' => 'التكرار', 't' => 'المكرر بيتشال لوحده.'], ['b' => 'الملف', 't' => 'إكسل أو CSV جاهز.']]],
            'metrics' => ['kicker' => 'ملخص', 'title' => 'البرنامج في أرقام', 'items' => [
                ['value' => '120', 'label' => 'نشاط افتراضي في البحث'], ['value' => '500', 'label' => 'أقصى حد للبحث'], ['value' => '2', 'label' => 'صيغتين تصدير: Excel و CSV']]],
            'gallery' => ['title' => 'صور من البرنامج'],
            'details' => ['title' => 'تفاصيل البرنامج', 'paragraphs' => [
                'مابس ليدز برو برنامج ويندوز بيشغّل نافذة Chrome أو Edge حقيقية، يفتح خرائط جوجل، ويدوّر على الكلمة والمكان اللي كتبتهم، ويمرر القايمة، وبعدين يفتح كل نشاط ويقرا بياناته العامة. مفيش حساب جوجل ولا API.',
                'النتايج بتتحفظ على جهازك في قاعدة بيانات SQLite، وتقدر ترجع لأي بحث قديم. الواجهة عربي وإنجليزي، والتصدير بيحافظ على الحروف العربية. البيانات اللي بتتقرا بيانات عامة لأنشطة تجارية، ومسؤوليتك تستخدمها حسب قوانين بلدك.',
            ], 'list_title' => 'اللي هتحصل عليه', 'list' => [
                'بحث بكلمة ومكان وحد أقصى (الافتراضي 120 وأقصى حد 500)', 'قراءة الاسم والتصنيف والعنوان والتليفون والموقع والتقييم والمراجعات ورابط الخريطة وساعات العمل',
                'شيل المكرر تلقائي', 'فلترة بالاسم ومن له تليفون ومن له موقع', 'تلميح شكل موبايل لأرقام مصر والسعودية والإمارات',
                'تصدير Excel و CSV', 'سجل بحث تقدر ترجعله وتحذفه', 'زرار إيقاف يحفظ اللي اتقرا', 'واجهة عربي وإنجليزي', 'سجل أنشطة وتحديث تلقائي',
            ]],
            'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                ['q' => 'هل ده مسموح من جوجل؟', 'a' => 'شروط خرائط جوجل بتقيّد الاستخراج الآلي للبيانات. البرنامج بيشتغل ببطء ومن غير أي تحايل، لكن الاستخدام على مسؤوليتك، وجوجل ممكن تعمل فحص أو تحد من الوصول. راجع الشروط قبل الاستخدام التجاري.'],
                ['q' => 'هل عدد النتايج مضمون؟', 'a' => 'لا. عدد الأنشطة اللي بتظهر بيحدده جوجل لكل بحث، فممكن تجيب أقل من الحد اللي كتبته.'],
                ['q' => 'لو جوجل غيّر صفحاته؟', 'a' => 'البرنامج بيعتمد على عناصر صفحة خرائط جوجل، وممكن تتغير. لو حصل ده بنحدّث البرنامج والتحديث بيوصلك تلقائي.'],
                ['q' => 'لو ظهر فحص بشري؟', 'a' => 'البحث بيقف ويقولك. حل الفحص بنفسك في النافذة أو استنى شوية وجرّب بحد أقل. البرنامج مش بيحل الفحص ولا بيتحايل عليه.'],
                ['q' => 'أستخدم الأرقام في رسايل؟', 'a' => 'الأرقام والبيانات بيانات شخصية أو تجارية. استخدمها بس مع اللي وافق أو في إطار القانون عندك (في مصر قانون حماية البيانات الشخصية)، واعمل طريقة واضحة لإلغاء التواصل.'],
                ['q' => 'بيشتغل على أنهي جهاز؟', 'a' => 'ويندوز 10 أو 11 (64 بت) ومعاه Chrome أو Edge.'],
                ['q' => 'الترخيص مرتبط بإيه؟', 'a' => 'ترخيص سنوي على جهاز واحد، ويتفعل تلقائي بعد الدفع.'],
            ]],
            'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز تجمع قايمة الأنشطة بنظام؟', 'body' => 'ادفع مرة واحدة في السنة واستلم البرنامج. بعد الدفع هنكلمك على الواتساب نفعّل معاك النسخة.', 'button' => 'اشتري مابس ليدز برو'],
        ];
    }

    private static function english(): array
    {
        return [
            'kicker' => 'Business lead lists',
            'headline' => 'Type a keyword and a place, and get a list of businesses with phones, without copying and pasting.',
            'subheadline' => 'The program opens Google Maps, searches, opens each business and reads its name, address, phone, website and rating, removes duplicates, and exports everything to Excel.',
            'overview' => ['title' => 'Understand everything in one minute', 'items' => [
                ['title' => 'Who is it for?', 'body' => 'Marketers and sales teams who look for businesses in a city (clinics, restaurants, showrooms) and need an organized list to work on.'],
                ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 (64-bit) computer with the Chrome or Edge browser, and an internet connection.'],
                ['title' => 'What happens after payment?', 'body' => 'After payment your account and license are created automatically, and if you opened the buy page from the program window your device is activated right away. You also get a ready WhatsApp message with your order number.'],
            ]],
            'problems' => ['kicker' => 'Problems of collecting businesses', 'title' => 'Why does building a business list take so long?', 'items' => [
                ['icon' => 'clock', 'title' => 'Hours of copying', 'body' => 'You open each business and copy the phone and address one by one.'],
                ['icon' => 'search', 'title' => 'Repeated results', 'body' => 'The same business shows up more than once and the order gets messy.'],
                ['icon' => 'check', 'title' => 'Missing data', 'body' => 'You cannot tell who has a phone and who has a website until you open each one.'],
            ]],
            'stat' => ['kicker' => 'A clear limit', 'value' => '500', 'title' => 'Most businesses in one search', 'body' => 'The default is 120 businesses and you can raise it to 500. The program works slowly on purpose and has a Stop button. Results read before you stop are kept.'],
            'showcase' => ['kicker' => 'Program screens', 'title' => 'From search to export', 'items' => [
                ['title' => 'A simple search', 'body' => 'A keyword, a place, a limit, and a Start button.'],
                ['title' => 'A results table', 'body' => 'Filter by name, by phone, and by website.'],
                ['title' => 'History and export', 'body' => 'Every search is saved. Open it again and export to Excel or CSV.'],
            ]],
            'pillars' => ['kicker' => 'What the program can do', 'title' => 'Four pillars of a clean list', 'items' => [
                ['icon' => 'search', 'title' => 'Reads every business', 'body' => 'Name, category, address, phone, website, rating, review count and the map link.'],
                ['icon' => 'refresh', 'title' => 'Removes duplicates', 'body' => 'A business counts once, by phone, or by name and address when there is no phone.'],
                ['icon' => 'phone', 'title' => 'Mobile format hint', 'body' => 'A mark (EG, SA or AE) next to a number that looks like a mobile. It only checks the number format. It is not a WhatsApp lookup.'],
                ['icon' => 'shield', 'title' => 'Polite working', 'body' => 'Random waits between places. If Google asks for a human check, the search stops and tells you clearly.'],
            ]],
            'video' => ['kicker' => 'See it yourself', 'title' => 'A quick tour inside the program'],
            'steps' => ['kicker' => 'How to use it', 'title' => 'From first launch to export in 4 steps', 'items' => [
                ['title' => 'Write the search', 'body' => 'The keyword (for example dentist), the place (for example Cairo) and the limit.'],
                ['title' => 'Start and leave it', 'body' => 'A Chrome window opens. Do not close it while the program works.'],
                ['title' => 'Filter the results', 'body' => 'Show only the businesses with a phone or a website.'],
                ['title' => 'Export', 'body' => 'An Excel or CSV file that opens with Arabic text intact.'],
            ]],
            'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the program', 'before_title' => 'Collecting by hand', 'after_title' => 'With Maps Leads Pro',
                'before' => [['b' => 'Work', 't' => 'Open each business and copy its data.'], ['b' => 'Repeats', 't' => 'The same business more than once.'], ['b' => 'The file', 't' => 'A table you build yourself.']],
                'after' => [['b' => 'Work', 't' => 'One search and the program reads the rest.'], ['b' => 'Repeats', 't' => 'Duplicates are removed by itself.'], ['b' => 'The file', 't' => 'Excel or CSV, ready.']]],
            'metrics' => ['kicker' => 'Summary', 'title' => 'The program in numbers', 'items' => [
                ['value' => '120', 'label' => 'Businesses per search by default'], ['value' => '500', 'label' => 'Highest limit per search'], ['value' => '2', 'label' => 'Export formats: Excel and CSV']]],
            'gallery' => ['title' => 'Pictures of the program'],
            'details' => ['title' => 'Program details', 'paragraphs' => [
                'Maps Leads Pro is a Windows program that drives a real Chrome or Edge window. It opens Google Maps, searches for your keyword and place, scrolls the list, then opens each business and reads its public data. It needs no Google account and no API.',
                'Results are saved on your own computer in an SQLite database, and you can return to any old search. The interface is Arabic and English, and exports keep Arabic letters intact. The data it reads is public business data, and you are responsible for using it under the laws of your country.',
            ], 'list_title' => 'What you get', 'list' => [
                'A search by keyword, place and limit (default 120, highest 500)', 'Reads name, category, address, phone, website, rating, reviews, map link and opening hours',
                'Automatic duplicate removal', 'Filter by name, with phone, with website', 'Mobile format hint for Egypt, Saudi Arabia and the UAE',
                'Export to Excel and CSV', 'A search history you can reopen and delete', 'A Stop button that keeps what was read', 'Arabic and English interface', 'An activity log and automatic updates',
            ]],
            'faq' => ['title' => 'Common questions', 'items' => [
                ['q' => 'Does Google allow this?', 'a' => 'The terms of Google Maps restrict automated extraction of data. The program works slowly and uses no evasion, but use is at your own risk, and Google may run a check or limit access. Read the terms before commercial use.'],
                ['q' => 'Is the number of results guaranteed?', 'a' => 'No. Google decides how many businesses appear for each search, so you may get fewer than the limit you typed.'],
                ['q' => 'What if Google changes its pages?', 'a' => 'The program relies on elements of the Google Maps page, and they can change. If that happens we update the program, and the update reaches you automatically.'],
                ['q' => 'What if a human check appears?', 'a' => 'The search stops and tells you. Solve the check yourself in the window, or wait and try again with a lower limit. The program does not solve or bypass the check.'],
                ['q' => 'Can I message the numbers?', 'a' => 'Phones and business data can be personal data. Use them only with people who agreed, or as your local law allows (in Egypt, the Personal Data Protection Law), and give a clear way to opt out.'],
                ['q' => 'Which computers does it run on?', 'a' => 'Windows 10 or 11 (64-bit) with Chrome or Edge.'],
                ['q' => 'What is the license tied to?', 'a' => 'A yearly license for one device, activated automatically after payment.'],
            ]],
            'cta' => ['kicker' => 'Start today', 'title' => 'Ready to build your business list in an organized way?', 'body' => 'Pay once a year and get the program. After payment we message you on WhatsApp to activate your copy.', 'button' => 'Buy Maps Leads Pro'],
        ];
    }
}
