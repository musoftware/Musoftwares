<?php

namespace Modules\SoftwareStore\Database\Seeders;

use App\Models\SerialSoftware;
use Illuminate\Database\Seeder;
use Modules\SoftwareStore\Models\SoftwareProduct;

class SoftwareProductSeeder extends Seeder
{
    /** Name each desktop program sends as program_name when it checks in. */
    private const SERIAL_NAMES = [
        'gridreader' => 'GridReader',
        'email-sender-pro' => 'Email Sender Pro',
        'whatsapp-bulk-sender' => 'WhatsApp Bulker',
        'inbox-sender-pro' => 'Inbox Sender Pro',
        'maps-leads-pro' => 'Maps Leads Pro',
        'email-finder-pro' => 'Email Finder Pro',
        'telegram-sender-pro' => 'Telegram Sender Pro',
        'shop-manager-pro' => 'Shop Manager Pro',
    ];

    /** Addresses that were renamed. The old row is renamed in place so orders and media stay linked. */
    private const RENAMED = [
        'amc-tasks' => ['slug' => 'marketing-suite', 'name_en' => 'Musoftware Marketing Suite', 'name_ar' => 'حزمة موسوفت للتسويق'],
        'audfinder' => ['slug' => 'gridreader', 'name_en' => 'GridReader', 'name_ar' => 'جريد ريدر'],
    ];

    /**
     * New programs start hidden with price 0. Set the price in the admin page, then turn them on.
     * Re-running only adds what is new (missing page text, missing blocks). It never overwrites
     * prices, visibility, uploaded media or text that the admin already edited.
     */
    public function run(): void
    {
        $this->renameLegacyProducts();

        foreach ($this->programs() as $program) {
            $program['landing'] = $this->withExtras($program['slug'], $program['landing']);
            $program['theme']['sections'] = $this->sectionsWithNewBlocks($program['theme']['sections'], []);

            $product = SoftwareProduct::firstOrNew(['slug' => $program['slug']]);
            $product->exists ? $this->addMissing($product, $program) : $this->createFrom($product, $program);

            $serialName = self::SERIAL_NAMES[$program['slug']] ?? null;
            $product->serial_software_id ??= $serialName ? SerialSoftware::where('name', $serialName)->value('id') : null;
            $product->save();
        }
    }

    private function renameLegacyProducts(): void
    {
        SerialSoftware::where('name', 'AudFinder')->update(['name' => 'GridReader']);
        foreach (self::RENAMED as $oldSlug => $new) {
            $old = SoftwareProduct::where('slug', $oldSlug)->first();
            if ($old && ! SoftwareProduct::where('slug', $new['slug'])->exists()) {
                $old->update($new);
                if ($oldSlug === 'audfinder') {
                    $old->update([
                        'image_path' => 'images/software/gridreader.png',
                        'landing' => null,
                        'tagline_en' => null,
                        'tagline_ar' => null,
                        'description_en' => null,
                        'description_ar' => null,
                    ]);
                }
            }
        }
    }

    private function createFrom(SoftwareProduct $product, array $program): void
    {
        $product->fill(['price' => 0, 'currency' => 'EGP', 'is_active' => false]);
        $product->fill($program);
    }

    private function addMissing(SoftwareProduct $product, array $program): void
    {
        $product->landing = $this->addMissingCopy($product->landing ?? [], $program['landing']);
        $theme = $product->theme ?? [];
        $theme['sections'] = $this->sectionsWithNewBlocks($theme['sections'] ?? [], $program['theme']['sections']);
        $product->theme = $theme;
    }

    /** Adds page text blocks that the product does not have yet, per language. */
    private function addMissingCopy(array $existing, array $seed): array
    {
        foreach ($seed as $lang => $blocks) {
            foreach ($blocks as $key => $value) {
                $existing[$lang][$key] ??= $value;
            }
        }

        return $existing;
    }

    /** Overview goes first, details and questions last. Blocks already chosen keep their place. */
    private function sectionsWithNewBlocks(array $current, array $seeded): array
    {
        $sections = $current ?: $seeded;
        $sections = array_values(array_diff($sections, ['overview', 'details', 'faq']));

        return array_merge(['overview'], $sections, ['details', 'faq']);
    }

    private function withExtras(string $slug, array $landing): array
    {
        foreach (['ar', 'en'] as $lang) {
            $landing[$lang] = ($landing[$lang] ?? []) + ProductExtras::for($slug, $lang);
        }

        return $landing;
    }

    private function programs(): array
    {
        return [$this->gridReader(), $this->emailSender(), $this->whatsappSender(), $this->marketingSuite(), $this->whatsappOfficial(), InboxSenderCatalog::program(), MapsLeadsCatalog::program(), EmailFinderCatalog::program(), TelegramSenderCatalog::program(), ShopManagerCatalog::program(), ...SystemsCatalog::programs(), ...GeneralServicesCatalog::programs()];
    }

    private function gridReader(): array
    {
        return [
            'slug' => 'gridreader',
            'sort_order' => 1,
            'image_path' => 'images/software/gridreader.png',
            'name_en' => 'GridReader',
            'name_ar' => 'جريد ريدر',
            'tagline_en' => 'Lightning-fast viewer and query engine for CSV, Excel, JSON, SQLite, PostgreSQL and MySQL.',
            'tagline_ar' => 'مستعرض ومحرك استعلام فائق السرعة لملفات CSV و Excel و JSON وقواعد بيانات SQLite و PostgreSQL و MySQL.',
            'description_en' => 'Open massive tabular datasets in milliseconds. Instant virtual scrolling, multi-column search, SQLite index acceleration, list matching, and direct SQL querying with zero lag.',
            'description_ar' => 'افتح أضخم ملفات البيانات والجداول في أجزاء من الثانية. تصفح افتراضي فائق السرعة، بحث متعدد الأعمدة، تسريع بالفهارس، مطابقة قوائم، واستعلام SQL مباشر بدون أي تهنيج.',
            'theme' => [
                'colors' => ['accent' => '#0f6cbd', 'ink' => '#0f172a'],
                'sections' => ['problems', 'showcase', 'stat', 'steps', 'video', 'pillars', 'before_after', 'metrics', 'gallery'],
            ],
            'landing' => [
                'ar' => [
                    'kicker' => 'أدوات إدارة وتحليل البيانات الضخمة',
                    'headline' => 'الملفات الضخمة اللي بتعلّق برامج الجداول؟ افتحها وتصفحها في لحظة.',
                    'subheadline' => 'استعرض وابحث في ملفات CSV و Excel و JSON وقواعد بيانات SQLite و PostgreSQL و MySQL بملايين الصفوف بدون بطء ولا استهلاك للذاكرة. كل البيانات بتفضل على جهازك.',
                    'problems' => ['kicker' => 'المعاناة مع البيانات الضخمة', 'title' => 'ليه التعامل مع الجداول الكبيرة متعب؟', 'items' => [
                        ['icon' => 'alert-triangle', 'title' => 'تهنيج برامج الجداول التقليدية', 'body' => 'الملفات اللي حجمها مئات الميجابايت بتعلق الإكسل وتجبرك تقفل البرنامج.'],
                        ['icon' => 'clock', 'title' => 'بطء شديد في البحث والفلترة', 'body' => 'البحث عن سجل وسط ملايين الصفوف بياخد دقايق طويلة وبيضيع وقتك.'],
                        ['icon' => 'database', 'title' => 'صعوبة استعراض قواعد البيانات الكبيرة', 'body' => 'تحتاج برامج إدارة قواعد بيانات معقدة بس عشان تبص على جدول أو تراجع بيانات.'],
                    ]],
                    'showcase' => ['kicker' => 'سرعة استثنائية', 'title' => 'اسحب الملف وتصفح ملايين السجلات فورا', 'items' => [
                        ['title' => 'نافذة افتراضية فائقة السرعة', 'body' => 'البرنامج بيحمّل بس الصفوف اللي بتشوفها على الشاشة، فالتصفح بيفضل ناعم وسلس مهما كبر حجم الملف.'],
                        ['title' => 'فهارس ذكية وبحث فوري', 'body' => 'ابحث في أي عمود أو عبر كل الأعمدة بضغطة زر وبسرعة فائقة باستخدام محرك الفهرسة.'],
                        ['title' => 'مطابقة قوائم واستعلام SQL', 'body' => 'طابق قائمة خارجية ضد جدولك بضغطة واحدة، أو اكتب استعلام SQL مخصص وصدّر النتيجة.'],
                    ]],
                    'stat' => ['kicker' => 'الأداء الفعلي', 'value' => '0s', 'title' => 'صفر ثواني انتظار للتمرير', 'body' => 'التمرير الافتراضي بيخلي تصفح ملف 5 مليون صف بنفس سرعة وسلاسة ملف فيه 10 صفوف.'],
                    'steps' => ['kicker' => 'طريقة الاستخدام', 'title' => 'من الملف للرؤية الكاملة في ثوانٍ', 'items' => [
                        ['title' => 'افتح الملف أو اتصل بالقاعدة', 'body' => 'اسحب ملف CSV أو Excel أو SQLite أو اتصل بـ Postgres/MySQL للقراءة فقط.'],
                        ['title' => 'استعرض وابحث في الجداول', 'body' => 'فلتر الأعمدة وابحث في السجلات بسرعة متناهية.'],
                        ['title' => 'طابق القوائم أو شغل SQL', 'body' => 'افحص وجود عناصر قائمة خارجية واعرف مين موجود ومين مش موجود.'],
                        ['title' => 'صدّر النتائج النظيفة', 'body' => 'صدّر البيانات المفلترة إلى CSV أو Excel أو JSON بضغطة زر.'],
                    ]],
                    'video' => ['kicker' => 'تجربة حية', 'title' => 'شاهد فتح واستعراض ملايين الصفوف في ثوانٍ'],
                    'pillars' => ['kicker' => 'أهم المميزات', 'title' => 'أربع ركائز تجعل جريد ريدر استثنائيا', 'items' => [
                        ['icon' => 'zap', 'title' => 'سرعة خارقة واستهلاك ذاكرة قليل', 'body' => 'معمارية متطورة مصممة خصيصا للتعامل مع البيانات الضخمة بدون استنزاف الرام.'],
                        ['icon' => 'database', 'title' => 'دعم الملفات وقواعد البيانات', 'body' => 'CSV, TSV, Excel, JSON, SQLite بالإضافة إلى PostgreSQL و MySQL.'],
                        ['icon' => 'shield', 'title' => 'خصوصية وأمان 100%', 'body' => 'كل المعالجة بتتم محليا على جهازك، مفيش أي بيانات بتترفع على أي سيرفر خارجي.'],
                        ['icon' => 'refresh-cw', 'title' => 'تحديثات تلقائية مجانية', 'body' => 'البرنامج بيتحدث باستمرار وبيوصلك أحدث إصدار وتطويرات بشكل فوري.'],
                    ]],
                    'before_after' => ['kicker' => 'المقارنة العملية', 'title' => 'قبل وبعد جريد ريدر', 'before_title' => 'البرامج التقليدية', 'after_title' => 'مع جريد ريدر',
                        'before' => [['b' => 'تهنيج وتوقف كامل', 't' => 'عند فتح ملفات تتجاوز 100 ألف صف.'], ['b' => 'بحث بطيء وممل', 't' => 'انتظار طويل لكل عملية فلترة أو فرز.'], ['b' => 'أدوات معقدة', 't' => 'صعوبة الربط مع قواعد بيانات SQLite أو خوادم SQL.']],
                        'after' => [['b' => 'تصفح فوري وسلس', 't' => 'حتى مع ملايين الصفوف بدون أي بطء.'], ['b' => 'بحث وفهرسة فورية', 't' => 'نتائج فورية بفضل محرك الفهرسة والبحث المتقدم.'], ['b' => 'أداة واحدة شاملة', 't' => 'استعراض كل صيغ البيانات وقواعد البيانات في واجهة نظيفة وبسيطة.']]],
                    'metrics' => ['kicker' => 'أرقام ومواصفات', 'title' => 'إمكانيات تلبي متطلباتك', 'items' => [['value' => '6+', 'label' => 'صيغ وأنواع بيانات مدعومة'], ['value' => '10M+', 'label' => 'جاهز لملايين السجلات'], ['value' => '100%', 'label' => 'أمان ومعالجة محلية بالكامل']]],
                    'gallery' => ['title' => 'لقطات من واجهة البرنامج'],
                    'cta' => ['kicker' => 'ابدأ اليوم', 'title' => 'جاهز لتصفح وتحليل بياناتك بدون عناء؟', 'body' => 'اشترك واحصل على البرنامج فوراً مع ترخيص سنوي كامل وتحديثات مستمرة.', 'button' => 'اشتري جريد ريدر'],
                ],
                'en' => [
                    'kicker' => 'Data and Table Productivity Tools',
                    'headline' => 'Large files crashing your spreadsheet software? Open and explore them instantly.',
                    'subheadline' => 'View and query CSV, Excel, JSON, SQLite, PostgreSQL and MySQL datasets with millions of rows without lag or memory exhaustion. Everything stays safely on your computer.',
                    'problems' => ['kicker' => 'The Big Data Headache', 'title' => 'Why is working with large tables so frustrating?', 'items' => [
                        ['icon' => 'alert-triangle', 'title' => 'Frozen spreadsheet tools', 'body' => 'Files over a few hundred megabytes crash standard office software and lose your work.'],
                        ['icon' => 'clock', 'title' => 'Painfully slow searching', 'body' => 'Searching for a single record across millions of rows takes minutes of frozen UI.'],
                        ['icon' => 'database', 'title' => 'Bulky database managers', 'body' => 'Heavy tools require complicated setups just to inspect a table or run a simple filter.'],
                    ]],
                    'showcase' => ['kicker' => 'Peak Performance', 'title' => 'Drop any file and navigate millions of rows right away', 'items' => [
                        ['title' => 'Ultra-fast virtual scrolling', 'body' => 'Only visible rows are rendered, keeping navigation fluid and responsive at any scale.'],
                        ['title' => 'Instant search and indexing', 'body' => 'Search across any column or all columns at once with smart index acceleration.'],
                        ['title' => 'List matching and SQL console', 'body' => 'Match external lists against your dataset or execute custom SQL queries with live execution times.'],
                    ]],
                    'stat' => ['kicker' => 'Real Performance', 'value' => '0s', 'title' => 'Zero lag scrolling', 'body' => 'Virtualized rendering handles a 5-million row dataset with the exact same speed and responsiveness as a 10-row file.'],
                    'steps' => ['kicker' => 'How It Works', 'title' => 'From raw file to complete clarity in seconds', 'items' => [
                        ['title' => 'Open a file or connect', 'body' => 'Drag-and-drop CSV, Excel, JSON, SQLite or connect read-only to PostgreSQL/MySQL.'],
                        ['title' => 'Filter and inspect', 'body' => 'Navigate columns, inspect detailed row views, and filter on any criteria.'],
                        ['title' => 'Match lists or run SQL', 'body' => 'Cross-reference external lists to spot matches and missing entries instantly.'],
                        ['title' => 'Export clean data', 'body' => 'Export results cleanly to CSV, Excel (XLSX), or JSON with a single click.'],
                    ]],
                    'video' => ['kicker' => 'See It in Action', 'title' => 'Watch GridReader load and navigate massive tables in seconds'],
                    'pillars' => ['kicker' => 'Core Strengths', 'title' => 'Four pillars that set GridReader apart', 'items' => [
                        ['icon' => 'zap', 'title' => 'Blazing speed & low memory', 'body' => 'Engineered specifically for massive tabular data without draining your RAM.'],
                        ['icon' => 'database', 'title' => 'Universal format support', 'body' => 'CSV, TSV, Excel, JSON, SQLite databases, plus PostgreSQL and MySQL connections.'],
                        ['icon' => 'shield', 'title' => '100% privacy & local execution', 'body' => 'All processing happens locally on your computer. Zero data is ever sent to any server.'],
                        ['icon' => 'refresh-cw', 'title' => 'Seamless automatic updates', 'body' => 'New improvements and format updates are delivered automatically on startup.'],
                    ]],
                    'before_after' => ['kicker' => 'The Practical Difference', 'title' => 'Before and after GridReader', 'before_title' => 'Standard tools', 'after_title' => 'With GridReader',
                        'before' => [['b' => 'Software freeze', 't' => 'Crashes on files exceeding 100k rows.'], ['b' => 'Sluggish searches', 't' => 'Long waiting times for simple column filtering.'], ['b' => 'Fragmented tools', 't' => 'Switching between multiple heavy database clients.']],
                        'after' => [['b' => 'Instant opening', 't' => 'Smooth scrolling across millions of rows with zero freeze.'], ['b' => 'Instant search', 't' => 'Fast indexed searches and column-level filters.'], ['b' => 'Unified viewer', 't' => 'One clean, focused tool for files and database engines.']]],
                    'metrics' => ['kicker' => 'At a Glance', 'title' => 'Built for demanding workflows', 'items' => [['value' => '6+', 'label' => 'Supported data formats'], ['value' => '10M+', 'label' => 'Ready for millions of records'], ['value' => '100%', 'label' => 'Private & local execution']]],
                    'gallery' => ['title' => 'Inside GridReader'],
                    'cta' => ['kicker' => 'Get Started', 'title' => 'Ready for painless data exploration?', 'body' => 'Subscribe today and get instant access to GridReader with automatic updates and full support.', 'button' => 'Buy GridReader'],
                ],
            ],
        ];
    }

    private function emailSender(): array
    {
        return [
            'slug' => 'email-sender-pro',
            'sort_order' => 2,
            'image_path' => 'images/software/email-sender.png',
            'name_en' => 'Email Sender Pro',
            'name_ar' => 'إيميل سيندر برو',
            'tagline_en' => 'Send bulk email campaigns with smart SMTP rotation.',
            'tagline_ar' => 'ابعت حملات إيميل جماعية مع تدوير ذكي لحسابات SMTP.',
            'description_en' => 'Many SMTP accounts, warm-up, DNS checks and A/B tests in one desktop program.',
            'description_ar' => 'حسابات SMTP كتير وتسخين وفحص DNS واختبار A/B في برنامج واحد.',
            'theme' => [
                'colors' => ['accent' => '#7c3aed', 'ink' => '#160f2e'],
                'sections' => ['problems', 'pillars', 'stat', 'showcase', 'before_after', 'steps', 'video', 'metrics', 'gallery'],
            ],
            'landing' => [
                'ar' => [
                    'kicker' => 'حملات الإيميل',
                    'headline' => 'رسايلك توصل الإنبوكس مش السبام.',
                    'subheadline' => 'أدر حسابات SMTP كتير، سخّن الدومين، افحص إعدادات DNS، وابعت حملات كبيرة من غير ما حساباتك تتحرق.',
                    'problems' => ['kicker' => 'مشاكل الإرسال الجماعي', 'title' => 'ليه حملاتك مش بتوصل؟', 'items' => [
                        ['icon' => 'mail', 'title' => 'الرسايل بتروح للسبام', 'body' => 'إعدادات ناقصة تخلي مزودي الإيميل يرفضوا رسايلك.'],
                        ['icon' => 'shield', 'title' => 'حسابات SMTP بتتحرق', 'body' => 'الإرسال كله من حساب واحد يوقفه بسرعة.'],
                        ['icon' => 'chart', 'title' => 'مفيش معرفة بالنتيجة', 'body' => 'مش عارف أنهي صيغة بتجيب ردود أحسن.'],
                    ]],
                    'pillars' => ['kicker' => 'قدرات البرنامج', 'title' => 'أربع ركائز لحملة ناجحة', 'items' => [
                        ['icon' => 'refresh', 'title' => 'تدوير SMTP', 'body' => 'وزّع الإرسال على حسابات كتير تلقائيا.'],
                        ['icon' => 'zap', 'title' => 'تسخين الحسابات', 'body' => 'زوّد الحجم بالتدريج عشان السمعة تتحسن.'],
                        ['icon' => 'shield', 'title' => 'فحص SPF و DKIM و DMARC', 'body' => 'اعرف مشاكل الدومين قبل ما تبعت.'],
                        ['icon' => 'users', 'title' => 'قائمة حظر', 'body' => 'متبعتش مرة تانية لمن طلب الإيقاف.'],
                    ]],
                    'stat' => ['kicker' => 'اختبار ذكي', 'value' => 'A/B', 'title' => 'اختبر قبل ما تبعت للكل', 'body' => 'جرّب صيغتين واعرف الأحسن على عينة صغيرة.'],
                    'showcase' => ['kicker' => 'لوحة التحكم', 'title' => 'كل حملاتك في شاشة واحدة', 'items' => [
                        ['title' => 'حملات ومتابعة', 'body' => 'شوف المرسل والفاشل لحظة بلحظة.'],
                        ['title' => 'Spintax', 'body' => 'كل رسالة بصيغة مختلفة قليلا.'],
                        ['title' => 'استيراد جهات الاتصال', 'body' => 'من CSV أو إكسل مباشرة.'],
                    ]],
                    'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد إيميل سيندر', 'before_title' => 'الإرسال العادي', 'after_title' => 'مع إيميل سيندر برو',
                        'before' => [['b' => 'حساب واحد', 't' => 'يتحرق بعد أول حملة.'], ['b' => 'بدون فحص', 't' => 'السبام مفاجأة.'], ['b' => 'تخمين', 't' => 'مفيش اختبار للصيغ.']],
                        'after' => [['b' => 'تدوير ذكي', 't' => 'الحمل موزع على الحسابات.'], ['b' => 'فحص مسبق', 't' => 'DNS سليم قبل الإرسال.'], ['b' => 'اختبار A/B', 't' => 'قرار مبني على نتيجة.']]],
                    'steps' => ['kicker' => 'خطوات العمل', 'title' => 'حملتك في 4 خطوات', 'items' => [
                        ['title' => 'أضف حسابات SMTP', 'body' => 'اربط حساباتك مرة واحدة.'],
                        ['title' => 'افحص الدومين', 'body' => 'SPF و DKIM و DMARC.'],
                        ['title' => 'جهّز الحملة', 'body' => 'الرسالة والقائمة والاختبار.'],
                        ['title' => 'ابعت وتابع', 'body' => 'تقارير مباشرة أثناء الإرسال.'],
                    ]],
                    'video' => ['kicker' => 'شوف بنفسك', 'title' => 'حملة كاملة في دقيقتين'],
                    'metrics' => ['kicker' => 'مؤشرات', 'title' => 'أرقام تهمك', 'items' => [['value' => 'N', 'label' => 'حسابات SMTP بلا حد ثابت'], ['value' => '3', 'label' => 'فحوص DNS قبل الإرسال'], ['value' => 'A/B', 'label' => 'اختبار على كل حملة']]],
                    'gallery' => ['title' => 'صور من البرنامج'],
                    'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز تبعت حملتك الأولى؟', 'body' => 'اشترك سنة كاملة واستلم البرنامج. بعد الدفع هنكلمك على الواتساب نفعّل معاك النسخة.', 'button' => 'اشتري إيميل سيندر برو'],
                ],
                'en' => [
                    'kicker' => 'Email campaigns',
                    'headline' => 'Your emails reach the inbox, not spam.',
                    'subheadline' => 'Manage many SMTP accounts, warm up your domain, check DNS and send big campaigns without burning your accounts.',
                    'problems' => ['kicker' => 'Bulk sending problems', 'title' => 'Why do your campaigns not arrive?', 'items' => [
                        ['icon' => 'mail', 'title' => 'Mail goes to spam', 'body' => 'Missing setup makes mail providers reject your messages.'],
                        ['icon' => 'shield', 'title' => 'SMTP accounts get burned', 'body' => 'Sending everything from one account gets it blocked fast.'],
                        ['icon' => 'chart', 'title' => 'No idea about results', 'body' => 'You do not know which wording gets better replies.'],
                    ]],
                    'pillars' => ['kicker' => 'Capabilities', 'title' => 'Four pillars of a good campaign', 'items' => [
                        ['icon' => 'refresh', 'title' => 'SMTP rotation', 'body' => 'Spread sending across many accounts automatically.'],
                        ['icon' => 'zap', 'title' => 'Account warm-up', 'body' => 'Grow volume slowly so reputation improves.'],
                        ['icon' => 'shield', 'title' => 'SPF, DKIM and DMARC checks', 'body' => 'Find domain problems before you send.'],
                        ['icon' => 'users', 'title' => 'Suppression list', 'body' => 'Never email someone who opted out.'],
                    ]],
                    'stat' => ['kicker' => 'Smart testing', 'value' => 'A/B', 'title' => 'Test before you send to everyone', 'body' => 'Try two versions and pick the best on a small sample.'],
                    'showcase' => ['kicker' => 'Control panel', 'title' => 'All campaigns on one screen', 'items' => [
                        ['title' => 'Campaigns and tracking', 'body' => 'See sent and failed live.'],
                        ['title' => 'Spintax', 'body' => 'Every message slightly different.'],
                        ['title' => 'Contact import', 'body' => 'Straight from CSV or Excel.'],
                    ]],
                    'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after Email Sender', 'before_title' => 'Normal sending', 'after_title' => 'With Email Sender Pro',
                        'before' => [['b' => 'One account', 't' => 'Burns after the first campaign.'], ['b' => 'No checks', 't' => 'Spam is a surprise.'], ['b' => 'Guessing', 't' => 'No wording tests.']],
                        'after' => [['b' => 'Smart rotation', 't' => 'Load is spread over accounts.'], ['b' => 'Pre-checks', 't' => 'DNS is right before sending.'], ['b' => 'A/B tests', 't' => 'Decisions based on results.']]],
                    'steps' => ['kicker' => 'How it works', 'title' => 'Your campaign in 4 steps', 'items' => [
                        ['title' => 'Add SMTP accounts', 'body' => 'Connect your accounts once.'],
                        ['title' => 'Check the domain', 'body' => 'SPF, DKIM and DMARC.'],
                        ['title' => 'Prepare the campaign', 'body' => 'Message, list and test.'],
                        ['title' => 'Send and follow', 'body' => 'Live reports while sending.'],
                    ]],
                    'video' => ['kicker' => 'See it yourself', 'title' => 'A full campaign in two minutes'],
                    'metrics' => ['kicker' => 'Numbers that matter', 'title' => 'The facts', 'items' => [['value' => 'N', 'label' => 'SMTP accounts, no fixed limit'], ['value' => '3', 'label' => 'DNS checks before sending'], ['value' => 'A/B', 'label' => 'Testing on every campaign']]],
                    'gallery' => ['title' => 'Inside the program'],
                    'cta' => ['kicker' => 'Start today', 'title' => 'Ready to send your first campaign?', 'body' => 'Subscribe for a year and get the program. After payment we message you on WhatsApp to activate your copy.', 'button' => 'Buy Email Sender Pro'],
                ],
            ],
        ];
    }

    private function whatsappSender(): array
    {
        return [
            'slug' => 'whatsapp-bulk-sender',
            'sort_order' => 3,
            'image_path' => 'images/software/whatsapp-bulker.png',
            'name_en' => 'WhatsApp Bulk Sender',
            'name_ar' => 'واتساب بلك سيندر',
            'tagline_en' => 'Send WhatsApp messages in bulk from several accounts.',
            'tagline_ar' => 'ابعت رسائل واتساب جماعية من أكتر من حساب.',
            'description_en' => 'Multi-account WhatsApp sending with safe pacing, spintax and number filtering.',
            'description_ar' => 'إرسال واتساب من أكتر من حساب بسرعة آمنة وspintax وفلترة أرقام.',
            'theme' => [
                'colors' => ['accent' => '#16a34a', 'ink' => '#0a1f14'],
                'sections' => ['problems', 'stat', 'pillars', 'showcase', 'steps', 'before_after', 'video', 'metrics', 'gallery'],
            ],
            'landing' => [
                'ar' => [
                    'kicker' => 'التسويق على واتساب',
                    'headline' => 'كلّم آلاف العملاء من غير ما رقمك يتبند.',
                    'subheadline' => 'سجّل أكتر من حساب بـ QR، وابعت رسائل بسرعة آمنة تشبه الإنسان، وفلتر الأرقام اللي عليها واتساب فعلا.',
                    'problems' => ['kicker' => 'مشاكل الإرسال الجماعي', 'title' => 'ليه رقمك بيتحظر؟', 'items' => [
                        ['icon' => 'shield', 'title' => 'الحظر المفاجئ', 'body' => 'الإرسال السريع جدا بيكشف الحساب ويوقفه.'],
                        ['icon' => 'phone', 'title' => 'أرقام مش على واتساب', 'body' => 'بتضيّع رسايل على أرقام مالهاش حساب.'],
                        ['icon' => 'message', 'title' => 'رسايل متطابقة', 'body' => 'نفس النص لكل الناس شكله سبام.'],
                    ]],
                    'stat' => ['kicker' => 'أمان الحساب', 'value' => '100%', 'title' => 'فلترة الأرقام قبل الإرسال', 'body' => 'اعرف الأرقام اللي عليها واتساب فعلا ومتضيّعش رسايل.'],
                    'pillars' => ['kicker' => 'قدرات البرنامج', 'title' => 'أربع ركائز للإرسال الآمن', 'items' => [
                        ['icon' => 'clock', 'title' => 'سرعة تشبه الإنسان', 'body' => 'فواصل عشوائية بين الرسايل.'],
                        ['icon' => 'users', 'title' => 'حسابات متعددة', 'body' => 'تسجيل دخول بـ QR لكل حساب.'],
                        ['icon' => 'search', 'title' => 'فلترة الأرقام', 'body' => 'اعرف مين عليه واتساب قبل الإرسال.'],
                        ['icon' => 'file', 'title' => 'مرفقات وspintax', 'body' => 'صور وملفات ونصوص متنوعة.'],
                    ]],
                    'showcase' => ['kicker' => 'لوحة التحكم', 'title' => 'تحكم كامل في حملتك', 'items' => [
                        ['title' => 'قنوات متعددة', 'body' => 'شوف حالة كل حساب في مكان واحد.'],
                        ['title' => 'قوائم جهات الاتصال', 'body' => 'استيراد من إكسل وتنظيم في قوائم.'],
                        ['title' => 'تقرير الحملة', 'body' => 'المرسل والفاشل لحظة بلحظة.'],
                    ]],
                    'steps' => ['kicker' => 'خطوات العمل', 'title' => 'حملتك في 4 خطوات', 'items' => [
                        ['title' => 'اربط الحساب', 'body' => 'امسح كود QR.'],
                        ['title' => 'ارفع الأرقام', 'body' => 'ملف إكسل أو CSV.'],
                        ['title' => 'اكتب الرسالة', 'body' => 'نص أو صورة مع spintax.'],
                        ['title' => 'ابدأ الإرسال', 'body' => 'البرنامج يتولى الباقي.'],
                    ]],
                    'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد واتساب بلك سيندر', 'before_title' => 'الإرسال اليدوي', 'after_title' => 'مع واتساب بلك سيندر',
                        'before' => [['b' => 'إرسال يدوي', 't' => 'ساعات من النسخ واللصق.'], ['b' => 'خطر الحظر', 't' => 'رقم واحد تحت ضغط.'], ['b' => 'أرقام ميتة', 't' => 'رسايل ضايعة.']],
                        'after' => [['b' => 'إرسال تلقائي', 't' => 'تجهز الحملة وتمشي.'], ['b' => 'حمل موزع', 't' => 'أكتر من حساب وسرعة آمنة.'], ['b' => 'أرقام صحيحة', 't' => 'فلترة قبل الإرسال.']]],
                    'video' => ['kicker' => 'شوف بنفسك', 'title' => 'أول حملة في دقيقتين'],
                    'metrics' => ['kicker' => 'مؤشرات', 'title' => 'أرقام تهمك', 'items' => [['value' => 'QR', 'label' => 'ربط حساب في ثواني'], ['value' => 'N', 'label' => 'حسابات في نفس الوقت'], ['value' => 'CSV', 'label' => 'استيراد الأرقام من إكسل']]],
                    'gallery' => ['title' => 'صور من البرنامج'],
                    'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز تبدأ حملتك على واتساب؟', 'body' => 'اشترك سنة كاملة واستلم البرنامج. بعد الدفع هنكلمك على الواتساب نفعّل معاك النسخة.', 'button' => 'اشتري واتساب بلك سيندر'],
                ],
                'en' => [
                    'kicker' => 'WhatsApp marketing',
                    'headline' => 'Reach thousands of customers without getting your number banned.',
                    'subheadline' => 'Log in several accounts with QR, send at a safe human-like pace and filter the numbers that really have WhatsApp.',
                    'problems' => ['kicker' => 'Bulk sending problems', 'title' => 'Why does your number get banned?', 'items' => [
                        ['icon' => 'shield', 'title' => 'Sudden bans', 'body' => 'Sending too fast exposes the account and stops it.'],
                        ['icon' => 'phone', 'title' => 'Numbers not on WhatsApp', 'body' => 'You waste messages on numbers with no account.'],
                        ['icon' => 'message', 'title' => 'Identical messages', 'body' => 'The same text for everyone looks like spam.'],
                    ]],
                    'stat' => ['kicker' => 'Account safety', 'value' => '100%', 'title' => 'Numbers filtered before sending', 'body' => 'Know which numbers really have WhatsApp and stop wasting messages.'],
                    'pillars' => ['kicker' => 'Capabilities', 'title' => 'Four pillars of safe sending', 'items' => [
                        ['icon' => 'clock', 'title' => 'Human-like pace', 'body' => 'Random gaps between messages.'],
                        ['icon' => 'users', 'title' => 'Multiple accounts', 'body' => 'QR login for each account.'],
                        ['icon' => 'search', 'title' => 'Number filtering', 'body' => 'Know who has WhatsApp before sending.'],
                        ['icon' => 'file', 'title' => 'Media and spintax', 'body' => 'Images, files and varied text.'],
                    ]],
                    'showcase' => ['kicker' => 'Control panel', 'title' => 'Full control of your campaign', 'items' => [
                        ['title' => 'Many channels', 'body' => 'See every account status in one place.'],
                        ['title' => 'Contact lists', 'body' => 'Import from Excel and organize in lists.'],
                        ['title' => 'Campaign report', 'body' => 'Sent and failed, live.'],
                    ]],
                    'steps' => ['kicker' => 'How it works', 'title' => 'Your campaign in 4 steps', 'items' => [
                        ['title' => 'Link the account', 'body' => 'Scan the QR code.'],
                        ['title' => 'Upload numbers', 'body' => 'Excel or CSV file.'],
                        ['title' => 'Write the message', 'body' => 'Text or image with spintax.'],
                        ['title' => 'Start sending', 'body' => 'The program does the rest.'],
                    ]],
                    'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after WhatsApp Bulk Sender', 'before_title' => 'Manual sending', 'after_title' => 'With WhatsApp Bulk Sender',
                        'before' => [['b' => 'Manual sending', 't' => 'Hours of copy and paste.'], ['b' => 'Ban risk', 't' => 'One number under pressure.'], ['b' => 'Dead numbers', 't' => 'Wasted messages.']],
                        'after' => [['b' => 'Automatic sending', 't' => 'Prepare the campaign and go.'], ['b' => 'Spread load', 't' => 'More accounts, safe pace.'], ['b' => 'Valid numbers', 't' => 'Filtered before sending.']]],
                    'video' => ['kicker' => 'See it yourself', 'title' => 'Your first campaign in two minutes'],
                    'metrics' => ['kicker' => 'Numbers that matter', 'title' => 'The facts', 'items' => [['value' => 'QR', 'label' => 'Link an account in seconds'], ['value' => 'N', 'label' => 'Accounts at the same time'], ['value' => 'CSV', 'label' => 'Import numbers from Excel']]],
                    'gallery' => ['title' => 'Inside the program'],
                    'cta' => ['kicker' => 'Start today', 'title' => 'Ready to start your WhatsApp campaign?', 'body' => 'Subscribe for a year and get the program. After payment we message you on WhatsApp to activate your copy.', 'button' => 'Buy WhatsApp Bulk Sender'],
                ],
            ],
        ];
    }

    /** Resold membership sold under our own brand. Prices are in EGP per year. */
    private function marketingSuite(): array
    {
        return [
            'slug' => 'marketing-suite',
            'sort_order' => 4,
            'image_path' => 'images/software/bundle.svg',
            'name_en' => 'Musoftware Marketing Suite',
            'name_ar' => 'حزمة موسوفت للتسويق',
            'tagline_en' => 'Fifteen marketing tools in one yearly membership.',
            'tagline_ar' => 'خمستاشر أداة تسويق في عضوية سنوية واحدة.',
            'description_en' => 'Desktop apps and browser extensions for messaging, lead collection and data work, with four yearly plans.',
            'description_ar' => 'برامج سطح مكتب وإضافات متصفح للرسائل وجمع العملاء وتنظيف البيانات، بأربع باقات سنوية.',
            'currency' => 'EGP',
            'plans' => [
                ['key' => 'lite', 'name' => 'Lite', 'price' => 4924, 'points' => 80000, 'bots' => 1],
                ['key' => 'basic', 'name' => 'Basic', 'price' => 9848, 'points' => 160000, 'bots' => 3],
                ['key' => 'personal', 'name' => 'Personal', 'price' => 13223, 'points' => 240000, 'bots' => 5],
                ['key' => 'company', 'name' => 'Company', 'price' => 26723, 'points' => 400000, 'bots' => 10],
            ],
            'theme' => [
                'colors' => ['accent' => '#0f766e', 'ink' => '#07201e'],
                'sections' => ['problems', 'stat', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery', 'plans'],
            ],
            'landing' => [
                'ar' => [
                    'kicker' => 'أدوات التسويق في مكان واحد',
                    'headline' => 'بدل ما تشترك في كل أداة لوحدها، خد عضوية واحدة فيها الشغل كله.',
                    'subheadline' => 'برامج للرسائل الجماعية، وإضافات لجمع العملاء المحتملين، وأدوات لتنضيف البيانات وتتبع الحملات. اشتراك سنوي واحد وباقات على قد حجم شغلك.',
                    'problems' => ['kicker' => 'مشاكل المسوّق', 'title' => 'ليه شغل التسويق بيتعب؟', 'items' => [
                        ['icon' => 'box', 'title' => 'أدوات متفرقة', 'body' => 'برنامج للواتساب وبرنامج للتيليجرام وإضافة لجمع الأرقام، وكل واحد باشتراك.'],
                        ['icon' => 'clock', 'title' => 'شغل يدوي كتير', 'body' => 'جمع الأرقام وتنضيفها وإرسال الرسائل بإيدك بياكل وقتك.'],
                        ['icon' => 'chart', 'title' => 'مفيش متابعة', 'body' => 'مش عارف مين فتح الرابط ومين رد ومين محتاج متابعة.'],
                    ]],
                    'stat' => ['kicker' => 'كل ده في عضوية واحدة', 'value' => '15', 'title' => 'أداة في اشتراك واحد', 'body' => 'تسعة برامج سطح مكتب وست إضافات متصفح، متاحة في كل الباقات.'],
                    'pillars' => ['kicker' => 'قدرات الحزمة', 'title' => 'أربع مجموعات أدوات', 'items' => [
                        ['icon' => 'message', 'title' => 'رسائل ومحادثات', 'body' => 'واتساب وتيليجرام وإنستجرام وفيسبوك إنبوكس ومركز اتصال.'],
                        ['icon' => 'search', 'title' => 'جمع العملاء', 'body' => 'إضافات تجمع العملاء المحتملين من التعليقات والجروبات والمنشورات.'],
                        ['icon' => 'database', 'title' => 'بيانات وخرائط', 'body' => 'تنضيف ملفات CSV والبحث في خرائط جوجل والأدلة والإعلانات المبوبة.'],
                        ['icon' => 'send', 'title' => 'حملات وفريق', 'body' => 'حملات SMS وواتساب، وروابط قصيرة بتتبع، وإدارة المبيعات والموظفين.'],
                    ]],
                    'steps' => ['kicker' => 'خطوات الاشتراك', 'title' => 'من الدفع للتشغيل في 4 خطوات', 'items' => [
                        ['title' => 'اختار الباقة', 'body' => 'على حسب عدد العملاء اللي محتاجهم.'],
                        ['title' => 'ادفع أونلاين', 'body' => 'من غير تسجيل حساب على موقعنا.'],
                        ['title' => 'كلمنا على الواتساب', 'body' => 'بتبعتلنا رقم طلبك ونجهز عضويتك.'],
                        ['title' => 'ابدأ الشغل', 'body' => 'استلم بيانات الدخول وحمّل البرامج.'],
                    ]],
                    'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد الحزمة', 'before_title' => 'أدوات متفرقة', 'after_title' => 'مع الحزمة',
                        'before' => [['b' => 'اشتراكات', 't' => 'كل أداة بسعر وتجديد لوحدها.'], ['b' => 'ملفات', 't' => 'بيانات مبعثرة بين برامج مختلفة.'], ['b' => 'وقت', 't' => 'تنقل بين أدوات كتير في اليوم.']],
                        'after' => [['b' => 'اشتراك واحد', 't' => 'سنة كاملة بسعر واضح.'], ['b' => 'أدوات متكاملة', 't' => 'من جمع العميل لحد الرسالة.'], ['b' => 'وقت أقل', 't' => 'كل شغلك في مكان واحد.']]],
                    'metrics' => ['kicker' => 'ملخص', 'title' => 'الحزمة في أرقام', 'items' => [['value' => '9', 'label' => 'برامج سطح مكتب'], ['value' => '6', 'label' => 'إضافات متصفح'], ['value' => '365', 'label' => 'يوم مدة العضوية']]],
                    'plans' => ['kicker' => 'الباقات', 'title' => 'اختار باقتك السنوية',
                        'included' => ['كل البرامج والإضافات', 'عضوية سنة كاملة'],
                        'note' => 'كل الباقات فيها نفس البرامج والإضافات، والفرق في نقاط الجمهور وعدد البوتات. نقطة الجمهور = عميل محتمل جديد. العضوية سنوية، والدعم الفني من خلال نظام التذاكر. اقرأ شروط الاستخدام واحترم سياسات كل منصة قبل ما تبدأ.'],
                    'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز تبدأ بعضوية واحدة؟', 'body' => 'اختار الباقة وادفع. بعد الدفع هنكلمك على الواتساب نجهز لك العضوية.', 'button' => 'كمّل للدفع'],
                ],
                'en' => [
                    'kicker' => 'Marketing tools in one place',
                    'headline' => 'Stop paying for every tool alone. Get one membership with all the work in it.',
                    'subheadline' => 'Apps for bulk messaging, extensions that collect leads, and tools that clean data and track campaigns. One yearly membership, with plans sized to your work.',
                    'problems' => ['kicker' => 'The marketer problem', 'title' => 'Why is marketing work so tiring?', 'items' => [
                        ['icon' => 'box', 'title' => 'Scattered tools', 'body' => 'One app for WhatsApp, one for Telegram, one extension for numbers, each with its own subscription.'],
                        ['icon' => 'clock', 'title' => 'Too much manual work', 'body' => 'Collecting numbers, cleaning them and sending messages by hand eats your time.'],
                        ['icon' => 'chart', 'title' => 'No follow-up', 'body' => 'You do not know who opened the link, who replied and who needs a follow-up.'],
                    ]],
                    'stat' => ['kicker' => 'All in one membership', 'value' => '15', 'title' => 'tools in one subscription', 'body' => 'Nine desktop apps and six browser extensions, in every plan.'],
                    'pillars' => ['kicker' => 'What is inside', 'title' => 'Four groups of tools', 'items' => [
                        ['icon' => 'message', 'title' => 'Messages and chats', 'body' => 'WhatsApp, Telegram, Instagram, Facebook inbox and a call center.'],
                        ['icon' => 'search', 'title' => 'Lead collection', 'body' => 'Extensions that collect leads from comments, groups and posts.'],
                        ['icon' => 'database', 'title' => 'Data and maps', 'body' => 'CSV cleaning, Google Maps search, directories and classified ads.'],
                        ['icon' => 'send', 'title' => 'Campaigns and team', 'body' => 'SMS and WhatsApp campaigns, tracked short links, sales and staff management.'],
                    ]],
                    'steps' => ['kicker' => 'How to subscribe', 'title' => 'From payment to work in 4 steps', 'items' => [
                        ['title' => 'Choose a plan', 'body' => 'By the number of leads you need.'],
                        ['title' => 'Pay online', 'body' => 'No account needed on our site.'],
                        ['title' => 'Message us on WhatsApp', 'body' => 'Send your order number and we prepare your membership.'],
                        ['title' => 'Start working', 'body' => 'Get your login details and download the apps.'],
                    ]],
                    'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the suite', 'before_title' => 'Separate tools', 'after_title' => 'With the suite',
                        'before' => [['b' => 'Subscriptions', 't' => 'Every tool has its own price and renewal.'], ['b' => 'Files', 't' => 'Data scattered across different apps.'], ['b' => 'Time', 't' => 'Jumping between many tools every day.']],
                        'after' => [['b' => 'One subscription', 't' => 'A full year at a clear price.'], ['b' => 'Connected tools', 't' => 'From finding the lead to sending the message.'], ['b' => 'Less time', 't' => 'All your work in one place.']]],
                    'metrics' => ['kicker' => 'Summary', 'title' => 'The suite in numbers', 'items' => [['value' => '9', 'label' => 'Desktop apps'], ['value' => '6', 'label' => 'Browser extensions'], ['value' => '365', 'label' => 'Days of membership']]],
                    'plans' => ['kicker' => 'Plans', 'title' => 'Choose your yearly plan',
                        'included' => ['All apps and extensions', 'A full year of membership'],
                        'note' => 'Every plan has the same apps and extensions. The difference is audience points and the number of bots. One audience point is one new lead. Membership is yearly and support is through the ticket system. Read the terms of use and follow each platform rules before you start.'],
                    'cta' => ['kicker' => 'Start today', 'title' => 'Ready to start with one membership?', 'body' => 'Choose your plan and pay. After payment we message you on WhatsApp to prepare your membership.', 'button' => 'Continue to payment'],
                ],
            ],
        ];
    }

    /**
     * Official WhatsApp (Meta Cloud API) setup and campaigns, sold under our own brand.
     * Prices are in USD because the payment gateway converts from currencies we have rates for.
     * They are the OMR price list at the fixed peg (1 OMR = 2.6008 USD).
     */
    private function whatsappOfficial(): array
    {
        return [
            'slug' => 'whatsapp-official',
            'sort_order' => 5,
            'image_path' => 'images/software/whatsapp-official.svg',
            'name_en' => 'Musoftware Official WhatsApp Service',
            'name_ar' => 'خدمة موسوفت لواتساب الرسمي',
            'tagline_en' => 'Official WhatsApp Business setup and campaigns through the Meta Cloud API.',
            'tagline_ar' => 'تجهيز واتساب الأعمال الرسمي وحملاته عن طريق Cloud API من ميتا.',
            'description_en' => 'Account setup and verification, approved templates and official campaigns with reports.',
            'description_ar' => 'تأسيس وتوثيق الحساب وقوالب معتمدة وحملات رسمية بتقارير.',
            'currency' => 'USD',
            'plans' => [
                ['key' => 'trial', 'name' => 'Trial campaign', 'name_ar' => 'حملة تجربة', 'price' => 46.8,
                    'description' => '1,000 official marketing messages with one approved template and a delivery and read report.',
                    'description_ar' => '1,000 رسالة تسويقية رسمية بقالب معتمد واحد وتقرير تسليم وقراءة.'],
                ['key' => 'setup', 'name' => 'Full setup and verification', 'name_ar' => 'التأسيس والتوثيق الشامل', 'price' => 143.04,
                    'description' => 'Meta Business Suite creation and verification, number activation, templates approval, shared inbox and a supervised first campaign.',
                    'description_ar' => 'إنشاء وتوثيق Meta Business Suite وتفعيل الرقم واعتماد القوالب وصندوق وارد موحد وإطلاق أول حملة بإشراف.'],
                ['key' => 'growth', 'name' => 'Growth campaign', 'name_ar' => 'حملة النمو', 'price' => 468.14,
                    'description' => '10,000 official marketing messages with tagging, scheduling and a clicks, replies and sales report.',
                    'description_ar' => '10,000 رسالة تسويقية رسمية مع تصنيف وجدولة وتقرير نقرات وردود ومبيعات.'],
            ],
            'theme' => [
                'colors' => ['accent' => '#2563eb', 'ink' => '#0b1730'],
                'sections' => ['problems', 'stat', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery', 'plans'],
            ],
            'landing' => [
                'ar' => [
                    'kicker' => 'واتساب الأعمال الرسمي',
                    'headline' => 'ابعت حملاتك على واتساب بالطريقة الرسمية من ميتا.',
                    'subheadline' => 'بنجهز لك حساب Meta Business ورقم واتساب على Cloud API، ونكتب قوالب الرسائل ونعتمدها، ونطلق حملتك مع تقارير التسليم والقراءة.',
                    'problems' => ['kicker' => 'مشاكل الإرسال غير الرسمي', 'title' => 'ليه الطريقة العادية مش مضمونة؟', 'items' => [
                        ['icon' => 'shield', 'title' => 'خطر تقييد الحساب', 'body' => 'البرامج غير الرسمية ممكن تخلي واتساب يقيّد رقمك.'],
                        ['icon' => 'chart', 'title' => 'مفيش تقارير', 'body' => 'مش عارف كام رسالة اتسلمت وكام اتقرت وكام رد.'],
                        ['icon' => 'file', 'title' => 'رسائل من غير قوالب', 'body' => 'الرسائل التسويقية الرسمية محتاجة قوالب معتمدة من ميتا.'],
                    ]],
                    'stat' => ['kicker' => 'الطريقة الرسمية', 'value' => 'رسمي', 'title' => 'واتساب الأعمال عن طريق Cloud API من ميتا', 'body' => 'قوالب معتمدة وتقارير حقيقية، من غير برامج وسيطة.'],
                    'pillars' => ['kicker' => 'اللي بنقدمه', 'title' => 'خدمات واتساب الرسمي', 'items' => [
                        ['icon' => 'check', 'title' => 'التأسيس والتوثيق', 'body' => 'إنشاء وتوثيق حساب Meta Business وتفعيل الرقم.'],
                        ['icon' => 'message', 'title' => 'قوالب معتمدة', 'body' => 'كتابة القوالب مع أزرار الإجراء واعتمادها.'],
                        ['icon' => 'send', 'title' => 'حملات وتقارير', 'body' => 'إطلاق الحملات وتقارير التسليم والقراءة والردود.'],
                        ['icon' => 'users', 'title' => 'خدمات إضافية', 'body' => 'شات بوت، ووكيل ذكاء اصطناعي، وتصميم موقع، وسحب أرقام. اطلبها على الواتساب.'],
                    ]],
                    'steps' => ['kicker' => 'خطوات العمل', 'title' => 'من الطلب للحملة في 4 خطوات', 'items' => [
                        ['title' => 'مراجعة المتطلبات', 'body' => 'نتأكد من نشاطك ومستنداتك.'],
                        ['title' => 'تأسيس الحساب والرقم', 'body' => 'Meta Business Suite وتفعيل الرقم.'],
                        ['title' => 'اعتماد القوالب', 'body' => 'نكتب القوالب وننتظر اعتماد ميتا.'],
                        ['title' => 'إطلاق الحملة', 'body' => 'جدولة الإرسال ومتابعة التقارير.'],
                    ]],
                    'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد واتساب الرسمي', 'before_title' => 'إرسال غير رسمي', 'after_title' => 'مع واتساب الرسمي',
                        'before' => [['b' => 'الحساب', 't' => 'خطر التقييد في أي وقت.'], ['b' => 'التقارير', 't' => 'مفيش أرقام دقيقة.'], ['b' => 'الرسائل', 't' => 'نصوص من غير اعتماد.']],
                        'after' => [['b' => 'الحساب', 't' => 'على واجهة ميتا الرسمية.'], ['b' => 'التقارير', 't' => 'تسليم وقراءة وردود.'], ['b' => 'الرسائل', 't' => 'قوالب معتمدة بأزرار.']]],
                    'metrics' => ['kicker' => 'ملخص الباقات', 'title' => 'الخدمة في أرقام', 'items' => [['value' => '1,000', 'label' => 'رسالة في حملة التجربة'], ['value' => '10,000', 'label' => 'رسالة في حملة النمو'], ['value' => '0', 'label' => 'اشتراكات شهرية إجبارية']]],
                    'plans' => ['kicker' => 'الباقات', 'title' => 'اختار الباقة', 'period' => 'دفعة واحدة',
                        'included' => ['شغل بصلاحيات محدودة على حسابك', 'من غير ما تشارك كلمات السر'],
                        'note' => 'الأسعار بالدولار. بعد التأسيس بتشحن رصيد وبيتخصم على حسب الرسائل، وسعر الرسالة التسويقية حوالي 0.047 دولار. مدة اعتماد ميتا بتختلف ومفيش مدة مضمونة.'],
                    'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز تبعت أول حملة رسمية؟', 'body' => 'اختار الباقة وادفع. بعد الدفع ابعتلنا رقم طلبك على الواتساب ونبدأ.', 'button' => 'كمّل للدفع'],
                ],
                'en' => [
                    'kicker' => 'Official WhatsApp Business',
                    'headline' => 'Send your campaigns on WhatsApp the official Meta way.',
                    'subheadline' => 'We set up your Meta Business account and a WhatsApp number on the Cloud API, write and approve your message templates, and launch your campaign with delivery and read reports.',
                    'problems' => ['kicker' => 'Problems of unofficial sending', 'title' => 'Why is the usual way not safe?', 'items' => [
                        ['icon' => 'shield', 'title' => 'Risk of account restriction', 'body' => 'Unofficial tools can make WhatsApp restrict your number.'],
                        ['icon' => 'chart', 'title' => 'No reports', 'body' => 'You do not know how many messages were delivered, read or answered.'],
                        ['icon' => 'file', 'title' => 'Messages without templates', 'body' => 'Official marketing messages need templates approved by Meta.'],
                    ]],
                    'stat' => ['kicker' => 'The official way', 'value' => 'Official', 'title' => 'WhatsApp Business through the Meta Cloud API', 'body' => 'Approved templates and real reports, with no middle tools.'],
                    'pillars' => ['kicker' => 'What we offer', 'title' => 'Official WhatsApp services', 'items' => [
                        ['icon' => 'check', 'title' => 'Setup and verification', 'body' => 'Create and verify your Meta Business account and activate your number.'],
                        ['icon' => 'message', 'title' => 'Approved templates', 'body' => 'Write templates with action buttons and get them approved.'],
                        ['icon' => 'send', 'title' => 'Campaigns and reports', 'body' => 'Launch campaigns with delivery, read and reply reports.'],
                        ['icon' => 'users', 'title' => 'Extra services', 'body' => 'Chatbot, AI agent, website design and number extraction. Ask on WhatsApp.'],
                    ]],
                    'steps' => ['kicker' => 'How it works', 'title' => 'From order to campaign in 4 steps', 'items' => [
                        ['title' => 'Review requirements', 'body' => 'We check your business and documents.'],
                        ['title' => 'Set up account and number', 'body' => 'Meta Business Suite and number activation.'],
                        ['title' => 'Approve templates', 'body' => 'We write the templates and wait for Meta approval.'],
                        ['title' => 'Launch the campaign', 'body' => 'Schedule sending and follow the reports.'],
                    ]],
                    'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after official WhatsApp', 'before_title' => 'Unofficial sending', 'after_title' => 'With official WhatsApp',
                        'before' => [['b' => 'Account', 't' => 'Restriction risk at any time.'], ['b' => 'Reports', 't' => 'No accurate numbers.'], ['b' => 'Messages', 't' => 'Texts with no approval.']],
                        'after' => [['b' => 'Account', 't' => 'On the official Meta interface.'], ['b' => 'Reports', 't' => 'Delivery, read and replies.'], ['b' => 'Messages', 't' => 'Approved templates with buttons.']]],
                    'metrics' => ['kicker' => 'Plans summary', 'title' => 'The service in numbers', 'items' => [['value' => '1,000', 'label' => 'Messages in the trial campaign'], ['value' => '10,000', 'label' => 'Messages in the growth campaign'], ['value' => '0', 'label' => 'Mandatory monthly subscriptions']]],
                    'plans' => ['kicker' => 'Plans', 'title' => 'Choose a plan', 'period' => 'one payment',
                        'included' => ['Work with limited permissions on your account', 'No need to share passwords'],
                        'note' => 'Prices are in US dollars. After setup you top up a balance that is used as you send, and a marketing message costs about 0.047 USD. Meta approval time varies and no time is guaranteed.'],
                    'cta' => ['kicker' => 'Start today', 'title' => 'Ready to send your first official campaign?', 'body' => 'Choose a plan and pay. After payment send us your order number on WhatsApp and we start.', 'button' => 'Continue to payment'],
                ],
            ],
        ];
    }
}
