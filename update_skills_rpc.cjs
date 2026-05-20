const fs = require('fs');
const path = require('path');

const rpcRule = `
## Generic WebSocket RPC Layer Architecture

All tools and plugins MUST be built using the exact following architecture. The UI communicates strictly over WebSockets, the Runtime routes it generically, and the Plugin handles it.

\`\`\`mermaid
sequenceDiagram
    participant UI as Frontend Tool UI (React)
    participant WS as Runtime WS Core (core/index.js)
    participant PL as PluginLoader
    participant Plugin as Installed Plugin Module (e.g. WhatsApp Sender)
    participant DB as Local SQLite (runtime.db)
    
    Note over UI,WS: 1. All UI actions sent over WebSocket
    UI->>WS: { type: "plugin_rpc", plugin: "wa-sender", action: "list", data: {} }
    
    Note over WS,PL: 2. Core generic layer routes the request
    WS->>PL: getModule("wa-sender")
    PL-->>WS: returns Plugin Module
    
    Note over WS,Plugin: 3. Core invokes standard exported function
    WS->>Plugin: handleRPC("list", {})
    
    Note over Plugin,DB: 4. Plugin interacts with local DB
    Plugin->>DB: SELECT * FROM wa_campaigns
    DB-->>Plugin: records
    
    Plugin-->>WS: returns { campaigns: [...] }
    
    Note over WS,UI: 5. Core sends back generic response
    WS-->>UI: { type: "plugin_rpc_res", payload: { campaigns: [...] } }
\`\`\`

### Standardized Plugin API (\`handleRPC\`)
Every plugin in the \`plugins/\` directory MUST export an async \`handleRPC(action, data)\` function from its entry file to handle synchronous data queries (like reading from SQLite) coming from the UI via the Generic WS Layer.

Example of what the plugin must look like internally:
\`\`\`javascript
// plugins/whatsapp-sender/index.js
module.exports.handleRPC = async (action, data) => {
    if (action === 'list_campaigns') {
        return { campaigns: [...] }; // fetch from sqlite
    }
    throw new Error('Unknown action: ' + action);
};
\`\`\`
`;

const skillsDir = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/.agent/skills';
const targetFolders = [
    'runtime-agent', 'plugin-sdk', 'websocket-system', 'architecture'
];

targetFolders.forEach(folder => {
    const filePath = path.join(skillsDir, folder, 'SKILL.md');
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        if (!content.includes('Generic WebSocket RPC Layer Architecture')) {
            fs.appendFileSync(filePath, '\n' + rpcRule + '\n');
            console.log('Appended RPC rule to ' + folder);
        } else {
            console.log('Already exists in ' + folder);
        }
    }
});
