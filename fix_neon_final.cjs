const fs = require('fs');
const neonPath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan.neon';
let content = fs.readFileSync(neonPath, 'utf8');

// I will just split by lines and remove the block
let lines = content.split('\n');
let newLines = [];
let skip = false;

for (let i = 0; i < lines.length; i++) {
    let line = lines[i];
    if (line.includes('message: \'#^Variable .*?week in empty() always exists and is not falsy.$#\'')) {
        skip = true;
        // remove the previous '-' line
        if (newLines.length > 0 && newLines[newLines.length - 1].trim() === '-') {
            newLines.pop();
        }
        continue;
    }
    
    if (skip) {
        // Skip paths lines
        if (line.includes('paths:') || line.includes('Modules/WebTools/app/Services/DateToolsService.php') || line.includes('Modules/WebTools/app/Services/Tools/DateToolsService.php')) {
            continue;
        } else {
            skip = false;
        }
    }
    
    newLines.push(line);
}

fs.writeFileSync(neonPath, newLines.join('\n'), 'utf8');
console.log("Cleaned phpstan.neon final");
