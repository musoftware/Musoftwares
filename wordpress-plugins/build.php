<?php

$pluginDir = __DIR__ . '/musoftware-sms-gateway';
$pluginFile = $pluginDir . '/musoftware-sms-gateway.php';
$outputDir = dirname(__DIR__) . '/public/downloads';
$baseZipName = 'musoftware-sms-gateway.zip';

// 1. Read the plugin file
$content = file_get_contents($pluginFile);

// 2. Extract current version
if (preg_match('/Version:\s*(\d+\.\d+\.)(\d+)/', $content, $matches)) {
    $majorMinor = $matches[1]; // e.g. "2.0."
    $patch = (int)$matches[2]; // e.g. "0"
    
    // Increment patch version
    $newPatch = $patch + 1;
    $newVersion = $majorMinor . $newPatch;
    
    echo "Bumping version from " . $matches[0] . " to Version: " . $newVersion . "\n";
    
    // 3. Replace version in Header
    $content = preg_replace('/Version:\s*\d+\.\d+\.\d+/', 'Version:           ' . $newVersion, $content);
    
    // 4. Replace version in define constant
    $content = preg_replace("/define\(\s*'MUSOFTWARE_SMS_GATEWAY_VERSION',\s*'(\d+\.\d+\.\d+)'\s*\);/", "define( 'MUSOFTWARE_SMS_GATEWAY_VERSION', '" . $newVersion . "' );", $content);
    
    // 5. Save the updated file
    file_put_contents($pluginFile, $content);
    echo "Successfully updated musoftware-sms-gateway.php to version {$newVersion}\n";
} else {
    echo "Could not find version string in plugin file.\n";
    exit(1);
}

// 6. Ensure output directory exists
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// 7. Remove old zip if exists
$zipPath = $outputDir . '/' . $baseZipName;
if (file_exists($zipPath)) {
    unlink($zipPath);
}

// 8. Run tar command
echo "Zipping the plugin using tar...\n";
chdir(__DIR__);
$command = sprintf('tar -a -c -f "%s" "musoftware-sms-gateway"', $zipPath);
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    echo "Done! Generated:\n";
    echo "- {$zipPath}\n";
} else {
    echo "Failed to create ZIP file.\n";
    exit(1);
}
