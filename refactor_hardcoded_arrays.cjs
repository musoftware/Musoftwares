const fs = require('fs');
const path = require('path');

const reportPath = path.join(__dirname, 'storage', 'logs', 'hardcoded_strings_report.json');

if (!fs.existsSync(reportPath)) {
    console.error('Report not found.');
    process.exit(1);
}

const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
const filesObj = report.files || {};

const group = 'general';
const newTranslationsEn = {};
const newTranslationsAr = {};

function getSlug(str) {
    let slug = str.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (!slug) slug = 'str_' + Math.random().toString(36).substr(2, 5);
    let uniqueSlug = slug;
    let counter = 1;
    while (newTranslationsEn[uniqueSlug] && newTranslationsEn[uniqueSlug] !== str) {
        uniqueSlug = `${slug}_${counter}`;
        counter++;
    }
    newTranslationsEn[uniqueSlug] = str;
    newTranslationsAr[uniqueSlug] = str;
    return `${group}.${uniqueSlug}`;
}

// We already refactored the files, but let's regenerate the dictionary and write the php arrays.
for (const [filePath, strings] of Object.entries(filesObj)) {
    const stringList = Array.isArray(strings) ? strings : Object.values(strings);
    for (const rawStr of stringList) {
        getSlug(rawStr);
    }
}

// Write the JSON dicts to temp files
const tempEnJson = path.join(__dirname, 'temp_en.json');
const tempArJson = path.join(__dirname, 'temp_ar.json');
fs.writeFileSync(tempEnJson, JSON.stringify(newTranslationsEn));
fs.writeFileSync(tempArJson, JSON.stringify(newTranslationsAr));

const phpScriptContent = `<?php
$locales = ['en', 'ar'];
$group = '${group}';
$newKeysEn = json_decode(file_get_contents(__DIR__ . '/temp_en.json'), true);
$newKeysAr = json_decode(file_get_contents(__DIR__ . '/temp_ar.json'), true);

foreach ($locales as $locale) {
    $dir = __DIR__ . "/lang/$locale";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $file = "$dir/$group.php";
    $existing = [];
    if (file_exists($file)) {
        $existing = require $file;
    }
    
    $newKeys = $locale === 'en' ? $newKeysEn : $newKeysAr;
    $merged = array_merge($existing, $newKeys);
    
    $content = "<?php\\n\\nreturn " . var_export($merged, true) . ";\\n";
    $content = str_replace(['array (', ')'], ['[', ']'], $content);
    file_put_contents($file, $content);
}
echo "Updated language arrays.\\n";
`;

const tempPhpScript = path.join(__dirname, 'temp_merge_hardcoded.php');
fs.writeFileSync(tempPhpScript, phpScriptContent);

console.log('Running PHP script to update language arrays...');
const { execSync } = require('child_process');
try {
    const output = execSync(`php ${tempPhpScript}`);
    console.log(output.toString());
} catch (e) {
    console.error('Failed to run PHP merge script:', e);
}

fs.unlinkSync(tempPhpScript);
fs.unlinkSync(tempEnJson);
fs.unlinkSync(tempArJson);

console.log('Language array generation complete!');
