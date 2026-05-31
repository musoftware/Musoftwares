const fs = require('fs');
const path = require('path');
const dir = 'resources/js/Pages/SmsPaymentGateway';
const files = fs.readdirSync(dir).map(f => path.join(dir, f));
files.push('resources/js/Pages/TextPaymentGateway.tsx');
const ar = JSON.parse(fs.readFileSync('lang/ar.json', 'utf8'));
const regex = /__\(\s*(['"])(.*?)\1\s*\)/g;
const keys = new Set();
files.forEach(f => {
    if(fs.statSync(f).isFile()) {
        const content = fs.readFileSync(f, 'utf8');
        let match;
        while ((match = regex.exec(content)) !== null) {
            keys.add(match[2]);
        }
    }
});
const missing = Array.from(keys).filter(k => !k.includes('.') && !ar[k]);
console.log(JSON.stringify(missing, null, 2));
