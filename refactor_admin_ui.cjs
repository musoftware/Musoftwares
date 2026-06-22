const fs = require('fs');
const path = require('path');

const targetDirs = [
    'resources/js/Pages/Admin',
    'resources/js/Pages/ERP',
    'resources/js/Pages/Booking',
    'resources/js/Pages/AffiliatePos',
    'resources/js/Pages/Client'
];

function getFiles(dir, filesList = []) {
    if (!fs.existsSync(dir)) return filesList;
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            getFiles(fullPath, filesList);
        } else if (
            (fullPath.includes('Create.tsx') || fullPath.includes('Edit.tsx') || 
             fullPath.includes('Create.jsx') || fullPath.includes('Edit.jsx'))
        ) {
            filesList.push(fullPath);
        }
    }
    return filesList;
}

let allFiles = [];
targetDirs.forEach(dir => {
    allFiles = allFiles.concat(getFiles(dir));
});

console.log(`Found ${allFiles.length} files to process.`);

allFiles.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let originalContent = content;

    // 1. Max Width replacement
    content = content.replace(/max-w-(?:md|lg|xl|2xl|3xl|4xl|5xl|6xl|\[\d+px\])/g, 'w-full max-w-7xl');
    
    // 2. Color replacement (Primary action colors to Slate)
    content = content.replace(/blue-600/g, 'slate-900');
    content = content.replace(/blue-500/g, 'slate-800');
    content = content.replace(/blue-700/g, 'slate-900');
    content = content.replace(/bg-blue-50/g, 'bg-slate-100');
    content = content.replace(/indigo-600/g, 'slate-900');
    content = content.replace(/indigo-500/g, 'slate-800');
    content = content.replace(/indigo-700/g, 'slate-900');

    // 3. Components Replacement
    let needsButton = false;
    let needsInput = false;

    // Check if we have raw <button
    if (/<button\b/.test(content)) {
        content = content.replace(/<button\b/g, '<Button');
        content = content.replace(/<\/button>/g, '</Button>');
        needsButton = true;
    }

    // Check if we have raw <input (except type="checkbox", type="radio", type="hidden", type="file")
    // This is tricky. Let's just do it for simple <input ... and avoid messing up checkbox.
    // Actually, Shadcn <Input> handles text, email, password, number.
    // Let's manually replace <input that don't have type="checkbox" or type="radio"
    const inputRegex = /<input([^>]*)>/g;
    content = content.replace(inputRegex, (match, p1) => {
        if (p1.includes('type="checkbox"') || p1.includes("type='checkbox'") || 
            p1.includes('type="radio"') || p1.includes("type='radio'") ||
            p1.includes('type="hidden"') || p1.includes("type='hidden'")) {
            return match; // leave as is
        }
        needsInput = true;
        return `<Input${p1}>`;
    });

    // 4. Inject Imports
    if (needsButton || content.includes('<Button')) {
        if (!content.includes("@/Components/ui/button") && !content.includes("@/Components/ui/Button")) {
            content = content.replace(/(import .* from ['"].*['"];\n)/, `$1import { Button } from '@/Components/ui/button';\n`);
        }
    }

    if (needsInput || content.includes('<Input')) {
        if (!content.includes("@/Components/ui/input") && !content.includes("@/Components/ui/Input")) {
            content = content.replace(/(import .* from ['"].*['"];\n)/, `$1import { Input } from '@/Components/ui/input';\n`);
        }
    }

    if (content !== originalContent) {
        fs.writeFileSync(file, content, 'utf8');
        console.log(`Updated ${file}`);
    }
});
