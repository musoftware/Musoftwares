const fs = require('fs');

function fixH(filePath) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(/\/\* @phpstan-ignore-line \*\/ \$h/g, '$height');
        content = content.replace(/'height' => \$h,/g, "'height' => $height,");
        content = content.replace(/'h' => \$h,/g, "'h' => $height,");
        content = content.replace(/\(\$h \?\? 0\)/g, '$height');
        
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Fixed $h in " + filePath);
    }
}

fixH('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\UtilityToolsService.php');
fixH('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\UtilityToolsService.php');

// Also remove the faulty ignore rules from phpstan.neon for $h
const neonPath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan.neon';
if (fs.existsSync(neonPath)) {
    let content = fs.readFileSync(neonPath, 'utf8');
    const regex = /\s*-\s*message: '#\^Variable \.\*\?h might not be defined\\\.\$#'\s*paths:\s*- Modules\/WebTools\/app\/Services\/UtilityToolsService\.php\s*- Modules\/WebTools\/app\/Services\/Tools\/UtilityToolsService\.php/g;
    content = content.replace(regex, '');
    fs.writeFileSync(neonPath, content, 'utf8');
    console.log("Cleaned phpstan.neon $h");
}

