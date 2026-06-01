const fs = require('fs');
const path = require('path');

const reportPath = path.join(__dirname, 'storage', 'logs', 'hardcoded_strings_report.json');

if (!fs.existsSync(reportPath)) {
    console.error('Report not found. Please run php artisan translations:check-hardcoded --report first.');
    process.exit(1);
}

const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
const filesObj = report.files || {};

const group = 'general';
const newTranslationsEn = {};
const newTranslationsAr = {};

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

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

let updatedFilesCount = 0;

for (const [filePath, strings] of Object.entries(filesObj)) {
    const fullPath = path.join(__dirname, filePath);
    if (!fs.existsSync(fullPath)) continue;

    let content = fs.readFileSync(fullPath, 'utf8');
    let originalContent = content;
    const stringList = Array.isArray(strings) ? strings : Object.values(strings);

    for (const rawStr of stringList) {
        const newKey = getSlug(rawStr);
        const escaped = escapeRegExp(rawStr);

        if (filePath.endsWith('.tsx') || filePath.endsWith('.jsx')) {
            // Replace >Text< with >{__('key')}<
            content = content.replace(new RegExp(`>\\s*${escaped}\\s*<`, 'g'), `>{__('${newKey}')}<`);
            // Replace prop="Text" with prop={__('key')}
            content = content.replace(new RegExp(`(placeholder|title|label|aria-label|description)=["']${escaped}["']`, 'g'), `$1={__('${newKey}')}`);
        } else if (filePath.endsWith('.blade.php')) {
            // Replace >Text< with >{{ __('key') }}<
            content = content.replace(new RegExp(`>\\s*${escaped}\\s*<`, 'g'), `>{{ __('${newKey}') }}<`);
            // Replace prop="Text" with prop="{{ __('key') }}"
            content = content.replace(new RegExp(`(placeholder|title|label|aria-label|description)=["']${escaped}["']`, 'g'), `$1="{{ __('${newKey}') }}"`);
        } else if (filePath.endsWith('.php')) {
            // Controller ->with('success', 'Text') -> ->with('success', __('key'))
            content = content.replace(new RegExp(`->with\\(\\s*(['"][^'"]+['"])\\s*,\\s*['"]${escaped}['"]\\s*\\)`, 'g'), `->with($1, __('${newKey}'))`);
            content = content.replace(new RegExp(`(abort|throw new [a-zA-Z0-9_\\\\]+Exception)\\(([^,]+),\\s*['"]${escaped}['"]\\)`, 'g'), `$1($2, __('${newKey}'))`);
            content = content.replace(new RegExp(`ActivityLogger::log\\(([^,]+),\\s*['"]${escaped}['"]\\)`, 'g'), `ActivityLogger::log($1, __('${newKey}'))`);
            content = content.replace(new RegExp(`->line\\(\\s*['"]${escaped}['"]\\s*\\)`, 'g'), `->line(__('${newKey}'))`);
        }
    }

    if (content !== originalContent) {
        fs.writeFileSync(fullPath, content);
        updatedFilesCount++;
    }
}

console.log(`Refactored ${updatedFilesCount} files.`);

// Update PHP Arrays via PHP script
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

console.log('Refactoring complete!');
