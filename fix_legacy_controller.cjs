const fs = require('fs');

const file = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\Http\\Controllers\\LegacyToolsController.php';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(/namespace App\\Http\\Controllers;/g, 'namespace Modules\\WebTools\\Http\\Controllers;');
content = content.replace(/class ToolsController extends Controller/g, 'class LegacyToolsController extends Controller');

fs.writeFileSync(file, content, 'utf8');
console.log("Renamed class and namespace in LegacyToolsController.php");
