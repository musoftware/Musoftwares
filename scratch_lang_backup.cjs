const fs = require('fs');

const enKeys = {
    "unlock_backup_addon_price": "Unlock Backup Addon for :price/Yr",
    "restore_from_file": "Restore from File",
    "yes_wipe_and_restore": "Yes, Wipe and Restore"
};

const arKeys = {
    "unlock_backup_addon_price": "افتح إضافة النسخ الاحتياطي مقابل :price/سنوياً",
    "restore_from_file": "استعادة من ملف",
    "yes_wipe_and_restore": "نعم، امسح واستعد البيانات"
};

function updateLangFile(filePath, newKeys) {
    let content = fs.readFileSync(filePath, 'utf8');
    let injectStr = '\n    // Backup Addon translations\n';
    let added = false;
    for (let key in newKeys) {
        if (!content.includes('\'' + key + '\' =>')) {
            let val = newKeys[key].replace(/'/g, "\\'");
            injectStr += '    \'' + key + '\' => \'' + val + '\',\n';
            added = true;
        }
    }
    
    if (added) {
        content = content.replace(/];\s*$/, injectStr + '];\n');
        fs.writeFileSync(filePath, content);
        console.log('Updated', filePath);
    } else {
        console.log('No new keys for', filePath);
    }
}

updateLangFile('lang/en/general.php', enKeys);
updateLangFile('lang/ar/general.php', arKeys);
