const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        file = path.join(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) { 
            results = results.concat(walk(file));
        } else { 
            if (file.endsWith('.tsx') || file.endsWith('.jsx')) results.push(file);
        }
    });
    return results;
}

const files = walk('resources/js');
let matches = [];

for (const file of files) {
    const content = fs.readFileSync(file, 'utf8');
    const lines = content.split('\n');
    
    for (let i = 0; i < lines.length; i++) {
        // Look for typical form action wrappers:
        // We look for divs that have a button and seem to be at the bottom of a form.
        // Actually, it's easier to just look for justify-between lines containing Cancel / Submit / Save
        if (lines[i].includes('justify-between')) {
            const block = lines.slice(i, i + 8).join('\n');
            const lowerBlock = block.toLowerCase();
            const hasActionBtn = lowerBlock.includes('submit') || lowerBlock.includes('save') || lowerBlock.includes('cancel');
            const hasButton = lowerBlock.includes('button');
            const isHeader = lowerBlock.includes('<h1') || lowerBlock.includes('<h2') || lowerBlock.includes('<h3') || lowerBlock.includes('cardtitle') || lowerBlock.includes('cardheader');
            
            if (hasActionBtn && hasButton && !isHeader) {
                matches.push(file + ':' + (i+1) + ' -> ' + lines[i].trim());
            }
        }
    }
}

console.log('Matches: ' + matches.length);
matches.forEach(m => console.log(m));
