const fs = require('fs');

const controllers = [
    'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/Admin/Tools/AdminToolController.php',
    'Modules/Tools/Http/Controllers/SubscriptionController.php',
    'Modules/Tools/Http/Controllers/MarketplaceController.php',
    'Modules/Tools/Http/Controllers/LicenseController.php',
    'Modules/Tools/Http/Controllers/Api/LicenseController.php'
];

for (const file of controllers) {
    if (!fs.existsSync(file)) {
        console.log(`File not found: ${file}`);
        continue;
    }
    
    let content = fs.readFileSync(file, 'utf8');
    
    // AdminToolController validations
    content = content.replace(/'max_devices'\s*=>\s*\['required',[^\]]*\],\s*/g, '');
    
    // Object/Array assignments
    content = content.replace(/'max_devices'\s*=>\s*[^,]+,\s*/g, '');
    content = content.replace(/'active_devices'\s*=>\s*[^,]+,\s*/g, '');
    
    // Eager loading strings: 'plan:id,name,max_devices' -> 'plan:id,name'
    content = content.replace(/'plan:id,name,max_devices'/g, "'plan:id,name'");
    
    // API logic handling device limits
    if (file.includes('Api/LicenseController.php')) {
        // Remove device limit check
        const checkRegex = /if\s*\(\$license->activeDevices\(\)->count\(\)\s*>=\s*\$license->max_devices\)\s*\{[\s\S]*?\}/g;
        content = content.replace(checkRegex, '');
        
        // Remove activeDevices mapping
        content = content.replace(/'active_devices'\s*=>\s*\$license->activeDevices->map\([^)]*\),\s*/g, '');
        content = content.replace(/'max'\s*=>\s*\$license->max_devices,\s*/g, '');
    }

    fs.writeFileSync(file, content);
    console.log(`Processed ${file}`);
}
