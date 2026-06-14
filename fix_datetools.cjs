const fs = require('fs');

function fixDateTools(filePath) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(/if \(empty\(\$week\)\)/g, 'if (!$week)');
        content = content.replace(/if \(\/\* @phpstan-ignore-line \*\/ empty\(\$week\)\)/g, 'if (!$week)');
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Fixed empty($week) in " + filePath);
    }
}

fixDateTools('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\DateToolsService.php');
fixDateTools('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\DateToolsService.php');

// Also remove the faulty ignore rules from phpstan.neon
const neonPath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan.neon';
if (fs.existsSync(neonPath)) {
    let content = fs.readFileSync(neonPath, 'utf8');
    const regex = /\s*-\s*message: '#\^Variable \.\*\?week in empty\\\(\\\) always exists and is not falsy\\\.\$#'\s*paths:\s*- Modules\/WebTools\/app\/Services\/DateToolsService\.php\s*- Modules\/WebTools\/app\/Services\/Tools\/DateToolsService\.php/g;
    content = content.replace(regex, '');
    fs.writeFileSync(neonPath, content, 'utf8');
    console.log("Cleaned phpstan.neon");
}

