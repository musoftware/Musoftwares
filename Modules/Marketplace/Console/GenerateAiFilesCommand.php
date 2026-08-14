<?php

namespace Modules\Marketplace\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use App\Models\BlogArticle;
use Illuminate\Support\Facades\Log;

class GenerateAiFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:generate-ai-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate static AI endpoints (/llms.txt, .well-known/*.json, sitemap.xml, robots.txt) for Marketplace services.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating Marketplace AI & SEO static files...');

        $baseUrl = config('app.url', 'https://musoftwares.com');
        $wellKnownDir = public_path('.well-known');

        if (!File::exists($wellKnownDir)) {
            File::makeDirectory($wellKnownDir, 0755, true);
        }

        // Fetch active services, categories, and blog articles
        $services = collect();
        $categories = collect();
        $articles = collect();

        try {
            $services = Service::with(['category', 'packages', 'seller'])
                ->whereNull('suspended_at')
                ->get();

            $categories = ServiceCategory::all();

            if (class_exists(BlogArticle::class)) {
                $articles = BlogArticle::published()->latest()->get();
            }
        } catch (\Throwable $e) {
            $this->warn('Database connection unavailable, generating fallback AI endpoints: ' . $e->getMessage());
            Log::error('Database connection failed in GenerateAiFilesCommand: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // 1. Generate llms.txt (Concise English Index)
        // ─────────────────────────────────────────────────────────────────────
        $llmsContent = "# Musoftwares - AI Agent Guide & Directory\n\n";
        $llmsContent .= "> Official index of services, plugins, and blog resources for LLMs and AI Agents.\n\n";
        $llmsContent .= "## Platform Overview\n";
        $llmsContent .= "Musoftwares provides professional web development, automation playbooks, SaaS plugins, and custom software services.\n";
        $llmsContent .= "Base URL: {$baseUrl}\n";
        $llmsContent .= "Full Comprehensive Index: {$baseUrl}/llms-full.txt\n\n";

        $llmsContent .= "## Machine Readable Endpoints\n";
        $llmsContent .= "- Services API: {$baseUrl}/marketplace/api/v1/services\n";
        $llmsContent .= "- Categories API: {$baseUrl}/marketplace/api/v1/categories\n";
        $llmsContent .= "- Search API: {$baseUrl}/marketplace/api/v1/search\n";
        $llmsContent .= "- OpenAPI Spec: {$baseUrl}/marketplace/api/v1/openapi.json\n\n";

        $llmsContent .= "## Service Categories\n";
        foreach ($categories as $cat) {
            $catSlug = $cat->slug ?? $cat->id;
            $llmsContent .= "- [{$cat->name}]({$baseUrl}/marketplace/categories/{$catSlug}): Discover services in this category.\n";
        }
        $llmsContent .= "\n## Featured Services\n\n";

        $servicesList = [];
        $capabilitiesList = [];

        $featuredCountEn = 0;
        foreach ($services as $service) {
            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]);
            $packages = $service->packages->map(function ($p) {
                return [
                    'name' => $p->name ?? 'Standard',
                    'price' => (float)($p->price ?? 0),
                    'delivery_days' => $p->delivery_days ?? 3,
                ];
            })->toArray();

            $minPrice = !empty($packages) ? min(array_column($packages, 'price')) : 0;

            if ($featuredCountEn < 15) {
                $llmsContent .= "### {$service->title}\n";
                $llmsContent .= "- **Category**: " . ($service->category->name ?? 'General') . "\n";
                $llmsContent .= "- **Starting Price**: \${$minPrice}\n";
                $llmsContent .= "- **Tagline**: {$service->tagline}\n";
                $llmsContent .= "- **URL**: {$serviceUrl}\n\n";
                $featuredCountEn++;
            }

            $servicesList[] = [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'category' => $service->category->name ?? 'General',
                'url' => $serviceUrl,
                'tagline' => $service->tagline,
                'starting_price' => $minPrice,
                'packages' => $packages,
                'tags' => $service->tags ?? [],
            ];

            $capabilitiesList[] = [
                'capability_id' => 'service_' . $service->id,
                'name' => $service->title,
                'description' => $service->tagline ?? $service->title,
                'endpoint' => "{$baseUrl}/marketplace/api/v1/services/{$service->id}",
                'method' => 'GET',
            ];
        }

        $llmsContent .= "## Recent Blog Insights\n\n";
        $articlesEn = $articles->where('language', 'en');
        $blogCountEn = 0;
        foreach ($articlesEn as $article) {
            if ($blogCountEn < 10) {
                $articleUrl = route('blog.show', $article->slug);
                $llmsContent .= "### {$article->title}\n";
                $llmsContent .= "- **Excerpt**: {$article->excerpt}\n";
                $llmsContent .= "- **URL**: {$articleUrl}\n\n";
                $blogCountEn++;
            }
        }

        File::put(public_path('llms.txt'), $llmsContent);
        $this->info('Created public/llms.txt');

        // ─────────────────────────────────────────────────────────────────────
        // 1.5 Generate llms-full.txt (Comprehensive English Index)
        // ─────────────────────────────────────────────────────────────────────
        $llmsFullContent = "# Musoftwares - Full AI Agent Catalog & Knowledge Base\n\n";
        $llmsFullContent .= "> Deep, comprehensive documentation and full indexes for retrieval-augmented generation (RAG) and agents.\n\n";
        $llmsFullContent .= "## Platform Overview\n";
        $llmsFullContent .= "Musoftwares provides premium software products, plugins, and custom development services.\n";
        $llmsFullContent .= "Base URL: {$baseUrl}\n\n";

        $llmsFullContent .= "## Machine Readable Endpoints & Specifications\n";
        $llmsFullContent .= "- Services API: {$baseUrl}/marketplace/api/v1/services\n";
        $llmsFullContent .= "- Categories API: {$baseUrl}/marketplace/api/v1/categories\n";
        $llmsFullContent .= "- Search API: {$baseUrl}/marketplace/api/v1/search\n";
        $llmsFullContent .= "- OpenAPI Spec: {$baseUrl}/marketplace/api/v1/openapi.json\n\n";

        $llmsFullContent .= "## Service Categories\n";
        foreach ($categories as $cat) {
            $catSlug = $cat->slug ?? $cat->id;
            $llmsFullContent .= "- **{$cat->name}**: {$baseUrl}/marketplace/categories/{$catSlug}\n";
        }
        $llmsFullContent .= "\n## Full Services Catalog\n\n";

        foreach ($services as $service) {
            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]);
            $packages = $service->packages;
            $minPrice = $packages->isNotEmpty() ? $packages->min('price') : 0;

            $llmsFullContent .= "### {$service->title}\n";
            $llmsFullContent .= "- **ID**: {$service->id}\n";
            $llmsFullContent .= "- **Category**: " . ($service->category->name ?? 'General') . "\n";
            $llmsFullContent .= "- **URL**: {$serviceUrl}\n";
            $llmsFullContent .= "- **Starting Price**: \${$minPrice}\n";
            $llmsFullContent .= "- **Tagline**: {$service->tagline}\n";
            $llmsFullContent .= "- **Description**: " . strip_tags($service->description ?? '') . "\n";
            if (!empty($service->tags)) {
                $llmsFullContent .= "- **Tags**: " . implode(', ', (array)$service->tags) . "\n";
            }
            if ($packages->isNotEmpty()) {
                $llmsFullContent .= "- **Available Packages**:\n";
                foreach ($packages as $pkg) {
                    $llmsFullContent .= "  - **{$pkg->name}** (\${$pkg->price}): {$pkg->delivery_days} days delivery, {$pkg->revisions} revisions. Description: {$pkg->description}\n";
                }
            }
            $llmsFullContent .= "\n";
        }

        $llmsFullContent .= "## Full Blog Articles Catalog\n\n";
        foreach ($articlesEn as $article) {
            $articleUrl = route('blog.show', $article->slug);
            $cleanBody = strip_tags($article->content ?? '');

            $llmsFullContent .= "### {$article->title}\n";
            $llmsFullContent .= "- **Slug**: {$article->slug}\n";
            $llmsFullContent .= "- **URL**: {$articleUrl}\n";
            $llmsFullContent .= "- **Published At**: " . ($article->published_at ? $article->published_at->toIso8601String() : $article->created_at->toIso8601String()) . "\n";
            $llmsFullContent .= "- **Excerpt**: {$article->excerpt}\n";
            $llmsFullContent .= "- **Content**:\n{$cleanBody}\n\n";
        }

        File::put(public_path('llms-full.txt'), $llmsFullContent);
        $this->info('Created public/llms-full.txt');

        // ─────────────────────────────────────────────────────────────────────
        // 1.6 Generate llms-ar.txt (Concise Arabic Index)
        // ─────────────────────────────────────────────────────────────────────
        $llmsArContent = "# ميوزوفت ويرز - دليل وعملاء الذكاء الاصطناعي\n\n";
        $llmsArContent .= "> الفهرس الرسمي للخدمات والبرمجيات والمقالات المتاحة لوكلاء ونماذج الذكاء الاصطناعي.\n\n";
        $llmsArContent .= "## نظرة عامة على المنصة\n";
        $llmsArContent .= "توفر ميوزوفت ويرز خدمات تطوير المواقع، البرمجيات الجاهزة، الإضافات البرمجية للمتاجر والمواقع، وحلول الأتمتة المخصصة.\n";
        $llmsArContent .= "رابط المنصة الرئيسي: {$baseUrl}\n";
        $llmsArContent .= "الفهرس الشامل والتفصيلي: {$baseUrl}/llms-full-ar.txt\n\n";

        $llmsArContent .= "## روابط برمجية سهلة القراءة\n";
        $llmsArContent .= "- واجهة برمجة الخدمات: {$baseUrl}/marketplace/api/v1/services\n";
        $llmsArContent .= "- واجهة برمجة التصنيفات: {$baseUrl}/marketplace/api/v1/categories\n";
        $llmsArContent .= "- واجهة برمجة البحث: {$baseUrl}/marketplace/api/v1/search\n";
        $llmsArContent .= "- مواصفات OpenAPI: {$baseUrl}/marketplace/api/v1/openapi.json\n\n";

        $llmsArContent .= "## تصنيفات الخدمات\n";
        foreach ($categories as $cat) {
            $catSlug = $cat->slug ?? $cat->id;
            $llmsArContent .= "- [{$cat->name}]({$baseUrl}/marketplace/categories/{$catSlug}): تصفح الخدمات المتاحة.\n";
        }
        $llmsArContent .= "\n## الخدمات المميزة\n\n";

        $featuredCountAr = 0;
        foreach ($services as $service) {
            $titleAr = $service->title_translations['ar'] ?? $service->title;
            $taglineAr = $service->tagline_translations['ar'] ?? $service->tagline;
            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]) . '?lang=ar';

            $minPrice = $service->packages->isNotEmpty() ? $service->packages->min('price') : 0;

            if ($featuredCountAr < 15) {
                $llmsArContent .= "### {$titleAr}\n";
                $llmsArContent .= "- **التصنيف**: " . ($service->category->name ?? 'عام') . "\n";
                $llmsArContent .= "- **سعر البدء**: \${$minPrice}\n";
                $llmsArContent .= "- **الوصف المختصر**: {$taglineAr}\n";
                $llmsArContent .= "- **الرابط**: {$serviceUrl}\n\n";
                $featuredCountAr++;
            }
        }

        $llmsArContent .= "## مقالات المدونة الحديثة\n\n";
        $articlesAr = $articles->where('language', 'ar');
        $blogCountAr = 0;
        foreach ($articlesAr as $article) {
            if ($blogCountAr < 10) {
                $articleUrl = route('blog.show', $article->slug) . '?lang=ar';
                $llmsArContent .= "### {$article->title}\n";
                $llmsArContent .= "- **مقتطف**: {$article->excerpt}\n";
                $llmsArContent .= "- **الرابط**: {$articleUrl}\n\n";
                $blogCountAr++;
            }
        }

        File::put(public_path('llms-ar.txt'), $llmsArContent);
        $this->info('Created public/llms-ar.txt');

        // ─────────────────────────────────────────────────────────────────────
        // 1.7 Generate llms-full-ar.txt (Comprehensive Arabic Index)
        // ─────────────────────────────────────────────────────────────────────
        $llmsFullArContent = "# ميوزوفت ويرز - الفهرس الشامل للبرمجيات وقاعدة المعرفة\n\n";
        $llmsFullArContent .= "> قاعدة المعرفة والخدمات التفصيلية لدعم تقنية توليد الإجابات المعزز بالاسترجاع (RAG) وعملاء الذكاء الاصطناعي.\n\n";
        $llmsFullArContent .= "## نظرة عامة على المنصة\n";
        $llmsFullArContent .= "ميوزوفت ويرز تقدم برمجيات ممتازة، إضافات جاهزة، وتطوير برمجيات وحلول رقمية مخصصة.\n";
        $llmsFullArContent .= "رابط المنصة الرئيسي: {$baseUrl}\n\n";

        $llmsFullArContent .= "## الروابط البرمجية والمواصفات المعيارية\n";
        $llmsFullArContent .= "- واجهة برمجة الخدمات: {$baseUrl}/marketplace/api/v1/services\n";
        $llmsFullArContent .= "- واجهة برمجة التصنيفات: {$baseUrl}/marketplace/api/v1/categories\n";
        $llmsFullArContent .= "- واجهة برمجة البحث: {$baseUrl}/marketplace/api/v1/search\n";
        $llmsFullArContent .= "- مواصفات OpenAPI: {$baseUrl}/marketplace/api/v1/openapi.json\n\n";

        $llmsFullArContent .= "## تصنيفات الخدمات\n";
        foreach ($categories as $cat) {
            $catSlug = $cat->slug ?? $cat->id;
            $llmsFullArContent .= "- **{$cat->name}**: {$baseUrl}/marketplace/categories/{$catSlug}\n";
        }
        $llmsFullArContent .= "\n## دليل الخدمات الشامل\n\n";

        foreach ($services as $service) {
            $titleAr = $service->title_translations['ar'] ?? $service->title;
            $taglineAr = $service->tagline_translations['ar'] ?? $service->tagline;
            $descAr = $service->description_translations['ar'] ?? $service->description;
            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]) . '?lang=ar';
            $packages = $service->packages;
            $minPrice = $packages->isNotEmpty() ? $packages->min('price') : 0;

            $llmsFullArContent .= "### {$titleAr}\n";
            $llmsFullArContent .= "- **معرف الخدمة (ID)**: {$service->id}\n";
            $llmsFullArContent .= "- **التصنيف**: " . ($service->category->name ?? 'عام') . "\n";
            $llmsFullArContent .= "- **رابط الخدمة**: {$serviceUrl}\n";
            $llmsFullArContent .= "- **سعر البدء**: \${$minPrice}\n";
            $llmsFullArContent .= "- **الوصف المختصر**: {$taglineAr}\n";
            $llmsFullArContent .= "- **تفاصيل الخدمة**: " . strip_tags($descAr ?? '') . "\n";
            if (!empty($service->tags)) {
                $llmsFullArContent .= "- **الوسوم**: " . implode(', ', (array)$service->tags) . "\n";
            }
            if ($packages->isNotEmpty()) {
                $llmsFullArContent .= "- **الباقات المتاحة**:\n";
                foreach ($packages as $pkg) {
                    $llmsFullArContent .= "  - **{$pkg->name}** (\${$pkg->price}): توصيل خلال {$pkg->delivery_days} يوم، عدد التعديلات المتاحة: {$pkg->revisions}. التفاصيل: {$pkg->description}\n";
                }
            }
            $llmsFullArContent .= "\n";
        }

        $llmsFullArContent .= "## فهرس المقالات الكامل باللغة العربية\n\n";
        foreach ($articlesAr as $article) {
            $articleUrl = route('blog.show', $article->slug) . '?lang=ar';
            $cleanBody = strip_tags($article->content ?? '');

            $llmsFullArContent .= "### {$article->title}\n";
            $llmsFullArContent .= "- **المعرف الفريد (Slug)**: {$article->slug}\n";
            $llmsFullArContent .= "- **الرابط**: {$articleUrl}\n";
            $llmsFullArContent .= "- **تاريخ النشر**: " . ($article->published_at ? $article->published_at->toIso8601String() : $article->created_at->toIso8601String()) . "\n";
            $llmsFullArContent .= "- **مقتطف المقال**: {$article->excerpt}\n";
            $llmsFullArContent .= "- **محتوى المقال**:\n{$cleanBody}\n\n";
        }

        File::put(public_path('llms-full-ar.txt'), $llmsFullArContent);
        $this->info('Created public/llms-full-ar.txt');

        // ─────────────────────────────────────────────────────────────────────
        // 2. Generate .well-known/ai.json
        // ─────────────────────────────────────────────────────────────────────
        $aiJson = [
            'schema_version' => 'v1',
            'name_for_human' => 'Musoftwares Marketplace',
            'name_for_model' => 'musoftwares_marketplace',
            'description_for_human' => 'Discover and order software development, automation, and SaaS services.',
            'description_for_model' => 'Plugin for discovering software services, pricing, and placing orders on Musoftwares Marketplace.',
            'auth' => [
                'type' => 'user_http',
                'authorization_type' => 'bearer',
            ],
            'api' => [
                'type' => 'openapi',
                'url' => "{$baseUrl}/marketplace/api/v1/openapi.json",
                'is_user_authenticated' => false,
            ],
            'logo_url' => "{$baseUrl}/v8main/img/logo.png",
            'contact_email' => 'support@musoftwares.com',
            'legal_info_url' => "{$baseUrl}/terms",
        ];
        File::put($wellKnownDir . '/ai.json', json_encode($aiJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Created public/.well-known/ai.json');

        // ─────────────────────────────────────────────────────────────────────
        // 3. Generate .well-known/services.json
        // ─────────────────────────────────────────────────────────────────────
        $servicesJson = [
            'updated_at' => now()->toIso8601String(),
            'total_services' => count($servicesList),
            'services' => $servicesList,
        ];
        File::put($wellKnownDir . '/services.json', json_encode($servicesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Created public/.well-known/services.json');

        // ─────────────────────────────────────────────────────────────────────
        // 4. Generate .well-known/capabilities.json
        // ─────────────────────────────────────────────────────────────────────
        $capabilitiesJson = [
            'updated_at' => now()->toIso8601String(),
            'platform' => 'Musoftwares Marketplace Engine',
            'version' => '1.0.0',
            'capabilities' => array_merge([
                [
                    'capability_id' => 'search_services',
                    'name' => 'Search Marketplace Services',
                    'endpoint' => "{$baseUrl}/marketplace/api/v1/search",
                    'method' => 'GET',
                    'parameters' => ['q', 'category_id', 'min_price', 'max_price', 'tag']
                ],
                [
                    'capability_id' => 'create_order',
                    'name' => 'Create Service Order',
                    'endpoint' => "{$baseUrl}/marketplace/api/v1/orders",
                    'method' => 'POST',
                    'parameters' => ['service_id', 'package_id', 'requirements_data']
                ],
            ], $capabilitiesList),
        ];
        File::put($wellKnownDir . '/capabilities.json', json_encode($capabilitiesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Created public/.well-known/capabilities.json');

        // ─────────────────────────────────────────────────────────────────────
        // 5. Delete static public/sitemap.xml if existing to avoid blocking dynamic SitemapController
        // ─────────────────────────────────────────────────────────────────────
        $staticSitemap = public_path('sitemap.xml');
        if (File::exists($staticSitemap)) {
            File::delete($staticSitemap);
            $this->info('Deleted public/sitemap.xml to allow dynamic sitemap routing.');
        }

        // ─────────────────────────────────────────────────────────────────────
        // 6. Generate robots.txt welcoming AI bots while restricting administrative paths
        // ─────────────────────────────────────────────────────────────────────
        $robotsContent = "# LLM Search Agent Indexes:\n";
        $robotsContent .= "# English: {$baseUrl}/llms.txt\n";
        $robotsContent .= "# Arabic: {$baseUrl}/llms-ar.txt\n\n";

        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Allow: /\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /seller/\n";
        $robotsContent .= "Disallow: /marketplace/dashboard\n\n";

        $robotsContent .= "User-agent: GPTBot\n";
        $robotsContent .= "Allow: /\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /seller/\n\n";

        $robotsContent .= "User-agent: ClaudeBot\n";
        $robotsContent .= "Allow: /\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /seller/\n\n";

        $robotsContent .= "User-agent: Google-Extended\n";
        $robotsContent .= "Allow: /\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /seller/\n\n";

        $robotsContent .= "User-agent: Applebot-Extended\n";
        $robotsContent .= "Allow: /\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /seller/\n\n";

        $robotsContent .= "Sitemap: {$baseUrl}/sitemap.xml\n";
        File::put(public_path('robots.txt'), $robotsContent);
        $this->info('Created public/robots.txt');

        $this->info('All Marketplace AI & SEO static files generated successfully!');
        return 0;
    }
}
