/**
 * WhatsApp Funnel Engine — Plugin Worker (Node.js)
 * ================================================
 * Executes a visual funnel graph (nodes & edges from ReactFlow)
 * for a list of contacts. Maintains state locally.
 */

'use strict';

const path = require('path');
const fs = require('fs');
const SessionManager = require('../_shared/session-manager');
const Humanizer = require('../_shared/humanizer');

const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, msg) => emit('log', { level, message: msg });
const progress = (pct, msg)   => emit('progress', { percent: pct, message: msg });

async function main() {
    log('info', '⚙️ WhatsApp Funnel Engine starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const { session_name, nodes = [], edges = [], contacts = [], aggressiveness = 'moderate' } = params;

    if (!session_name) { emit('error', { message: 'session_name required' }); process.exit(1); }
    if (nodes.length === 0 || contacts.length === 0) {
        emit('error', { message: 'Funnel nodes and contacts required.' });
        process.exit(1);
    }

    const humanizer = new Humanizer(aggressiveness);
    const sessionDir = path.join(__dirname, '..', '_sessions', session_name);
    const sessionManager = new SessionManager(sessionDir);

    let browser;
    try {
        browser = await sessionManager.launchBrowser();
    } catch (err) { emit('error', { message: 'Browser launch failed: ' + err.message }); process.exit(1); }

    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    progress(20, 'Loading WhatsApp Web...');
    await page.goto('https://web.whatsapp.com', { waitUntil: 'networkidle2', timeout: 60000 });

    const authStatus = await sessionManager.waitForAuth(page);
    if (authStatus === 'qr') {
        const qrCode = await sessionManager.getQrCode(page);
        emit('progress', { percent: 30, message: 'Waiting for QR scan...', qr: qrCode });
        await sessionManager.waitForAuth(page, 120000);
    }

    log('info', `✅ Session authenticated. Engine initialized.`);
    progress(40, 'Executing funnel...');

    // ── Simple Funnel Executor ──────────────────────────────────────────────
    // Finds the Start Node
    const startNode = nodes.find(n => n.type === 'triggerNode' || n.id === 'start');
    if (!startNode) {
        log('error', 'No Start node found in funnel.');
        process.exit(1);
    }

    // Process each contact
    let completed = 0;
    for (const contact of contacts) {
        log('info', `▶️ Starting funnel for ${contact}...`);
        
        let currentNodeId = startNode.id;
        
        while (currentNodeId) {
            const node = nodes.find(n => n.id === currentNodeId);
            if (!node) break;
            
            // Execute Node Action
            if (node.type === 'messageNode') {
                const text = node.data?.message || 'Hello';
                const spunText = humanizer.spinMessage(text);
                log('info', `[${contact}] Executing Node [${node.id}]: Send Message`);
                
                try {
                    await page.goto(`https://web.whatsapp.com/send?phone=${contact}&text=${encodeURIComponent(spunText)}`, { waitUntil: 'networkidle2' });
                    await page.waitForSelector('span[data-icon="send"]', { timeout: 30000 });
                    
                    const delayMs = humanizer.calculateDelay(spunText);
                    log('info', `[${contact}] Humanizer delay: ${Math.round(delayMs/1000)}s`);
                    await new Promise(r => setTimeout(r, delayMs));
                    
                    await page.click('span[data-icon="send"]');
                    log('info', `[${contact}] Message sent successfully.`);
                    await new Promise(r => setTimeout(r, 2000)); // Buffer
                } catch (e) {
                    log('warn', `[${contact}] Failed to send: ${e.message}`);
                }
            } else if (node.type === 'delayNode') {
                const delayMinutes = parseInt(node.data?.minutes || '1');
                log('info', `[${contact}] Executing Node [${node.id}]: Wait ${delayMinutes} minutes`);
                await new Promise(r => setTimeout(r, delayMinutes * 60000));
            } else if (node.type === 'conditionNode') {
                log('info', `[${contact}] Executing Node [${node.id}]: Check Condition`);
                // Mock condition check
            }
            
            // Find next node
            const outgoingEdge = edges.find(e => e.source === currentNodeId);
            currentNodeId = outgoingEdge ? outgoingEdge.target : null;
        }
        
        completed++;
        progress(40 + Math.round((completed / contacts.length) * 60), `Completed ${completed}/${contacts.length} contacts`);
    }

    progress(100, 'Funnel execution finished.');
    log('info', `✅ Completed funnel for ${completed} contacts.`);
    await browser.close();
    
    emit('result', { data: { completed, status: 'success' } });
    process.exit(0);
}

main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
