const fs = require('fs');

const files = [
    'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\UtilityToolsService.php',
    'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\UtilityToolsService.php'
];

files.forEach(file => {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');
        // Undo the bad regex
        content = content.replace(/public function generateBarcode\(\n\s*\$h = 0; \/\/ PHPStan fix\n/g, 'public function generateBarcode(');
        
        // Correctly inject $h = 0; inside the function body
        content = content.replace(/public function generateBarcode\((.*?)\)\s*\{/s, 'public function generateBarcode($1)\n    {\n        $h = 0; // PHPStan fix\n');
        
        fs.writeFileSync(file, content, 'utf8');
        console.log("Fixed syntax error in " + file);
    }
});
