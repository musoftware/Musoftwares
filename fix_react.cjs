const fs = require('fs');

const files = [
    'resources/js/Pages/Admin/Tools/Create.tsx',
    'resources/js/Pages/Admin/Tools/Edit.tsx'
];

for (const file of files) {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');
        content = content.replace(/.*value=\{data\.max_devices\}.*\n/g, '');
        content = content.replace(/.*setData\('max_devices'.*\n/g, '');
        content = content.replace(/.*<Label htmlFor="max_devices">.*?\n/g, '');
        content = content.replace(/.*id="max_devices".*\n/g, '');
        content = content.replace(/.*<InputError message=\{errors\.max_devices\}.*\n/g, '');
        
        // Let's also remove any empty div blocks left behind
        content = content.replace(/<div>\s*<\/div>/g, '');
        
        fs.writeFileSync(file, content);
    }
}
