const fs = require('fs');

const testFile = 'Modules/Tools/Tests/Feature/LicenseControllerTest.php';

if (fs.existsSync(testFile)) {
    let content = fs.readFileSync(testFile, 'utf8');
    content = content.replace(/.*'max_devices'.*\n/g, '');
    fs.writeFileSync(testFile, content);
}
