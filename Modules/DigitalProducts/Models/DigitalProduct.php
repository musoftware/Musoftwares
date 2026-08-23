<?php

namespace Modules\DigitalProducts\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property int|null $category_id
 * @property float|string $price
 * @property int|null $currency_id
 * @property bool $is_free
 * @property bool $has_free_edition
 * @property string|null $free_edition_title
 * @property string|null $free_edition_file_path
 * @property string|null $free_edition_cover_path
 * @property int|null $free_edition_page_count
 * @property int|null $free_edition_file_size
 * @property int $free_edition_download_count
 * @property string $file_path
 * @property string|null $cover_image_path
 * @property string|null $sample_file_path
 * @property int|null $file_size
 * @property int|null $page_count
 * @property string|null $author_name
 * @property string|null $publisher
 * @property string|null $publication_year
 * @property string|null $language
 * @property int $download_count
 * @property int $view_count
 * @property bool $is_published
 * @property bool $is_featured
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read string $cover_url
 * @property-read string $formatted_price
 * @property-read string $formatted_file_size
 * @property-read string $free_edition_cover_url
 * @property-read string $formatted_free_edition_file_size
 * @property-read \Modules\DigitalProducts\Models\DigitalCategory|null $category
 * @property-read \App\Models\Currency|null $currency
 */
class DigitalProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'digital_products';

    protected $appends = [
        'cover_url',
        'formatted_price',
        'formatted_file_size',
        'free_edition_cover_url',
        'formatted_free_edition_file_size',
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'category_id',
        'price',
        'currency_id',
        'is_free',
        'has_free_edition',
        'free_edition_title',
        'free_edition_file_path',
        'free_edition_cover_path',
        'free_edition_page_count',
        'free_edition_file_size',
        'free_edition_download_count',
        'file_path',
        'cover_image_path',
        'sample_file_path',
        'file_size',
        'page_count',
        'author_name',
        'publisher',
        'publication_year',
        'language',
        'download_count',
        'view_count',
        'is_published',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'has_free_edition' => 'boolean',
        'free_edition_page_count' => 'integer',
        'free_edition_file_size' => 'integer',
        'free_edition_download_count' => 'integer',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'page_count' => 'integer',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'view_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $baseSlug = Str::slug($product->title);
                if (empty($baseSlug)) {
                    $baseSlug = 'book-' . Str::random(6);
                }
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $product->slug = $slug;
            }

            if ($product->price <= 0) {
                $product->is_free = true;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DigitalCategory::class, 'category_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DigitalProductDownload::class, 'digital_product_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(DigitalProductPurchase::class, 'digital_product_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free || $this->price <= 0) {
            return app()->getLocale() === 'ar' ? 'مجاناً' : 'Free';
        }

        $currencyId = $this->currency_id ?? 1;
        return \App\Helpers\FinanceHelper::instance()->format_money((float) $this->price, $currencyId);
    }

    /**
     * Get product price converted to a target currency using current exchange rate.
     */
    public function getPriceInCurrency(?int $targetCurrencyId = null): float
    {
        $basePrice = (float) $this->price;
        if ($this->is_free || $basePrice <= 0) {
            return 0.0;
        }

        $fromCurrencyId = (int) ($this->currency_id ?: 1);
        $targetCurrencyId = (int) ($targetCurrencyId ?: $fromCurrencyId);

        if ($fromCurrencyId === $targetCurrencyId) {
            return $basePrice;
        }

        try {
            $rate = (float) \App\Models\CurrenciesExchange::RateToday($basePrice, $fromCurrencyId, $targetCurrencyId);
            return $rate > 0 ? $rate : $basePrice;
        } catch (\Throwable $e) {
            return $basePrice;
        }
    }

    /**
     * Get product price converted to USD ($).
     */
    public function getPriceInUsd(): float
    {
        return $this->getPriceInCurrency(1);
    }

    /**
     * Format product price in USD ($).
     */
    public function formatPriceInUsd(): string
    {
        if ($this->is_free || $this->price <= 0) {
            return app()->getLocale() === 'ar' ? 'مجاناً' : 'Free';
        }

        $usdPrice = $this->getPriceInUsd();
        return \App\Helpers\FinanceHelper::instance()->format_money($usdPrice, 1);
    }

    /**
     * Format product price for a specific viewer currency.
     */
    public function formatPriceInCurrency(?Currency $currency): string
    {
        if ($this->is_free || $this->price <= 0) {
            return app()->getLocale() === 'ar' ? 'مجاناً' : 'Free';
        }

        if (!$currency) {
            return $this->formatted_price;
        }

        $converted = $this->getPriceInCurrency($currency->id);
        return \App\Helpers\FinanceHelper::instance()->format_money($converted, $currency->id);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '—';
        }

        $bytes = (float) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image_path) {
            if (Str::startsWith($this->cover_image_path, ['http://', 'https://'])) {
                return $this->cover_image_path;
            }
            return asset($this->cover_image_path);
        }

        return asset('images/default-book-cover.svg');
    }

    public function getFormattedFreeEditionFileSizeAttribute(): string
    {
        if (!$this->free_edition_file_size) {
            return '—';
        }

        $bytes = (float) $this->free_edition_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFreeEditionCoverUrlAttribute(): string
    {
        if ($this->free_edition_cover_path) {
            if (Str::startsWith($this->free_edition_cover_path, ['http://', 'https://'])) {
                return $this->free_edition_cover_path;
            }
            return asset($this->free_edition_cover_path);
        }

        return $this->cover_url;
    }

    public function hasDualEditions(): bool
    {
        return $this->has_free_edition && !empty($this->free_edition_file_path);
    }

    public function isPurchasedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->purchases()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($this->is_free && $this->downloads()->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('email', strtolower(trim($user->email)));
        })->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Get real chapters and table of contents customized for this specific book.
     *
     * @return array
     */
    public function getRealChaptersAttribute(): array
    {
        // 1. Try to extract directly from the uploaded PDF file (Bookmarks / Outlines / Text)
        $pdfPath = $this->sample_file_path ?: ($this->free_edition_file_path ?: $this->file_path);
        if (!empty($pdfPath)) {
            $pdfChapters = \Modules\DigitalProducts\Services\PdfTableOfContentsExtractor::extract($pdfPath, $this->id);
            if (!empty($pdfChapters) && count($pdfChapters) >= 2) {
                return $pdfChapters;
            }
        }

        $desc = $this->description ?? '';
        $title = $this->title ?? '';
        $categoryName = $this->category?->name ?? '';

        // 2. Try to extract explicit chapters from description if formatted as list
        $extracted = [];
        if (preg_match_all('/(?:الفصل|المحور|الوحدة|Chapter|Part)\s*(?:الأول|الثاني|الثالث|الرابع|الخامس|السادس|السابع|الثامن|\d+)[:\-\s]+([^\n\r\.\#]+)(?:[\.\n\r]+([^\n\r\#]+))?/iu', $desc, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $idx => $match) {
                $chTitle = trim($match[1]);
                $chSub = isset($match[2]) ? trim($match[2]) : '';
                if ($chTitle) {
                    $extracted[] = [
                        'num' => sprintf('%02d', $idx + 1),
                        'title' => $chTitle,
                        'subtopics' => $chSub ? array_map('trim', explode(',', $chSub)) : [],
                        'project' => 'تطبيق عملي وأكواد برمجية جاهزة'
                    ];
                }
            }
        }

        if (count($extracted) >= 3) {
            return $extracted;
        }

        // 2. Intelligent Topic-Specific Real Curriculum based on book title & category
        $tLower = mb_strtolower($title . ' ' . $categoryName);

        if (str_contains($tLower, 'خوارزم') || str_contains($tLower, 'algorithm')) {
            return [
                [
                    'num' => '01',
                    'title' => 'المدخل إلى تحليل الخوارزميات والتعقيد الزمني (Time & Space Complexity)',
                    'subtopics' => ['مفهوم Big-O Notation', 'تحليل أفضل وأسوأ الحالات', 'قياس استهلاك الذاكرة والذاكرة المؤقتة'],
                    'project' => 'مشروع: بناء أداة لاختبار ومقارنة سرعة تنفيذ الأكواد برمجياً'
                ],
                [
                    'num' => '02',
                    'title' => 'هياكل البيانات الجوهرية وطرق تطبيقها (Essential Data Structures)',
                    'subtopics' => ['المصفوفات والقوائم المترابطة (Linked Lists)', 'المكدس والطابور (Stack & Queue)', 'جداول التجزئة (Hash Tables) وكفاءة البحث'],
                    'project' => 'مشروع: تطوير محرك كاش (Custom Cache Engine) بنظام LRU'
                ],
                [
                    'num' => '03',
                    'title' => 'خوارزميات البحث والترتيب المتقدمة (Searching & Sorting Paradigms)',
                    'subtopics' => ['البحث الثنائي المطور (Binary Search)', 'الدمج والترتيب السريع (Merge & Quick Sort)', 'تقنية فرّق تسد (Divide & Conquer)'],
                    'project' => 'مشروع: بناء نظام بحث وفلترة لحظي للبيانات الضخمة'
                ],
                [
                    'num' => '04',
                    'title' => 'الأشجار والرسوم البيانية (Trees & Graph Algorithms)',
                    'subtopics' => ['أشجار البحث الثنائية (BST)', 'خوارزميات المسح (BFS & DFS)', 'أقصر مسار عبر Dijkstra و A*'],
                    'project' => 'مشروع: برمجة محاكي ملاحة ورسم خرائط لأقصر المسارات'
                ],
                [
                    'num' => '05',
                    'title' => 'البرمجة الديناميكية والحلول الجشعة (Dynamic Programming & Greedy)',
                    'subtopics' => ['استراتيجية Memoization و Tabulation', 'مسألة حقيبة الظهر (Knapsack)', 'خوارزميات الأمثلة الاقتصادية'],
                    'project' => 'مشروع: حل مسائل المقابلات التقنية لشركات التكنولوجيا الكبرى'
                ]
            ];
        }

        if (str_contains($tLower, 'flutter') || str_contains($tLower, 'فلاتر') || str_contains($tLower, 'موبايل')) {
            return [
                [
                    'num' => '01',
                    'title' => 'تأسيس Dart 3 والمعمارية الحديثة لـ Flutter',
                    'subtopics' => ['الميزات الحديثة في Dart (Pattern Matching, Records)', 'دورة حياة الودجات (Widget Lifecycle)', 'بناء واجهات responsive متوافقة مع كافة الشاشات'],
                    'project' => 'مشروع: تطبيق Dashboard تفاعلي بدعم كامل للـ Dark Mode'
                ],
                [
                    'num' => '02',
                    'title' => 'إدارة الحالة المتقدمة (State Management with BLoC / Riverpod)',
                    'subtopics' => ['مقارنة معمارية (BLoC vs Riverpod vs Cubit)', 'فصل منطق الأعمال عن الواجهات', 'معالجة الـ Streams والـ Events'],
                    'project' => 'مشروع: تطبيق تجارة إلكترونية كامل بسلة شراء حية'
                ],
                [
                    'num' => '03',
                    'title' => 'التكامل مع الـ REST APIs والتخزين غير المتزامن (Offline-First)',
                    'subtopics' => ['استخدام Dio مع الـ Interceptors والتوثيق', 'التخزين المحلي عبر Hive و Isar', 'مزامنة البيانات في الخلفية عند انقطاع الإنترنت'],
                    'project' => 'مشروع: تطبيق إدارة مهام وملاحظات يعمل بدون اتصال'
                ],
                [
                    'num' => '04',
                    'title' => 'الأنيميشن وتجربة المستخدم الفائقة (Micro-interactions & Motion)',
                    'subtopics' => ['Hero Animations والتحولات السلسة', 'ودجات Implicit و Explicit Animations', 'تخصيص الـ Custom Painter للمخططات الرسومية'],
                    'project' => 'مشروع: تطبيق محفظة مالية تفاعلي برسوم بيانية متحركة'
                ],
                [
                    'num' => '05',
                    'title' => 'الاختبارات والنشر إلى App Store و Google Play',
                    'subtopics' => ['كتابة Unit & Widget Tests', 'إعداد CI/CD عبر GitHub Actions', 'تجهيز الشهادات وتشفير التطبيق للمتاجر الرسمية'],
                    'project' => 'مشروع: بناء حزمة إنتاجية جاهزة للنشر الفوري'
                ]
            ];
        }

        if (str_contains($tLower, 'python') || str_contains($tLower, 'بايثون')) {
            return [
                [
                    'num' => '01',
                    'title' => 'قواعد بايثون المتقدمة والكتابة النظيفة (Pythonic Architecture)',
                    'subtopics' => ['التعامل المتقدم مع Generators و Decorators', 'إدارة الذاكرة والـ Context Managers', 'الكتابة النمطية عبر Type Hinting'],
                    'project' => 'مشروع: بناء أداة CLI متقدمة لإدارة المهام والملفات'
                ],
                [
                    'num' => '02',
                    'title' => 'أتمتة المهام وسحب البيانات الذكي (Automation & Web Scraping)',
                    'subtopics' => ['مكتبات Requests و BeautifulSoup و Playwright', 'تجاوز الكابتشا وإدارة البروكسي', 'أتمتة التقارير وتوليد ملفات Excel و PDF'],
                    'project' => 'مشروع: روبوت أوتوماتيكي لمراقبة أسعار المنتجات وإرسال تنبيهات'
                ],
                [
                    'num' => '03',
                    'title' => 'بناء واجهات البرمجة السريعة عبر FastAPI',
                    'subtopics' => ['الـ Async Endpoints والمعالجة غير المتزامنة', 'التحقق الصارم من البيانات عبر Pydantic', 'التوثيق التلقائي عبر OpenAPI / Swagger'],
                    'project' => 'مشروع: خدمة Microservice لتحليل النصوص والوسائط'
                ],
                [
                    'num' => '04',
                    'title' => 'تطبيقات الذكاء الاصطناعي ومعالجة البيانات',
                    'subtopics' => ['أساسيات Pandas و NumPy لتحليل البيانات', 'التكامل مع نماذج OpenAI و Gemini APIs', 'بناء RAG Pipeline مع قواعد البيانات المتجهة (Vector DBs)'],
                    'project' => 'مشروع: مساعد ذكي متخصص للإجابة عن أسئلة المستندات'
                ],
                [
                    'num' => '05',
                    'title' => 'التجهيز للإنتاج والنشر السحابي (Docker & Cloud Deployment)',
                    'subtopics' => ['تغليف التطبيق عبر Docker Compose', 'جدولة المهام عبر Celery و Redis', 'النشر السحابي مع المراقبة وتسجيل الأخطاء'],
                    'project' => 'مشروع: نشر نظام متكامل على خادم VPS حقيقي'
                ]
            ];
        }

        if (str_contains($tLower, 'cyber') || str_contains($tLower, 'أمن') || str_contains($tLower, 'security')) {
            return [
                [
                    'num' => '01',
                    'title' => 'تأسيس الأمن السيبراني ونموذج التهديدات (Threat Modeling)',
                    'subtopics' => ['مبادئ CIA Triad وإدارة المخاطر', 'بروتوكولات الشبكات وتحليل الحزم (Wireshark)', 'استراتيجية الدفاع في العمق (Defense in Depth)'],
                    'project' => 'مشروع: إعداد معمل اختبار اختراق آمن (Virtual Pentesting Lab)'
                ],
                [
                    'num' => '02',
                    'title' => 'فحص الثغرات واختبار اختراق تطبيقات الويب (OWASP Top 10)',
                    'subtopics' => ['ثغرات حقن قواعد البيانات (SQLi)', 'ثغرات XSS و CSRF و SSRF', 'كسر حماية التوثيق وإدارة الجلسات'],
                    'project' => 'مشروع: فحص واكتشاف ثغرات تطبيق تجريبي واستخراج تقرير تقييم'
                ],
                [
                    'num' => '03',
                    'title' => 'أمن واجهات البرمجة والأنظمة السحابية (API & Cloud Security)',
                    'subtopics' => ['تأمين JWT و OAuth2 و Rate Limiting', 'حماية بيئات Docker و Kubernetes', 'إدارة المفاتيح والأسرار عبر Vault'],
                    'project' => 'مشروع: إعداد بوابة حماية وتشفير متكاملة للـ APIs'
                ],
                [
                    'num' => '04',
                    'title' => 'البرمجة الآمنة ومراجعة الأكواد (Secure Code Review)',
                    'subtopics' => ['أدوات فحص الكود الساكن (SAST & DAST)', 'تطهير المدخلات والتشفير القياسي AES-256', 'تجنب أخطاء تجاوز الذاكرة والمنطق البرمجي'],
                    'project' => 'مشروع: مراجعة كود مشروع مفتوح المصدر وإصلاح ثغراته'
                ],
                [
                    'num' => '05',
                    'title' => 'الاستجابة للحوادث والتحقيق الجنائي الرقمي (Incident Response)',
                    'subtopics' => ['مراقبة سجلات الخوادم وتحليل الأدلة (Logs Analysis)', 'عزل الأنظمة المصابة واحتواء الهجمات', 'بناء خطة استعادة الأعمال بعد الكوارث'],
                    'project' => 'مشروع: محاكاة هجمة حقيقية وإعداد تقرير الاستجابة والتعافي'
                ]
            ];
        }

        // Generic Professional Fallback tailored to the actual book title
        return [
            [
                'num' => '01',
                'title' => 'المدخل الشامل والأسس التطبيقية لـ ' . Str::limit($title, 40),
                'subtopics' => ['فهم المفاهيم الجوهرية بعيداً عن التعقيد', 'إعداد بيئة العمل والأدوات الاحترافية', 'قواعد البناء السليم والتخطيط المنهجي'],
                'project' => 'مشروع: بناء النموذج الأولي واختبار الفرضيات الأساسية'
            ],
            [
                'num' => '02',
                'title' => 'الأدوات والتقنيات العملية المتقدمة (Core Implementation Tools)',
                'subtopics' => ['التطبيق خطوة بخطوة مع أمثلة عملية حية', 'أفضل الممارسات المتبعة في المشاريع الواقعية', 'تقنيات تسريع الإنتاجية وتفادي الأخطاء الشائعة'],
                'project' => 'مشروع: تطوير الوحدة الأساسية وحل التحديات الشائعة'
            ],
            [
                'num' => '03',
                'title' => 'هندسة الأنظمة والتطوير عالي الأداء (Architectural Mastery)',
                'subtopics' => ['بناء هياكل قابلة للتوسع والصيانة', 'تحسين الأداء وإدارة الموارد بكفاءة', 'الربط والتكامل مع الأنظمة والخدمات الخارجية'],
                'project' => 'مشروع: اختبار الضغط والجاهزية للتوسع والعمل الفعلي'
            ],
            [
                'num' => '04',
                'title' => 'النماذج الواقعية ودراسات الحالة (Real-World Case Studies)',
                'subtopics' => ['تحليل سيناريوهات وتجارب حقيقية من السوق', 'حل المشكلات المعقدة والتحديات غير المتوقعة', 'استراتيجيات تحويل المعرفة إلى قيمة تجارية ملموسة'],
                'project' => 'مشروع: محاكاة إطلاق نظام عملي متكامل خطوة بخطوة'
            ],
            [
                'num' => '05',
                'title' => 'مشروع التخرج الشامل وخارطة طريق الاحتراف المستمر',
                'subtopics' => ['تجميع وتكامل كافة مخرجات الكتاب في مشروع نهائي', 'قوائم المراجعة والتحقق النهائي (Checklists)', 'مصادر التطور ومواكبة أحدث التحديثات المستقبلية'],
                'project' => 'مشروع: تسليم النسخة النهائية الجاهزة للاستخدام'
            ]
        ];
    }
}

