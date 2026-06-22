const fs = require('fs');

const audit = fs.readFileSync('.control/ux_consistency_audit.md', 'utf8');
let inSection = false;
let files = [];
for (const line of audit.split('\n')) {
    if (line.startsWith('### 1. Button Placements')) inSection = true;
    else if (inSection && line.startsWith('### ')) break;
    else if (inSection && line.startsWith('- ')) {
        files.push(line.substring(2).split(' ')[0].trim());
    }
}

files.forEach(file => {
    if (!fs.existsSync(file)) return;
    const lines = fs.readFileSync(file, 'utf8').split('\n');
    let replaced = false;
    
    // We want to find the form action button wrapper and replace justify-between with justify-end gap-4.
    // Usually it looks like: className="flex items-center justify-between..."
    // near the bottom of a <form> or <DialogFooter> or <CardFooter>
    
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('justify-between')) {
            // Very naive heuristic to find form action wrappers:
            // Check lines around it for <Button or type="submit" or type="button"
            const block = lines.slice(Math.max(0, i - 2), Math.min(lines.length, i + 10)).join('\n');
            const hasButton = block.includes('<Button') || block.includes('PrimaryButton') || block.includes('SecondaryButton') || block.includes('DangerButton');
            
            // Should NOT be a page header or standard card header that's legitimate
            const isHeader = block.includes('<h1') || block.includes('<h2') || lines[i].includes('border-b');
            
            if (hasButton && !isHeader) {
                console.log(`[${file}:${i+1}]`);
                console.log(lines[i].trim());
            }
        }
    }
});
