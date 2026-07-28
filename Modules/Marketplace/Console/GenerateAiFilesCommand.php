<?php

namespace Modules\Marketplace\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;

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

        // Fetch active services and categories
        $services = collect();
        $categories = collect();

        try {
            $services = Service::with(['category', 'packages', 'seller'])
                ->whereNull('suspended_at')
                ->get();

            $categories = ServiceCategory::all();
        } catch (\Throwable $e) {
            $this->warn('Database connection unavailable, generating fallback AI endpoints: ' . $e->getMessage());
        }


        // 1. Generate llms.txt
        $llmsContent = "# Musoftwares Marketplace - AI Agent Guide\n\n";
        $llmsContent .= "> Official documentation and index of services for LLMs and AI Agents.\n\n";
        $llmsContent .= "## Platform Overview\n";
        $llmsContent .= "Musoftwares Marketplace provides web development, automation tools, SaaS plugins, and custom software services.\n";
        $llmsContent .= "Base URL: {$baseUrl}\n\n";
        $llmsContent .= "## Machine Readable Endpoints\n";
        $llmsContent .= "- Services API: {$baseUrl}/marketplace/api/v1/services\n";
        $llmsContent .= "- Categories API: {$baseUrl}/marketplace/api/v1/categories\n";
        $llmsContent .= "- Search API: {$baseUrl}/marketplace/api/v1/search\n";
        $llmsContent .= "- Autocomplete API: {$baseUrl}/marketplace/api/v1/search/autocomplete\n";
        $llmsContent .= "- OpenAPI Spec: {$baseUrl}/marketplace/api/v1/openapi.json\n\n";

        $llmsContent .= "## Service Categories\n";
        foreach ($categories as $cat) {
            $catSlug = $cat->slug ?? $cat->id;
            $llmsContent .= "- **{$cat->name}**: {$baseUrl}/marketplace/categories/{$catSlug}\n";
        }
        $llmsContent .= "\n## Available Services\n\n";

        $servicesList = [];
        $capabilitiesList = [];

        foreach ($services as $service) {
            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]);
            $packages = $service->packages->map(function ($p) {
                return [
                    'name' => $p->name ?? 'Standard',
                    'price' => (float)($p->price ?? 0),
                    'delivery_days' => $p->delivery_days ?? 1,
                ];
            })->toArray();

            $minPrice = !empty($packages) ? min(array_column($packages, 'price')) : 0;

            $llmsContent .= "### {$service->title}\n";
            $llmsContent .= "- **ID**: {$service->id}\n";
            $llmsContent .= "- **Category**: " . ($service->category->name ?? 'General') . "\n";
            $llmsContent .= "- **URL**: {$serviceUrl}\n";
            $llmsContent .= "- **Starting Price**: \${$minPrice}\n";
            $llmsContent .= "- **Tagline**: {$service->tagline}\n";
            $llmsContent .= "- **Description**: " . strip_tags($service->description ?? '') . "\n";
            if (!empty($service->tags)) {
                $llmsContent .= "- **Tags**: " . implode(', ', (array)$service->tags) . "\n";
            }
            $llmsContent .= "\n";

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

        File::put(public_path('llms.txt'), $llmsContent);
        $this->info('Created public/llms.txt');

        // 2. Generate .well-known/ai.json
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

        // 3. Generate .well-known/services.json
        $servicesJson = [
            'updated_at' => now()->toIso8601String(),
            'total_services' => count($servicesList),
            'services' => $servicesList,
        ];
        File::put($wellKnownDir . '/services.json', json_encode($servicesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Created public/.well-known/services.json');

        // 4. Generate .well-known/capabilities.json
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

        // 5. Generate sitemap.xml
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $sitemap .= "  <url>\n    <loc>{$baseUrl}/marketplace/services</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";

        foreach ($categories as $cat) {
            $catSlug = $cat->slug ?? $cat->id;
            $sitemap .= "  <url>\n    <loc>{$baseUrl}/marketplace/categories/{$catSlug}</loc>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
        }

        foreach ($services as $service) {
            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]);
            $updatedAt = ($service->updated_at ?? now())->toAtomString();
            $sitemap .= "  <url>\n    <loc>{$serviceUrl}</loc>\n    <lastmod>{$updatedAt}</lastmod>\n    <changefreq>daily</changefreq>\n    <priority>0.9</priority>\n  </url>\n";
        }

        $sitemap .= '</urlset>';
        File::put(public_path('sitemap.xml'), $sitemap);
        $this->info('Created public/sitemap.xml');

        // 6. Generate robots.txt if not existing or update it
        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Allow: /\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /seller/\n";
        $robotsContent .= "Disallow: /marketplace/dashboard\n\n";
        $robotsContent .= "Sitemap: {$baseUrl}/sitemap.xml\n";
        File::put(public_path('robots.txt'), $robotsContent);
        $this->info('Created public/robots.txt');

        $this->info('All Marketplace AI & SEO static files generated successfully!');
        return 0;
    }
}
