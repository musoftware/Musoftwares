<?php

/**
 * Dynamic E2E Route Manifest Generator
 * Discovers static routes and queries live database entities to build a comprehensive
 * audit matrix for Playwright / Automated QA.
 */

$isDbOnline = false;

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
    } catch (\Throwable $e) {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        \Illuminate\Support\Facades\DB::purge();
        \Illuminate\Support\Facades\DB::connection('sqlite')->getPdo();
    }
    $isDbOnline = true;
} catch (\Throwable $e) {
    $isDbOnline = false;
}

$manifest = [
    'generated_at' => date('c'),
    'db_online' => $isDbOnline,
    'public_routes' => [
        '/',
        '/login',
        '/register',
        '/portfolio',
        '/blog',
        '/install-app',
    ],
    'client_routes' => [
        '/dashboard',
        '/profile',
        '/transactions',
        '/tickets',
        '/invoices',
        '/erp/dashboard',
        '/erp/clients',
        '/erp/projects',
        '/erp/invoices',
        '/erp/expenses',
        '/booking/dashboard',
        '/booking/providers',
        '/booking/appointments',
        '/booking/exceptions',
        '/crm/dashboard',
        '/crm/pipelines',
        '/crm/leads',
        '/crm/sequences',
        '/crm/campaigns',
    ],
    'admin_routes' => [
        '/admin/dashboard',
        '/admin/users',
        '/admin/plans',
        '/admin/kyc',
        '/admin/reports',
        '/admin/projects',
        '/admin/contracts',
        '/admin/busy-times',
        '/admin/settings',
        '/admin/blog-articles',
        '/admin/coupons',
        '/admin/vouchers',
        '/admin/transactions',
        '/admin/currencies',
        '/admin/payouts',
        '/admin/tickets',
        '/admin/website-services',
    ],
    'dynamic_entities' => [
        'blog_posts' => [],
        'portfolio_items' => [],
        'website_services' => [],
        'contracts' => [],
    ]
];

if ($isDbOnline) {
    try {
        // 1. Discover Blog Posts
        if (class_exists(\App\Models\BlogArticle::class)) {
            $blogSlugs = \App\Models\BlogArticle::where('status', 'published')
                ->orWhereNotNull('slug')
                ->latest()
                ->take(10)
                ->pluck('slug')
                ->filter()
                ->values()
                ->toArray();

            foreach ($blogSlugs as $slug) {
                $route = "/blog/{$slug}";
                $manifest['public_routes'][] = $route;
                $manifest['dynamic_entities']['blog_posts'][] = $route;
            }
        }

        // 2. Discover Website Services & Portfolio
        if (class_exists(\App\Models\WebsiteService::class)) {
            $serviceSlugs = \App\Models\WebsiteService::latest()
                ->take(10)
                ->pluck('slug')
                ->filter()
                ->values()
                ->toArray();

            foreach ($serviceSlugs as $slug) {
                $serviceRoute = "/services/{$slug}";
                $portfolioRoute = "/portfolio/{$slug}";
                $manifest['public_routes'][] = $serviceRoute;
                $manifest['public_routes'][] = $portfolioRoute;
                $manifest['dynamic_entities']['website_services'][] = $serviceRoute;
                $manifest['dynamic_entities']['portfolio_items'][] = $portfolioRoute;
            }
        }

        // 3. Discover Public Contracts
        if (class_exists(\App\Models\Contract::class)) {
            $contractUuids = \App\Models\Contract::latest()
                ->take(5)
                ->pluck('uuid')
                ->filter()
                ->values()
                ->toArray();

            foreach ($contractUuids as $uuid) {
                $route = "/c/{$uuid}";
                $manifest['public_routes'][] = $route;
                $manifest['dynamic_entities']['contracts'][] = $route;
            }
        }

        // 4. Discover Dynamic Admin entities if any (e.g. users edit, invoices show)
        if (class_exists(\App\Models\Invoice::class)) {
            $invoice = \App\Models\Invoice::latest()->first();
            if ($invoice) {
                $manifest['admin_routes'][] = "/admin/invoices/{$invoice->id}";
            }
        }

    } catch (\Throwable $e) {
        // Fallback gracefully on query error
    }
}

// Deduplicate all arrays
$manifest['public_routes'] = array_values(array_unique($manifest['public_routes']));
$manifest['client_routes'] = array_values(array_unique($manifest['client_routes']));
$manifest['admin_routes'] = array_values(array_unique($manifest['admin_routes']));

$outputPath = __DIR__ . '/../storage/app/e2e_manifest.json';
if (!is_dir(dirname($outputPath))) {
    mkdir(dirname($outputPath), 0777, true);
}

file_put_contents($outputPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Generated Dynamic E2E Manifest:\n";
echo " - Public / Edge Routes: " . count($manifest['public_routes']) . "\n";
echo " - Client / Tenant Routes: " . count($manifest['client_routes']) . "\n";
echo " - Admin Routes: " . count($manifest['admin_routes']) . "\n";
echo " - DB Status: " . ($isDbOnline ? "ONLINE" : "OFFLINE") . "\n";
echo "Saved to: storage/app/e2e_manifest.json\n";
