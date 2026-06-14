const fs = require('fs');

function fixFinal(filePath) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(/if \(!empty\(\$week\)\)/g, 'if ($week)');
        content = content.replace(/\$h \/= 6;/g, 'if (!isset($h)) $h = 0; $h /= 6;');
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Fixed " + filePath);
    }
}

fixFinal('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\DateToolsService.php');
fixFinal('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\DateToolsService.php');
fixFinal('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\UtilityToolsService.php');
fixFinal('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\UtilityToolsService.php');

