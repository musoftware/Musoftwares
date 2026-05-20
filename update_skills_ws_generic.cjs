const fs = require('fs');
const path = require('path');

const wsRule = `
**Communication Architecture Strict Rule**:
1. **WebSocket Only for Realtime/Operations**: ALL realtime and operational communication between the Frontend Tools and the Local Runtime MUST happen via WebSockets or unified generic HTTP task spawners.
2. **Generic Interface**: The Local Runtime MUST NOT have tool-specific hardcoded routes (e.g., no custom \`/whatsapp/...\` in the core runtime index). The runtime must expose a generic, unified interface.
3. **Internal Plugin Routing**: The frontend sends a generic payload (e.g., \`{ plugin: 'whatsapp-sender', action: 'get_campaigns' }\`), and the runtime routes this internally to the installed plugin. The plugin handles the logic and responds back through the generic bus.
`;

const skillsDir = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/.agent/skills';
const targetFolders = [
    'runtime-agent', 'plugin-sdk', 'websocket-system', 'frontend-system', 
    'architecture', 'conventions'
];

targetFolders.forEach(folder => {
    const filePath = path.join(skillsDir, folder, 'SKILL.md');
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        if (!content.includes('**Communication Architecture Strict Rule**')) {
            fs.appendFileSync(filePath, '\n' + wsRule + '\n');
            console.log('Appended to ' + folder);
        } else {
            console.log('Already exists in ' + folder);
        }
    }
});
