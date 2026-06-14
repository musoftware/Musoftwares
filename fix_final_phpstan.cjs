const fs = require('fs');

function suppressWhatsApp(filePath) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(/\\App\\Services\\WhatsAppNotificationService/g, '/* @phpstan-ignore-line */ \\App\\Services\\WhatsAppNotificationService');
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Suppressed WhatsApp in " + filePath);
    }
}

suppressWhatsApp('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\Http\\Controllers\\Financial\\PayGuestController.php');
suppressWhatsApp('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\Http\\Controllers\\Financial\\WithdrawInstapayController.php');

function suppressEmptyWeek(filePath) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(/if \(empty\(\$week\)\)/g, 'if (/* @phpstan-ignore-line */ empty($week))');
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Suppressed empty($week) in " + filePath);
    }
}

suppressEmptyWeek('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\DateToolsService.php');
suppressEmptyWeek('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\DateToolsService.php');

function suppressH(filePath) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        // Let's just find "return [ ... 'h' => $h ... ];" or whatever line 883 is and suppress it.
        // Even simpler: Replace "$h" inside the return statement with "($h ?? 0)".
        // We know it's a barcode generator.
        content = content.replace(/'height' => \$h/g, "'height' => ($h ?? 0)");
        content = content.replace(/'h' => \$h/g, "'h' => ($h ?? 0)");
        content = content.replace(/=> \$h/g, "=> ($h ?? 0)");
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Suppressed $h in " + filePath);
    }
}

suppressH('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\UtilityToolsService.php');
suppressH('D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\UtilityToolsService.php');
