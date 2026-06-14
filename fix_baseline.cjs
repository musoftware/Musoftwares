const fs = require('fs');

const baselinePath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\phpstan-baseline.neon';
if (fs.existsSync(baselinePath)) {
    let content = fs.readFileSync(baselinePath, 'utf8');
    // Remove blocks containing WhatsAppNotificationService not found
    // A block looks like:
    //		message: '#^Class App\\\\Services\\\\WhatsAppNotificationService not found\\.$#'
    //		count: 1
    //		path: app/Http/Controllers/Admin/InvoiceController.php
    
    // We can use a regex to match the message, count, and path lines
    const regex = /\n\t\tmessage: '#\^Class App\\\\\\\\Services\\\\\\\\WhatsAppNotificationService not found\\.\$#'\n\t\tcount: \d+\n\t\tpath: .*?\n/g;
    content = content.replace(regex, '\n');
    
    fs.writeFileSync(baselinePath, content, 'utf8');
    console.log("Cleaned baseline");
}
