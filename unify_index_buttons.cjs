const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(fullPath));
        } else if (file === 'Index.tsx') {
            results.push(fullPath);
        }
    });
    return results;
}

const pagesDir = path.join(__dirname, 'resources', 'js', 'Pages');
const indexFiles = walk(pagesDir);

let fixedFiles = 0;

for (const file of indexFiles) {
    const originalContent = fs.readFileSync(file, 'utf8');
    const lines = originalContent.split('\n');
    let changed = false;

    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('justify-between')) {
            // Check context
            const contextStart = Math.max(0, i - 3);
            const contextEnd = Math.min(lines.length - 1, i + 5);
            const context = lines.slice(contextStart, contextEnd).join('\n');

            // Skip typical CardHeaders
            if (lines[i].includes('CardHeader') || context.includes('<CardTitle')) continue;
            
            // Skip Pagination
            if (context.includes('Showing ') && context.includes('entries')) continue;
            if (context.includes('<Pagination')) continue;
            
            // Skip typical stat/progress bars
            if (context.includes('text-muted-foreground') && context.includes('%')) continue;
            if (context.includes('formatCurrency(') && !context.includes('<form')) continue;
            if (context.includes('font-medium') && !context.includes('<Button') && !context.includes('<form')) continue;

            // If it's a top action bar or form actions
            if (
                lines[i].includes('mb-6') || lines[i].includes('mb-4') || 
                lines[i].includes('mt-4') || lines[i].includes('mt-6') ||
                lines[i].includes('<form') || context.includes('<form') || 
                context.includes('<Button') || context.includes('handleSearch') ||
                context.includes('handleFilter')
            ) {
                // Do the replacement on this line
                lines[i] = lines[i].replace('justify-between', 'justify-end gap-4');
                changed = true;
            }
        }
    }

    if (changed) {
        fs.writeFileSync(file, lines.join('\n'), 'utf8');
        fixedFiles++;
        console.log(`Fixed: ${file}`);
    }
}

console.log(`Total files fixed: ${fixedFiles}`);
