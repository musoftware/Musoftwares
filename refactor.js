const fs = require('fs');
const path = require('path');

const dirs = [
    'Modules/CRM/Http/Controllers/WhatsApp',
    'Modules/CRM/Http/Controllers/WhatsAppCampaign'
];

const apiDirs = [
    'Modules/CRM/Http/Controllers/Api/WhatsApp',
    'Modules/CRM/Http/Controllers/Api/WhatsAppCampaign'
];

// Ensure API dirs exist
apiDirs.forEach(dir => {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
});

let webPhp = fs.readFileSync('Modules/CRM/routes/web.php', 'utf8');

// Mixed controllers that need manual splitting (or we skip them for now and handle manually)
const mixedControllers = [
    'InboxController.php',
    'TemplateController.php',
    'CampaignController.php',
    'AudienceController.php',
    'AnalyticsController.php' // In WhatsAppCampaign
];

for (const dir of dirs) {
    if (!fs.existsSync(dir)) continue;
    const files = fs.readdirSync(dir).filter(f => f.endsWith('.php'));
    for (const file of files) {
        const filePath = path.join(dir, file);
        const content = fs.readFileSync(filePath, 'utf8');
        
        // If it's a mixed controller, we skip automatic move and will handle manually
        if (mixedControllers.includes(file)) {
            console.log(`Skipping mixed controller: ${file}`);
            continue;
        }

        const isPureApi = content.includes('response()->json') && !content.includes('Inertia::render');
        const isAlreadyApi = content.includes('namespace Modules\\CRM\\Http\\Controllers\\Api');
        
        if (isPureApi && !isAlreadyApi) {
            console.log(`Moving ${file} to Api...`);
            
            // 1. Move file
            const newDir = dir.replace('Modules/CRM/Http/Controllers', 'Modules/CRM/Http/Controllers/Api');
            const newPath = path.join(newDir, file);
            
            // 2. Update namespace
            const newContent = content.replace(
                /namespace Modules\\CRM\\Http\\Controllers\\(WhatsApp|WhatsAppCampaign);/,
                'namespace Modules\\CRM\\Http\\Controllers\\Api\\$1;'
            );
            
            fs.writeFileSync(newPath, newContent);
            fs.unlinkSync(filePath);
            
            // 3. Update web.php
            // If it's imported via use statement:
            const oldUse = `use Modules\\CRM\\Http\\Controllers\\${dir.split('/').pop()}\\${file.replace('.php', '')};`;
            const newUse = `use Modules\\CRM\\Http\\Controllers\\Api\\${dir.split('/').pop()}\\${file.replace('.php', '')};`;
            webPhp = webPhp.replace(oldUse, newUse);
            
            // If it's FQCN inline:
            const oldFQCN = `\\Modules\\CRM\\Http\\Controllers\\${dir.split('/').pop()}\\${file.replace('.php', '')}`;
            const newFQCN = `\\Modules\\CRM\\Http\\Controllers\\Api\\${dir.split('/').pop()}\\${file.replace('.php', '')}`;
            webPhp = webPhp.split(oldFQCN).join(newFQCN);
        }
    }
}

fs.writeFileSync('Modules/CRM/routes/web.php', webPhp);
console.log('Done refactoring pure APIs!');
