<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tools = \Modules\Tools\Models\Tool::with('pricingPlans', 'latestVersion')->get();
foreach ($tools as $tool) {
    echo "✅ " . $tool->title . " | slug: " . $tool->slug . " | " . $tool->pricingPlans->count() . " plans | v" . ($tool->latestVersion?->version ?? 'none') . "\n";
}

$planCount = \Modules\Tools\Models\ToolPricingPlan::count();
$versionCount = \Modules\Tools\Models\ToolVersion::count();
echo "\nTotal: " . $tools->count() . " tools | " . $planCount . " plans | " . $versionCount . " versions\n";
