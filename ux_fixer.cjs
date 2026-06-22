const fs = require('fs');
const path = require('path');

const auditFile = fs.readFileSync('.control/ux_consistency_audit.md', 'utf-8');

const rules = {
    buttons: [],
    spacing: [],
    colors: []
};

let currentSection = null;

auditFile.split('\n').forEach(line => {
    if (line.includes('1. Button Placements')) currentSection = 'buttons';
    else if (line.includes('2. Spacing & Layout Widths')) currentSection = 'spacing';
    else if (line.includes('3. Colors and Typography')) currentSection = 'colors';
    else if (line.includes('4. Component Structural Layouts')) currentSection = null;

    if (currentSection && line.startsWith('- resources')) {
        const filePath = line.match(/- (resources[^\s]+)/)[1];
        if (filePath) {
            rules[currentSection].push(filePath.replace(/\\/g, '/'));
        }
    }
});

// Remove duplicates
rules.buttons = [...new Set(rules.buttons)];
rules.spacing = [...new Set(rules.spacing)];
rules.colors = [...new Set(rules.colors)];

console.log(`Found ${rules.buttons.length} files for buttons`);
console.log(`Found ${rules.spacing.length} files for spacing`);
console.log(`Found ${rules.colors.length} files for colors`);

function processButtons() {
    rules.buttons.forEach(file => {
        if (!fs.existsSync(file)) return;
        let content = fs.readFileSync(file, 'utf-8');
        let lines = content.split('\n');
        let modified = false;

        for (let i = 0; i < lines.length; i++) {
            if (lines[i].includes('justify-between')) {
                // Check if it's a form action container.
                // It usually doesn't have h1, h2, h3.
                // It usually has Button, Link, or submit.
                let nextLines = lines.slice(i, i + 15).join('\n');
                if (nextLines.includes('<Button') && !nextLines.includes('<h1') && !nextLines.includes('<h2') && !nextLines.includes('<h3') && !nextLines.includes('CardTitle') && !nextLines.includes('CardHeader') && !nextLines.includes('pb-2') && !nextLines.includes('border-b')) {
                    lines[i] = lines[i].replace('justify-between', 'justify-end gap-4');
                    modified = true;
                }
            }
        }

        if (modified) {
            fs.writeFileSync(file, lines.join('\n'));
            console.log(`Fixed buttons in ${file}`);
        }
    });
}

function processSpacing() {
    rules.spacing.forEach(file => {
        if (!fs.existsSync(file)) return;
        let content = fs.readFileSync(file, 'utf-8');
        let lines = content.split('\n');
        let modified = false;

        for (let i = 0; i < lines.length; i++) {
            if (lines[i].includes('mx-auto') && lines[i].match(/max-w-(xs|sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl)/)) {
                lines[i] = lines[i].replace(/max-w-(xs|sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl)/g, 'max-w-7xl');
                modified = true;
            }
        }

        if (modified) {
            fs.writeFileSync(file, lines.join('\n'));
            console.log(`Fixed spacing in ${file}`);
        }
    });
}

function processColors() {
    const colorReplacements = {
        'text-indigo-500': 'text-slate-900',
        'text-indigo-600': 'text-slate-900',
        'text-indigo-700': 'text-slate-900',
        'bg-indigo-500': 'bg-slate-900',
        'bg-indigo-600': 'bg-slate-900',
        'bg-indigo-700': 'bg-slate-900',
        'text-blue-500': 'text-slate-900',
        'text-blue-600': 'text-slate-900',
        'text-blue-700': 'text-slate-900',
        'bg-blue-500': 'bg-slate-900',
        'bg-blue-600': 'bg-slate-900',
        'bg-blue-700': 'bg-slate-900',
        'text-purple-500': 'text-slate-900',
        'text-purple-600': 'text-slate-900',
        'text-purple-700': 'text-slate-900',
        'bg-purple-500': 'bg-slate-900',
        'bg-purple-600': 'bg-slate-900',
        'bg-purple-700': 'bg-slate-900',
        'text-rose-400': 'text-slate-900',
        'text-rose-500': 'text-slate-900',
        'text-rose-600': 'text-slate-900',
        'text-rose-700': 'text-slate-900',
        'bg-rose-500': 'bg-slate-900',
        'bg-rose-600': 'bg-slate-900',
        'bg-rose-700': 'bg-slate-900',
        'text-emerald-400': 'text-slate-900',
        'text-emerald-500': 'text-slate-900',
        'text-emerald-600': 'text-slate-900',
        'text-emerald-700': 'text-slate-900',
        'bg-emerald-500': 'bg-slate-900',
        'bg-emerald-600': 'bg-slate-900',
        'bg-emerald-700': 'bg-slate-900',
        'bg-cyan-500': 'bg-slate-900',
        'text-cyan-500': 'text-slate-900'
    };

    rules.colors.forEach(file => {
        if (!fs.existsSync(file)) return;
        let content = fs.readFileSync(file, 'utf-8');
        let modified = false;

        Object.keys(colorReplacements).forEach(color => {
            if (content.includes(color)) {
                content = content.split(color).join(colorReplacements[color]);
                modified = true;
            }
        });

        if (modified) {
            fs.writeFileSync(file, content);
            console.log(`Fixed colors in ${file}`);
        }
    });
}

processButtons();
processSpacing();
processColors();

console.log('Done!');
