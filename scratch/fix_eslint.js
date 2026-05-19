const fs = require('fs');

const path = 'resources/js/global.d.ts';
let dts = fs.readFileSync(path, 'utf8');
if (!dts.includes('declare function route(')) {
    dts += `\ndeclare function route(name?: string, params?: any, absolute?: boolean): string;\n`;
    fs.writeFileSync(path, dts);
}
