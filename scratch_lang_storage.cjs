const fs = require('fs');

const enKeys = {
    "driver": "Driver",
    "region": "Region",
    "custom_endpoint_optional": "Custom Endpoint (Optional)",
    "cancel": "Cancel",
    "saving": "Saving...",
    "save_configuration": "Save Configuration",
    "uploading": "Uploading..."
};

const arKeys = {
    "driver": "المشغل",
    "region": "المنطقة",
    "custom_endpoint_optional": "نقطة نهاية مخصصة (اختياري)",
    "cancel": "إلغاء",
    "saving": "جاري الحفظ...",
    "save_configuration": "حفظ الإعدادات",
    "uploading": "جاري الرفع..."
};

function updateLangFile(filePath, newKeys) {
    let content = fs.readFileSync(filePath, 'utf8');
    let injectStr = '\n    // ERP Storage common translations\n';
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
