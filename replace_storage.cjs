const fs = require('fs');
const path = require('path');

function replaceInDir(dir) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            replaceInDir(fullPath);
        } else if (fullPath.endsWith('.ts') || fullPath.endsWith('.tsx') || fullPath.endsWith('.js') || fullPath.endsWith('.jsx')) {
            let content = fs.readFileSync(fullPath, 'utf8');
            let updated = content.replace(/window\.localStorage\.getItem\('musoftware_runtime_host'\)/g, '(window as any).MUSOFTWARE_RUNTIME_HOST');
            updated = updated.replace(/localStorage\.getItem\('musoftware_runtime_host'\)/g, '(window as any).MUSOFTWARE_RUNTIME_HOST');
            if (content !== updated) {
                fs.writeFileSync(fullPath, updated, 'utf8');
                console.log('Updated: ' + fullPath);
            }
        }
    }
}

replaceInDir('d:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/Musoftwares/resources/js');
