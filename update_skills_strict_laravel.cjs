const fs = require('fs');
const path = require('path');

const oldText = "**Laravel Backend**: Handles ONLY authentication, subscriptions, billing, marketplace, licensing, user accounts, and high-level metadata. It NEVER handles local operational execution or heavy processing (like CSV uploads).";

const newText = `**Laravel Backend Strict Rule**: 
For any Tool/Plugin, its ONLY connection to the Laravel backend is checking if the user is subscribed to the service or not.
EVERYTHING ELSE related to the tools (data, configurations, campaigns, logs, operational entities, processing) MUST be handled by the Local Runtime Agent and stored locally in the client's local SQLite database. The Laravel backend must NEVER be used to store or process tool-specific data.`;

const skillsDir = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/.agent/skills';
const targetFolders = [
    'runtime-agent', 'plugin-sdk', 'websocket-system', 'frontend-system', 
    'operational-ux', 'automation-engine', 'architecture', 'conventions', 'backend-system'
];

let updatedCount = 0;

targetFolders.forEach(folder => {
    const filePath = path.join(skillsDir, folder, 'SKILL.md');
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        if (content.includes(oldText)) {
            content = content.replace(newText, ''); // Clean up if it was previously there
            content = content.replace(oldText, newText);
            fs.writeFileSync(filePath, content);
            console.log('Updated ' + folder);
            updatedCount++;
        } else if (!content.includes(newText)) {
            // Append if neither old nor new text is there
            fs.appendFileSync(filePath, '\n' + newText + '\n');
            console.log('Appended to ' + folder);
            updatedCount++;
        } else {
            console.log('Already up-to-date in ' + folder);
        }
    } else {
        console.log('File not found for ' + folder);
    }
});

console.log(`Successfully updated ${updatedCount} files.`);
