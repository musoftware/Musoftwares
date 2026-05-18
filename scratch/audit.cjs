const fs = require('fs');
const path = require('path');

const DIRECTORY = path.join(__dirname, '../resources/js');

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        const dirPath = path.join(dir, f);
        const isDirectory = fs.statSync(dirPath).isDirectory();
        isDirectory ? walkDir(dirPath, callback) : callback(path.join(dir, f));
    });
}

const results = [];

const rules = [
    { name: 'Empty Click', regex: /onClick=\{\s*\(\)\s*=>\s*\{\s*\}\s*\}/g },
    { name: 'Fake Link', regex: /href=["']#["']/g },
    { name: 'TODO', regex: /TODO:/gi },
    { name: 'Placeholder', regex: /placeholder/gi },
    { name: 'Console Log (Action)', regex: /console\.log\(['"`](?:action|click|submit|todo)/gi },
    { name: 'Hardcoded Number', regex: />\s*\$?\d{1,3}(?:,\d{3})*(?:\.\d+)?\s*</g }, // Naive, but might show fake stats
    { name: 'No Data/Empty State', regex: /No data available|Coming Soon|Not implemented/gi }
];

walkDir(DIRECTORY, filePath => {
    if (!filePath.match(/\.(jsx|tsx)$/)) return;
    
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n');
    
    lines.forEach((line, index) => {
        rules.forEach(rule => {
            if (rule.regex.test(line)) {
                // To reduce noise, filter out some things
                if (rule.name === 'Hardcoded Number' && line.includes('flex')) return; 
                
                results.push({
                    file: filePath.replace(DIRECTORY, ''),
                    line: index + 1,
                    type: rule.name,
                    content: line.trim()
                });
            }
        });
    });
});

fs.writeFileSync(path.join(__dirname, 'audit.json'), JSON.stringify(results, null, 2));
console.log(`Found ${results.length} issues.`);
