const fs = require('fs');

const models = [
    'Modules/Tools/Models/Tool.php',
    'Modules/Tools/Models/ToolLicense.php',
    'Modules/Tools/Models/ToolPricingPlan.php',
    'Modules/Tools/Models/ToolSubscription.php'
];

for (const file of models) {
    if (!fs.existsSync(file)) {
        console.log(`File not found: ${file}`);
        continue;
    }
    
    let content = fs.readFileSync(file, 'utf8');
    
    // Remove 'max_devices' from fillable
    content = content.replace(/'max_devices',\s*/g, '');
    
    // Remove 'max_devices' from casts
    content = content.replace(/'max_devices'\s*=>\s*'[a-zA-Z_]+',\s*/g, '');

    // For ToolLicense.php, remove the activeDevices method entirely
    if (file.includes('ToolLicense.php')) {
        content = content.replace(/\s*public function activeDevices\(\)[\s\S]*?\{[\s\S]*?\}/g, '');
        // Also remove device check in isValid
        content = content.replace(/\s*&& \$this->activeDevices\(\)->count\(\) < \$this->max_devices/g, '');
    }

    // For ToolSubscription.php, remove plan->max_devices from license creation
    if (file.includes('ToolSubscription.php')) {
        content = content.replace(/\s*'max_devices'\s*=>\s*\$this->plan->max_devices\s*\?\?\s*\d+,/g, '');
    }

    fs.writeFileSync(file, content);
    console.log(`Processed ${file}`);
}
