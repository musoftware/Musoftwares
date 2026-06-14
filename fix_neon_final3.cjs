const fs = require('fs');
const neonPath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan.neon';
let content = fs.readFileSync(neonPath, 'utf8');

const str = `        -
            message: '#^Variable .*?week in empty() always exists and is not falsy.$#'
            paths:
                - Modules/WebTools/app/Services/DateToolsService.php
                - Modules/WebTools/app/Services/Tools/DateToolsService.php`;

content = content.replace(str, '');
fs.writeFileSync(neonPath, content, 'utf8');
console.log("Cleaned phpstan.neon");
