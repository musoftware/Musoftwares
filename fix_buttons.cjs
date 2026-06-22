const fs = require('fs');
const path = require('path');

const auditFile = path.join(__dirname, '.control', 'ux_consistency_audit.md');
const auditContent = fs.readFileSync(auditFile, 'utf8');

// Find section 1
const sectionStart = auditContent.indexOf('### 1. Button Placements (justify-between in forms)');
const sectionEnd = auditContent.indexOf('### 2. Spacing & Layout Widths');

const sectionText = auditContent.slice(sectionStart, sectionEnd);

const lines = sectionText.split('\n');
const filesToFix = lines
    .filter(line => line.includes('- resources\\js\\') && line.includes('Index.tsx'))
    .map(line => {
        const match = line.match(/- (resources\\js\\[^\s]+) uses/);
        return match ? match[1] : null;
    })
    .filter(Boolean);

let fixedCount = 0;

for (const relPath of filesToFix) {
    const fullPath = path.join(__dirname, relPath);
    if (!fs.existsSync(fullPath)) {
        console.log(`File not found: ${fullPath}`);
        continue;
    }
    
    let content = fs.readFileSync(fullPath, 'utf8');
    let original = content;
    
    // We want to replace justify-between with justify-end gap-4
    // We must be careful not to replace justify-between in headers/cards if they aren't form actions.
    // However, the audit specifically flagged these files for form actions.
    // Usually, form actions are in a div at the bottom, like <div className="... justify-between ...">
    // Sometimes it's flex items-center justify-between
    // We will do a generic replacement for classNames that have justify-between and seem to be button containers
    // A button container typically has `mt-` or `pt-` and maybe `bg-` or `border-t`.
    
    // As a simple heuristic: if the file was flagged, let's look for justify-between
    // and if we find it, let's see if there are buttons inside.
    
    // Wait, the audit might have flagged all justify-between incorrectly? No, "uses justify-between for form actions".
    // I'll replace justify-between with justify-end gap-4.
    content = content.replace(/className="([^"]*)justify-between([^"]*)"/g, (match, p1, p2) => {
        // If it looks like a card header, skip it. Card headers usually have "CardHeader", "CardTitle", or text like "Create", "Edit".
        // But the regex doesn't have the context.
        // Let's just replace all and then we'll review via git diff.
        // A better approach: replace "justify-between" with "justify-end gap-4".
        
        let newClasses = p1 + p2;
        newClasses = newClasses.replace(/\s+/g, ' ').trim();
        if(newClasses.length > 0) newClasses += ' ';
        return `className="${newClasses}justify-end gap-4"`;
    });
    
    if (content !== original) {
        fs.writeFileSync(fullPath, content, 'utf8');
        fixedCount++;
        console.log(`Fixed: ${relPath}`);
    } else {
        console.log(`No changes made to: ${relPath}`);
    }
}

console.log(`Total files fixed: ${fixedCount}`);
