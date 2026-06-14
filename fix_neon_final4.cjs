const fs = require('fs');
const neonPath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan.neon';
let content = fs.readFileSync(neonPath, 'utf8');

// remove everything from ignoreErrors: to excludePaths:
content = content.replace(/ignoreErrors:[\s\S]*?excludePaths:/, `ignoreErrors:
        -
            message: '#^Class App\\\\Services\\\\WhatsAppNotificationService not found\\.$#'
            paths:
                - Modules/WebTools/Http/Controllers/Financial/PayGuestController.php
                - Modules/WebTools/Http/Controllers/Financial/WithdrawInstapayController.php

    excludePaths:`);

fs.writeFileSync(neonPath, content, 'utf8');
console.log("Cleaned phpstan.neon");
