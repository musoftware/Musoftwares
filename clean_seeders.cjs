const fs = require('fs');
const glob = require('glob');

const seederFiles = [
    'Modules/Tools/Database/Seeders/ToolsSeeder.php',
    'Modules/Tools/Database/Migrations/2026_05_19_000003_seed_whatsapp_tools.php',
    'Modules/Tools/Database/Migrations/2026_05_19_000004_seed_wa_ai_agent.php',
    'Modules/Tools/Database/Migrations/2026_05_19_000005_create_wa_funnels_table.php'
];

for (const file of seederFiles) {
    if (!fs.existsSync(file)) {
        console.log(`File not found: ${file}`);
        continue;
    }
    
    let content = fs.readFileSync(file, 'utf8');
    
    // Remove 'max_devices' => X,
    content = content.replace(/\s*'max_devices'\s*=>\s*\d+,/g, '');
    
    // Remove features like '1 device', '3 active devices', '10 active devices'
    content = content.replace(/'\d+\s+(?:active\s+)?device(?:s)?',?\s*/g, '');
    
    // Let's ensure no empty strings left in features array if we didn't remove the comma properly
    // Actually, maybe some files have the string in features without a trailing comma, leaving a dangling comma.
    content = content.replace(/,\s*,/g, ',');
    content = content.replace(/\[\s*,/g, '[');
    content = content.replace(/,\s*\]/g, ']');

    fs.writeFileSync(file, content);
    console.log(`Processed ${file}`);
}
