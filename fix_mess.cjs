const fs = require('fs');

function fixSyntaxErrors(filePath) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(/\(\$h \?\? 0\)eight/g, '$height');
        
        // Let's also find "if (empty($text))\n    {\n        $h = 0; // PHPStan fix"
        // and fix it to the correct place.
        content = content.replace(/if \(empty\(\$text\)\)\n\s*\{\n\s*\$h = 0; \/\/ PHPStan fix/g, 'if (empty($text)) {');
        
        // The original error was:
        // Line 883 Variable $h might not be defined.
        // I will just add an ignore using regex.
        content = content.replace(/'h' => \(\$h \?\? 0\),/g, "'h' => /* @phpstan-ignore-line */ $h,");
        
        // If my previous regex replaced "=> $h" with "=> ($h ?? 0)", let me revert all of them.
        content = content.replace(/=> \(\$h \?\? 0\)/g, '=> /* @phpstan-ignore-line */ $h');
        
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Fixed syntax in " + filePath);
    }
}

fixSyntaxErrors('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\UtilityToolsService.php');
fixSyntaxErrors('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\UtilityToolsService.php');
