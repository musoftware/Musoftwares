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
    if (content.includes('justify-between')) {
        const lines = content.split('\n');
        for (let i = 0; i < lines.length; i++) {
            if (lines[i].includes('justify-between')) {
                const snippet = lines.slice(i, i + 15).join('\n');
                if ((snippet.includes('PrimaryButton') || snippet.includes('Button ') || snippet.includes('type="submit"')) && 
                    (snippet.includes('Cancel') || snippet.includes('Save') || snippet.includes('Submit') || snippet.includes('Update') || snippet.includes('Create') || snippet.includes('SecondaryButton'))) {
                    matches.push({ file, line: i + 1, text: lines[i].trim() });
                }
            }
        }
    }
}

console.log('Found ' + matches.length + ' potential form action lines with justify-between');
matches.forEach(m => console.log(m.file + ':' + m.line + ' -> ' + m.text));
