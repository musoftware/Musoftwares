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

let toReplace = [];

files.forEach(file => {
    if (!fs.existsSync(file)) return;
    const lines = fs.readFileSync(file, 'utf8').split('\n');
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('justify-between')) {
            // Check if this is a form action area. Usually contains 'Cancel', 'Save', 'Submit', 'PrimaryButton'
            // We check the next 15 lines.
            const block = lines.slice(i, i + 15).join('\n');
            const hasAction = (block.includes('PrimaryButton') || block.includes('SecondaryButton') || block.includes('Button ') || block.includes('type="submit"')) && 
                              (block.includes('Cancel') || block.includes('Save') || block.includes('Submit') || block.includes('Update') || block.includes('Create') || block.includes('Send') || block.includes('Upload'));
            
            // Exclude page headers which often have <h1> or similar titles
            const isHeader = block.includes('<h1') || block.includes('<h2') || lines[i].includes('border-b');
            
            if (hasAction && !isHeader) {
                console.log(file + ':' + (i+1));
                console.log('  ' + lines[i].trim());
            }
        }
    }
});
