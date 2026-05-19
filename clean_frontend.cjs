const fs = require('fs');

const frontendFiles = [
    'resources/js/Pages/Tools/Subscribe.tsx',
    'resources/js/Pages/Tools/Show.tsx',
    'resources/js/Pages/Tools/MyLicenses.tsx',
    'resources/js/Pages/Dashboard.tsx',
    'resources/js/Pages/Admin/Tools/Create.tsx',
    'resources/js/Pages/Admin/Tools/Edit.tsx'
];

for (const file of frontendFiles) {
    if (!fs.existsSync(file)) {
        console.log(`File not found: ${file}`);
        continue;
    }
    
    let content = fs.readFileSync(file, 'utf8');

    // Subscribe.tsx
    if (file.includes('Subscribe.tsx')) {
        content = content.replace(/max_devices:\s*number;\s*/g, '');
        content = content.replace(/and use it on your devices\./g, 'and unlock its full potential.');
        content = content.replace(/<p className="text-xs text-slate-400 mt-0\.5">Up to \{plan\.max_devices\} device\{plan\.max_devices > 1 \? 's' : ''\}<\/p>/g, '');
    }

    // Show.tsx
    if (file.includes('Show.tsx')) {
        content = content.replace(/max_devices:\s*number;\s*/g, '');
        content = content.replace(/active_devices:\s*number;\s*/g, '');
        content = content.replace(/<p className="text-xs text-slate-400 mt-0\.5">Up to \{p\.max_devices\} device\{p\.max_devices > 1 \? 's' : ''\}<\/p>/g, '');
    }

    // MyLicenses.tsx
    if (file.includes('MyLicenses.tsx')) {
        content = content.replace(/max_devices:\s*number;\s*/g, '');
        content = content.replace(/active_devices:\s*number;\s*/g, '');
        content = content.replace(/const devicePct =[\s\S]*?;/g, '');
        // Remove the whole progress bar block
        const pbRegex = /<div className="flex items-center justify-between mb-1\.5">[\s\S]*?<span className="font-semibold text-slate-800">\{lic\.active_devices\} \/ \{lic\.max_devices\}<\/span>[\s\S]*?<\/div>[\s\S]*?<div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">[\s\S]*?<\/div>/g;
        content = content.replace(pbRegex, '');
        // Remove Devices button
        const btnRegex = /<Button[\s\S]*?onClick=\{\(\) => router\.visit\(route\('tools\.devices', lic\.id\)\)\}[\s\S]*?Devices \(\{lic\.active_devices\}\)[\s\S]*?<\/Button>/g;
        content = content.replace(btnRegex, '');
    }

    // Dashboard.tsx
    if (file.includes('Dashboard.tsx')) {
        content = content.replace(/active_devices:\s*number;\s*/g, '');
        content = content.replace(/max_devices:\s*number;\s*/g, '');
        content = content.replace(/<p className="text-\[10px\] text-text-muted">\{lic\.active_devices\}\/\{lic\.max_devices\} devices<\/p>/g, '');
    }

    // Admin Create/Edit
    if (file.includes('Create.tsx') || file.includes('Edit.tsx')) {
        content = content.replace(/max_devices:\s*number;\s*/g, '');
        content = content.replace(/max_devices:\s*(?:3|tool\.max_devices),/g, '');
        
        // Remove the form group for Max Devices
        const fieldRegex = /<div>\s*<Label htmlFor="max_devices">Max Devices<\/Label>\s*<Input\s*id="max_devices"\s*type="number"\s*min="1"\s*value=\{data\.max_devices\}\s*onChange=\{e => setData\('max_devices', parseInt\(e\.target\.value\)\)\}\s*className="mt-1\.5"\s*\/>\s*<InputError message=\{errors\.max_devices\} className="mt-2" \/>\s*<\/div>/g;
        content = content.replace(fieldRegex, '');
    }

    fs.writeFileSync(file, content);
    console.log(`Processed ${file}`);
}
