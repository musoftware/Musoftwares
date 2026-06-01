const fs = require('fs');
const path = require('path');

const reportPath = path.join(__dirname, 'storage', 'logs', 'missing_translations_report.json');

if (!fs.existsSync(reportPath)) {
    console.error('Report not found. Please run php artisan translations:check --report first.');
    process.exit(1);
}

const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
// Depending on how CheckMissingTranslations was structured, missing might be in root or missing_by_locale
let missingEn = [];
if (report.missing_by_locale && report.missing_by_locale.en) {
    missingEn = report.missing_by_locale.en;
} else if (report.en) {
    missingEn = report.en;
}

// Filter to only raw strings (no dots, or strings with spaces)
const rawStrings = missingEn.filter(str => str.includes(' ') || !str.includes('.'));

if (rawStrings.length === 0) {
    console.log('No raw English strings found to refactor.');
    process.exit(0);
}

console.log(`Found ${rawStrings.length} raw strings to refactor.`);

const group = 'general';
const newTranslationsEn = {};
const newTranslationsAr = {};
const mappings = [];

// 1. Generate slugs
for (const rawStr of rawStrings) {
    let slug = rawStr.toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
    
    if (!slug) slug = 'str_' + Math.random().toString(36).substr(2, 5);

    let uniqueSlug = slug;
    let counter = 1;
    while (newTranslationsEn[uniqueSlug] && newTranslationsEn[uniqueSlug] !== rawStr) {
        uniqueSlug = `${slug}_${counter}`;
        counter++;
    }
    
    newTranslationsEn[uniqueSlug] = rawStr;
    newTranslationsAr[uniqueSlug] = rawStr;

    mappings.push({ rawStr, newKey: `${group}.${uniqueSlug}` });
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Prepare Regex array
const replacements = mappings.map(m => {
    return {
        regex: new RegExp(`(__|trans|@lang|trans_choice)(\\s*\\(\\s*)(['"])(${escapeRegExp(m.rawStr)})\\3`, 'g'),
        newKey: m.newKey
    };
});

// 2. Scan and Refactor Files
let updatedFilesCount = 0;
const extensions = ['.php', '.js', '.jsx', '.ts', '.tsx', '.vue'];
const directoriesToScan = ['app', 'resources', 'Modules'];

function scanDir(dir) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory()) {
            scanDir(fullPath);
        } else {
            const ext = path.extname(fullPath);
            if (extensions.includes(ext)) {
                let content = fs.readFileSync(fullPath, 'utf8');
                let originalContent = content;

                for (const r of replacements) {
                    content = content.replace(r.regex, `$1$2$3${r.newKey}$3`);
                }

                if (content !== originalContent) {
                    fs.writeFileSync(fullPath, content);
                    updatedFilesCount++;
                }
            }
        }
    }
}

for (const dir of directoriesToScan) {
    const fullDirPath = path.join(__dirname, dir);
    if (fs.existsSync(fullDirPath)) {
        scanDir(fullDirPath);
    }
}

console.log(`Refactored ${updatedFilesCount} files.`);

// 3. Update PHP Arrays via PHP script
const phpScriptContent = `<?php
$locales = ['en', 'ar'];
$group = '${group}';
$newKeysEn = json_decode('${JSON.stringify(newTranslationsEn).replace(/'/g, "\\'")}', true);
$newKeysAr = json_decode('${JSON.stringify(newTranslationsAr).replace(/'/g, "\\'")}', true);

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
    echo "Updated lang/$locale/$group.php\\n";
}
`;

const tempPhpScript = path.join(__dirname, 'temp_merge_lang.php');
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

console.log('Refactoring complete!');
