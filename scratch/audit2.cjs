const fs = require('fs');
const path = require('path');

const DIRECTORY = path.join(__dirname, '../resources/js');

function walkDir(dir, callback) {
    if (!fs.existsSync(dir)) return;
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
    { name: 'Mock/Dummy/Fake', regex: /mock|dummy|fake|hardcoded/gi },
    { name: 'Console Log Action', regex: /console\.log\(['"`](?:action|click|submit|todo)/gi },
    { name: 'Coming Soon / Not implemented', regex: /Coming Soon|Not implemented/gi },
    { name: 'Fake Form Submit', regex: /onSubmit=\{\(e\)\s*=>\s*e\.preventDefault\(\)\}/g }
];

walkDir(DIRECTORY, filePath => {
    if (!filePath.match(/\.(jsx|tsx)$/)) return;
    
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n');
    
    lines.forEach((line, index) => {
        rules.forEach(rule => {
            if (rule.regex.test(line)) {
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

let markdown = `# Frontend Deep Audit Results\n\n`;

const grouped = results.reduce((acc, curr) => {
    if (!acc[curr.file]) acc[curr.file] = [];
    acc[curr.file].push(curr);
    return acc;
}, {});

for (const [file, items] of Object.entries(grouped)) {
    markdown += `### ${file}\n`;
    items.forEach(item => {
        markdown += `- Line ${item.line}: **[${item.type}]** \`${item.content.substring(0, 80)}\`\n`;
    });
    markdown += `\n`;
}

fs.writeFileSync(path.join(__dirname, 'audit_report.md'), markdown);
console.log(`Found ${results.length} issues.`);
