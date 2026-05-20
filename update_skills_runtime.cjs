const fs = require('fs');
const path = require('path');

const injectionText = `

# Runtime-First Architecture & Execution

## Critical Engineering Philosophy
The Frontend UI is ONLY an **operational control surface**. The REAL system exists exclusively inside the **Runtime Agent + Workers**.
- Buttons MUST trigger real runtime commands.
- Uploads MUST stream directly to the runtime.
- Tasks execute locally via the runtime.
- Logs, statuses, and progress MUST stream from the runtime via WebSockets.
- The Runtime is the absolute source of truth.

## No More Fake UI
NEVER create fake dashboards, static metrics, fake progress bars, mock runtime states, disconnected tabs, dead buttons, or simulated success messages. 
Tables, cards, and widgets MUST display real runtime data, not hardcoded placeholders.

## Architecture Responsibility
**Frontend (UI)**: ONLY renders state, triggers actions via Runtime SDK, subscribes to events, and displays progress. It NEVER owns operational logic or truth.
**Runtime Agent**: Handles execution, queues, workers, automation, browser sessions, local storage, CSV parsing, retries, logs, and concurrency. IT OWNS the operational entities (Campaigns, Queues, Sessions).
**Laravel Backend**: Handles ONLY authentication, subscriptions, billing, marketplace, licensing, user accounts, and high-level metadata. It NEVER handles local operational execution or heavy processing (like CSV uploads).

## Realtime System Requirement
Operational software MUST feel alive. You must always implement:
- Live logs
- Live queue states
- Live runtime heartbeat
- Realtime progress updates
- WebSocket event subscriptions

## Final AI Behavior
When building tools, you must constantly ask: 
- "Where does this operation actually execute?"
- "Is this runtime-owned or frontend-owned?"
- "How does realtime synchronization work?"
Never stop at a beautiful UI. Build fully operational, runtime-connected infrastructure.
`;

const skillsDir = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/.agent/skills';
const targetFolders = [
    'runtime-agent', 'plugin-sdk', 'websocket-system', 'frontend-system', 
    'operational-ux', 'automation-engine', 'architecture', 'conventions'
];

targetFolders.forEach(folder => {
    const filePath = path.join(skillsDir, folder, 'SKILL.md');
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        if (!content.includes('Runtime-First Architecture & Execution')) {
            fs.appendFileSync(filePath, injectionText);
            console.log('Injected into ' + folder);
        } else {
            console.log('Already exists in ' + folder);
        }
    } else {
        console.log('File not found for ' + folder);
    }
});
