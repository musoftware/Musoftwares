<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$subscriptions = \Modules\Tools\Models\ToolSubscription::where('status', 'active')->get();
$agentType = 'nodejs';

$pluginFileExists = function (string $slug, $latestVersion) {
    return true; // Simulate file exists
};

$paidPlugins = $subscriptions
    ->filter(fn($s) => $s->tool)
    ->filter(function ($s) use ($agentType, $pluginFileExists) {
        $tool = (object) $s->tool;
        // Suppress warning if metadata is null
        $runtime = @$tool->metadata['runtime'] ?? 'nodejs';
        return $runtime === $agentType && $pluginFileExists($tool->slug, null);
    })
    ->map(function ($s) {
        $tool = (object) $s->tool;
        return [
            'tool_slug' => $tool->slug,
        ];
    });

echo json_encode(['plugins' => $paidPlugins->values()]);
