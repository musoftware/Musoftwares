const fs = require('fs');
const path = require('path');

const oldRule = `**Communication Architecture Strict Rule**:
1. **WebSocket Only for Realtime/Operations**: ALL realtime and operational communication between the Frontend Tools and the Local Runtime MUST happen via WebSockets or unified generic HTTP task spawners.
2. **Generic Interface**: The Local Runtime MUST NOT have tool-specific hardcoded routes (e.g., no custom \`/whatsapp/...\` in the core runtime index). The runtime must expose a generic, unified interface.
3. **Internal Plugin Routing**: The frontend sends a generic payload (e.g., \`{ plugin: 'whatsapp-sender', action: 'get_campaigns' }\`), and the runtime routes this internally to the installed plugin. The plugin handles the logic and responds back through the generic bus.`;

const newRule = `**Communication Architecture Strict Rule**:
1. **WebSocket ONLY**: ALL communication between the UI and the Tool Plugin MUST happen EXCLUSIVELY via WebSockets. No HTTP REST endpoints.
2. **Generic Fixed Layer**: You MUST build a generic layer with fixed functions in the Local Runtime. This generic layer must NEVER change per tool. 
3. **Plugin Communication**: The Tool Plugin must communicate with the UI strictly via this generic WebSocket layer. The frontend sends a generic payload (e.g., \`{ plugin: 'whatsapp-sender', action: 'get_campaigns' }\`) over the WebSocket, and the runtime routes this internally to the installed plugin.`;

const skillsDir = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/.agent/skills';
const targetFolders = [
    'runtime-agent', 'plugin-sdk', 'websocket-system', 'frontend-system', 
    'architecture', 'conventions'
];

targetFolders.forEach(folder => {
    const filePath = path.join(skillsDir, folder, 'SKILL.md');
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        // Replace old rule with new rule
        if (content.includes(oldRule)) {
            content = content.replace(oldRule, newRule);
            fs.writeFileSync(filePath, content);
            console.log('Updated ' + folder);
        } else if (!content.includes(newRule)) {
            // Fallback: append if old rule wasn't exactly matched
            fs.appendFileSync(filePath, '\n' + newRule + '\n');
            console.log('Appended to ' + folder);
        }
    }
});
