/**
 * WhatsApp Bulk Sender — Plugin Worker
 * =====================================
 * Runs as a child process spawned by the Node.js Agent.
 * Communicates via stdout JSON lines.
 *
 * Protocol (stdout → agent):
 *   { type: "log",      level: "info|warn|error", message }
 *   { type: "progress", percent: 0-100, message }
 *   { type: "result",   data: { sent, failed, skipped } }
 *   { type: "error",    message }
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   {
 *     action:     "send_bulk" | "send_single" | "check_session",
 *     contacts:   [{ phone: "+1...", name: "John" }],
 *     message:    "Hello {name}!",
 *     media_url:  null,
 *     delay_ms:   3000,
 *     session_id: "default"
 *   }
 */

'use strict';

// ── IPC helpers ──────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, message) => emit('log', { level, message });
const progress = (percent, message) => emit('progress', { percent, message });

// ── Session management ───────────────────────────────────────────────────────
const path = require('path');
const fs   = require('fs');

const SESSIONS_DIR = path.join(__dirname, 'sessions');
if (!fs.existsSync(SESSIONS_DIR)) fs.mkdirSync(SESSIONS_DIR, { recursive: true });

// ── Main ─────────────────────────────────────────────────────────────────────
async function main() {
    log('info', '🟢 WhatsApp Sender starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const {
        action     = 'send_bulk',
        contacts   = [],
        message    = '',
        media_url  = null,
        delay_ms   = 3000,
        session_id = 'default',
    } = params;

    log('info', `Action: ${action} | Contacts: ${contacts.length} | Session: ${session_id}`);

    // ── Action dispatch ─────────────────────────────────────────────────────
    switch (action) {
        case 'check_session':
            await checkSession(session_id);
            break;
        case 'send_single':
            if (!contacts.length) { emit('error', { message: 'No contact provided' }); process.exit(1); }
            await sendSingle(contacts[0], message, media_url, session_id);
            break;
        case 'send_bulk':
            if (!contacts.length) { emit('error', { message: 'No contacts provided' }); process.exit(1); }
            await sendBulk(contacts, message, media_url, delay_ms, session_id);
            break;
        default:
            emit('error', { message: `Unknown action: ${action}` });
            process.exit(1);
    }
}

// ── Puppeteer session bootstrap ──────────────────────────────────────────────
async function launchBrowser(sessionId) {
    let puppeteer;
    try {
        puppeteer = require('puppeteer');
    } catch {
        try {
            puppeteer = require('puppeteer-core');
        } catch {
            emit('error', { message: 'puppeteer or puppeteer-core not installed. Run: npm install puppeteer' });
            process.exit(1);
        }
    }

    const userDataDir = path.join(SESSIONS_DIR, sessionId);
    log('info', `Launching browser (session: ${userDataDir})`);

    const browser = await puppeteer.launch({
        headless: false,  // WhatsApp Web needs visible browser for QR scan
        userDataDir,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-gpu',
            '--window-size=1280,800',
        ],
    });

    const page = (await browser.pages())[0] || await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });
    return { browser, page };
}

async function openWhatsApp(page) {
    log('info', 'Opening WhatsApp Web...');
    await page.goto('https://web.whatsapp.com', { waitUntil: 'domcontentloaded', timeout: 30000 });

    // Wait for either the QR code or the main chat list
    log('info', 'Waiting for WhatsApp to load (scan QR if needed)...');
    await page.waitForSelector('[data-testid="chat-list"], canvas[aria-label*="QR"]', { timeout: 120000 });

    // Check if we need QR scan
    const needsQR = await page.$('canvas[aria-label*="QR"]');
    if (needsQR) {
        log('info', '📱 QR Code displayed — scan with your phone to continue');
        emit('progress', { percent: 0, message: 'Waiting for QR scan...' });
        // Wait for chat list to appear (user scanned QR)
        await page.waitForSelector('[data-testid="chat-list"]', { timeout: 300000 }); // 5 min
        log('info', '✓ QR scanned — WhatsApp connected');
    } else {
        log('info', '✓ Session restored — already logged in');
    }
}

// ── Actions ──────────────────────────────────────────────────────────────────

async function checkSession(sessionId) {
    progress(10, 'Checking session...');
    const { browser, page } = await launchBrowser(sessionId);
    try {
        await openWhatsApp(page);
        progress(100, 'Session OK');
        emit('result', { data: { status: 'connected', session_id: sessionId } });
    } finally {
        await browser.close();
    }
}

async function sendSingle(contact, message, mediaUrl, sessionId) {
    progress(10, `Sending to ${contact.phone}...`);
    const { browser, page } = await launchBrowser(sessionId);
    try {
        await openWhatsApp(page);
        const ok = await sendMessage(page, contact, message, mediaUrl);
        progress(100, 'Done');
        emit('result', { data: { sent: ok ? 1 : 0, failed: ok ? 0 : 1, total: 1 } });
    } finally {
        await browser.close();
    }
}

async function sendBulk(contacts, messageTemplate, mediaUrl, delayMs, sessionId) {
    progress(5, 'Launching browser...');
    const { browser, page } = await launchBrowser(sessionId);
    let sent = 0, failed = 0, skipped = 0;

    try {
        await openWhatsApp(page);
        progress(10, `Connected — starting campaign (${contacts.length} contacts)`);

        for (let i = 0; i < contacts.length; i++) {
            const contact = contacts[i];
            const pct = Math.round(10 + (i / contacts.length) * 85);
            progress(pct, `Sending ${i + 1}/${contacts.length}: ${contact.phone}`);

            // Personalize message
            const personalMsg = messageTemplate
                .replace(/{name}/g,  contact.name  || '')
                .replace(/{phone}/g, contact.phone || '');

            try {
                const ok = await sendMessage(page, contact, personalMsg, mediaUrl);
                if (ok) {
                    sent++;
                    log('info', `✓ Sent to ${contact.phone}`);
                } else {
                    skipped++;
                    log('warn', `⚠ Skipped ${contact.phone}`);
                }
            } catch (err) {
                failed++;
                log('error', `✗ Failed ${contact.phone}: ${err.message}`);
            }

            // Delay between messages
            if (i < contacts.length - 1) {
                const jitter = Math.floor(Math.random() * 1000);
                await sleep(delayMs + jitter);
            }
        }

        progress(100, 'Campaign complete');
        log('info', `Campaign done — sent: ${sent}, failed: ${failed}, skipped: ${skipped}`);
        emit('result', { data: { sent, failed, skipped, total: contacts.length } });

    } finally {
        await browser.close();
    }
}

async function sendMessage(page, contact, message, mediaUrl) {
    const phone = contact.phone.replace(/[^0-9]/g, '');
    if (!phone || phone.length < 7) return false;

    // Use WhatsApp Web direct link to open chat
    const chatUrl = `https://web.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(message)}`;
    await page.goto(chatUrl, { waitUntil: 'domcontentloaded', timeout: 20000 });

    // Wait for the send button to appear
    try {
        await page.waitForSelector('[data-testid="send"], [data-icon="send"]', { timeout: 15000 });
    } catch {
        log('warn', `Could not find send button for ${phone} — number may not be on WhatsApp`);
        return false;
    }

    // If media, handle attachment
    if (mediaUrl) {
        // Media sending would require additional Puppeteer interaction
        // (click attach, upload file, etc.) — simplified for now
        log('info', `Media attachment: ${mediaUrl} (attach logic TBD)`);
    }

    // Click send
    const sendBtn = await page.$('[data-testid="send"], [data-icon="send"]');
    if (sendBtn) {
        await sendBtn.click();
        await sleep(1000); // wait for message to send
        return true;
    }

    return false;
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

// ── Run ──────────────────────────────────────────────────────────────────────
main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
