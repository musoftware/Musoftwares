const fs = require('fs');
const path = require('path');

const SERVICES_DIR = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services';

function processDirectory(directory) {
    const files = fs.readdirSync(directory);
    for (const file of files) {
        const fullPath = path.join(directory, file);
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory()) {
            processDirectory(fullPath);
        } else if (file.endsWith('.php')) {
            let content = fs.readFileSync(fullPath, 'utf8');
            let modified = false;
            
            if (content.includes('catch (Exception ')) {
                content = content.replace(/catch \(Exception /g, 'catch (\\Exception ');
                modified = true;
            }
            if (content.includes('throw new Exception')) {
                content = content.replace(/throw new Exception/g, 'throw new \\Exception');
                modified = true;
            }
            
            if (modified) {
                fs.writeFileSync(fullPath, content, 'utf8');
                console.log(`Updated: ${fullPath}`);
            }
        }
    }
}

processDirectory(SERVICES_DIR);
