const fs = require('fs');
const path = require('path');

const auditFile = path.join(__dirname, '.control', 'ux_consistency_audit.md');
const auditContent = fs.readFileSync(auditFile, 'utf8');

const sectionStart = auditContent.indexOf('### 1. Button Placements (justify-between in forms)');
const sectionEnd = auditContent.indexOf('### 2. Spacing & Layout Widths');
const sectionText = auditContent.slice(sectionStart, sectionEnd);

const files = sectionText.split('\n')
    .filter(line => line.includes('- resources\\js\\') && line.includes('Index.tsx'))
    .map(line => {
        const match = line.match(/- (resources\\js\\[^\s]+) uses/);
        return match ? match[1] : null;
    })
    .filter(Boolean);

for (const relPath of files) {
    const fullPath = path.join(__dirname, relPath);
    if (!fs.existsSync(fullPath)) continue;
    
    const lines = fs.readFileSync(fullPath, 'utf8').split('\n');
    let hasMatch = false;
    
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('justify-between')) {
            // Check if it looks like a form action (e.g. near a Button or form)
            // We'll print a few lines around it to see
            if (!hasMatch) {
                console.log(`\n--- ${relPath} ---`);
                hasMatch = true;
            }
            console.log(`Line ${i+1}:`);
            for (let j = Math.max(0, i - 2); j <= Math.min(lines.length - 1, i + 5); j++) {
                console.log(`  ${lines[j]}`);
            }
        }
    }
}
