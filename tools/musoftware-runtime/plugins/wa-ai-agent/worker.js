/**
 * WhatsApp AI Sales Agent — Plugin Worker (Node.js)
 * ===================================================
 * Connects to WhatsApp using Puppeteer, listens for inbound messages,
 * and responds autonomously using OpenAI (gpt-4o-mini).
 * Specifically engineered for Arabic dialects and context preservation.
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   session_name (string)
 *   api_key (string) - OpenAI API key
 *   system_prompt (string) - Business context
 *   goal (string) - What the AI should achieve
 *   aggressiveness (string) - 'conservative', 'moderate', 'aggressive'
 */

'use strict';

const path = require('path');
const { OpenAI } = require('openai');
const SessionManager = require('../_shared/session-manager');
const Humanizer = require('../_shared/humanizer');

// ── IPC Protocol ─────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, msg) => emit('log', { level, message: msg });
const progress = (pct, msg)   => emit('progress', { percent: pct, message: msg });

const AGENT_SYSTEM_PROMPT = `You are a highly effective AI sales agent operating on WhatsApp.
Your primary goals are to qualify leads, answer their questions based on the provided business context, and guide them toward the stated goal.

CRITICAL INSTRUCTIONS:
1. DIALECT MATCHING: If the user speaks Arabic, you MUST reply in their specific dialect (e.g., Egyptian, Gulf, Levantine). DO NOT use formal Modern Standard Arabic (Fusha) unless the user uses it first.
2. WHATSAPP FORMATTING: Keep your responses short and punchy. People don't read long paragraphs on WhatsApp. Use line breaks and occasional emojis to make it readable.
3. CONVERSATION FLOW: Do not ask more than one question at a time. Guide the conversation naturally.
4. NO HALLUCINATION: If asked something outside the provided business context, politely state you don't have that information and offer to connect them with a human agent.
`;

async function main() {
    log('info', '🤖 WhatsApp AI Agent starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const { session_name, api_key, system_prompt, goal, aggressiveness = 'moderate' } = params;

    if (!api_key) {
        emit('error', { message: 'OpenAI API key is required.' });
        process.exit(1);
    }

    if (!system_prompt) {
        emit('error', { message: 'Business context (system prompt) is required.' });
        process.exit(1);
    }

    const openai = new OpenAI({ apiKey: api_key });
    const humanizer = new Humanizer(aggressiveness);

    progress(10, 'Initializing browser session...');
    const sessionDir = path.join(__dirname, '..', '_sessions', session_name);
    const sessionManager = new SessionManager(sessionDir);

    let browser;
    try {
        browser = await sessionManager.launchBrowser();
    } catch (err) {
        emit('error', { message: 'Failed to launch browser: ' + err.message });
        process.exit(1);
    }

    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    progress(30, 'Loading WhatsApp Web...');
    await page.goto('https://web.whatsapp.com', { waitUntil: 'networkidle2', timeout: 60000 });

    const authStatus = await sessionManager.waitForAuth(page);
    if (authStatus === 'qr') {
        const qrCode = await sessionManager.getQrCode(page);
        log('info', 'QR Code required for authentication.');
        emit('progress', { percent: 40, message: 'Waiting for QR scan...', qr: qrCode });
        await sessionManager.waitForAuth(page, 120000); // wait longer for scan
    }

    log('info', `✅ Session authenticated: ${session_name}`);
    progress(60, 'Injecting WhatsApp Web listeners...');

    // Wait for the chat list to load to ensure WhatsApp is fully initialized
    await page.waitForSelector('div[id="pane-side"]', { timeout: 60000 });

    // Inject our observer into the page to listen for incoming messages
    await page.exposeFunction('onMessageReceived', async (messageData) => {
        handleIncomingMessage(messageData, page, openai, system_prompt, goal, humanizer);
    });

    await page.evaluate(() => {
        // Simplified observer: we monitor the pane-side for unread badges
        // A more robust implementation would use WAPI or inject a deeper listener, 
        // but DOM observation of unread badges is safer for not getting banned.
        
        let processedChats = new Set();
        
        setInterval(() => {
            const unreadChats = document.querySelectorAll('div[id="pane-side"] span[aria-label*="unread message"]');
            
            unreadChats.forEach(badge => {
                const chatContainer = badge.closest('div[role="listitem"]');
                if (chatContainer) {
                    const titleEl = chatContainer.querySelector('span[title]');
                    const previewEl = chatContainer.querySelector('span[dir="ltr"]'); // Last message preview
                    
                    if (titleEl && previewEl) {
                        const contactName = titleEl.getAttribute('title');
                        const messageText = previewEl.innerText;
                        const msgId = contactName + ':' + messageText; // pseudo-ID
                        
                        if (!processedChats.has(msgId)) {
                            processedChats.add(msgId);
                            
                            // Click the chat to open it
                            const clickEvent = new MouseEvent('mousedown', { bubbles: true, cancelable: true, view: window });
                            chatContainer.dispatchEvent(clickEvent);
                            
                            // Send to Node.js backend
                            window.onMessageReceived({
                                contact: contactName,
                                text: messageText,
                                timestamp: Date.now()
                            });
                            
                            // Prevent unbounded memory growth
                            if (processedChats.size > 1000) processedChats.clear();
                        }
                    }
                }
            });
        }, 2000);
    });

    log('info', '🤖 AI Agent is active and listening for messages.');
    progress(100, 'Agent Active');

    // Keep process alive until user stops it
    process.on('SIGINT', async () => {
        log('info', 'Stopping AI Agent...');
        await browser.close();
        process.exit(0);
    });
}

// ── Conversation Memory Store ────────────────────────────────────────────────
const conversationStore = new Map();

async function handleIncomingMessage(msg, page, openai, businessContext, goal, humanizer) {
    log('info', `📨 Received message from ${msg.contact}: "${msg.text}"`);

    // Ensure we have a history array for this contact
    if (!conversationStore.has(msg.contact)) {
        conversationStore.set(msg.contact, [
            { role: 'system', content: AGENT_SYSTEM_PROMPT + '\n\nBusiness Context:\n' + businessContext + '\n\nGoal:\n' + goal }
        ]);
    }

    const history = conversationStore.get(msg.contact);
    history.push({ role: 'user', content: msg.text });

    // Keep history manageable (last 15 messages) to save tokens
    if (history.length > 15) {
        // Keep the system prompt at [0], then keep the last 14 messages
        const recentHistory = [history[0], ...history.slice(history.length - 14)];
        conversationStore.set(msg.contact, recentHistory);
    }

    try {
        log('info', `🧠 Generating AI response for ${msg.contact}...`);
        const response = await openai.chat.completions.create({
            model: 'gpt-4o-mini',
            messages: conversationStore.get(msg.contact),
            temperature: 0.7,
            max_tokens: 150, // Keep WhatsApp messages short
        });

        const replyText = response.choices[0].message.content;
        history.push({ role: 'assistant', content: replyText });

        log('info', `💬 Replying to ${msg.contact}: "${replyText}"`);

        // Use Humanizer to simulate the typing delay before sending
        const delayMs = humanizer.calculateDelay(replyText);
        log('info', `⏱️ Simulating typing for ${Math.round(delayMs / 1000)}s...`);
        
        await new Promise(r => setTimeout(r, delayMs));

        // Note: The chat is already open from the evaluation loop
        // Find the input box and type
        const inputSelector = 'div[title="Type a message"], div[title="اكتب رسالة"]';
        await page.waitForSelector(inputSelector, { timeout: 5000 });
        
        // Clear input first
        await page.click(inputSelector);
        
        // Type out the response with jitter to look human
        for (const char of replyText) {
            await page.keyboard.sendCharacter(char);
            await new Promise(r => setTimeout(r, Math.random() * 50 + 20)); // typing jitter
        }
        
        await page.keyboard.press('Enter');
        log('info', `✅ Message sent to ${msg.contact}`);

    } catch (err) {
        log('error', `❌ Failed to generate or send reply to ${msg.contact}: ${err.message}`);
    }
}

main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
