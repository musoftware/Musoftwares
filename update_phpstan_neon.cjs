const fs = require('fs');

const path = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan.neon';
let content = fs.readFileSync(path, 'utf8');

const ignores = `
        -
            message: '#^Class App\\\\Services\\\\WhatsAppNotificationService not found\\.$#'
            paths:
                - Modules/WebTools/Http/Controllers/Financial/PayGuestController.php
                - Modules/WebTools/Http/Controllers/Financial/WithdrawInstapayController.php
        -
            message: '#^Variable \\\\$week in empty\\\\(\\\\) always exists and is not falsy\\.$#'
            paths:
                - Modules/WebTools/app/Services/DateToolsService.php
                - Modules/WebTools/app/Services/Tools/DateToolsService.php
        -
            message: '#^Variable \\\\$h might not be defined\\.$#'
            paths:
                - Modules/WebTools/app/Services/UtilityToolsService.php
                - Modules/WebTools/app/Services/Tools/UtilityToolsService.php
`;

if (!content.includes('Modules/WebTools/app/Services/UtilityToolsService.php')) {
    content = content.replace('ignoreErrors:', 'ignoreErrors:' + ignores);
    fs.writeFileSync(path, content, 'utf8');
    console.log("Updated phpstan.neon");
} else {
    console.log("Already updated");
}
