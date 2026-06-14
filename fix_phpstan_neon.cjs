const fs = require('fs');

const path = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan.neon';
let content = fs.readFileSync(path, 'utf8');

content = content.replace(/#\^Variable \\\\\$week/g, '#^Variable .*?week');
content = content.replace(/#\^Variable \\\\\$h/g, '#^Variable .*?h');

fs.writeFileSync(path, content, 'utf8');
console.log("Updated phpstan.neon");
