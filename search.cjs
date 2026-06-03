const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        file = path.resolve(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(file));
        } else {
            if (file.endsWith('.tsx') || file.endsWith('.jsx')) {
                results.push(file);
            }
        }
    });
    return results;
}

const files = walk('d:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/Musoftwares/resources/js');
files.forEach(f => {
    const c = fs.readFileSync(f, 'utf-8');
    if (c.includes('Download') && !c.match(/import\s*\{[^}]*Download[^}]*\}\s*from\s*['"]lucide-react['"]/)) {
        console.log('Missing import in:', f);
    }
});
