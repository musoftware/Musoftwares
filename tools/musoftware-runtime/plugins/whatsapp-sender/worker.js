/**
 * WhatsApp Bulk Sender — Plugin Worker (v2)
 * ==========================================
 * Upgraded with shared infrastructure:
 *   - Humanizer: natural typing, delays, message variation
 *   - SessionManager: multi-session, health tracking, proxy support
 *   - HealthScorer: trust scoring, ban prediction
 *
 * Runs as a child process spawned by the Node.js Agent.
 * Communicates via stdout JSON lines.
 *
 * Protocol (stdout → agent):
 *   { type: "log",      level: "info|warn|error", message }
 *   { type: "progress", percent: 0-100, message }
 *   { type: "result",   data: { sent, failed, skipped, deliverability } }
 *   { type: "error",    message }
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   {
 *     action:         "send_bulk" | "send_single" | "check_session",
 *     contacts:       [{ phone: "+1...", name: "John" }],
 *     message:        "Hello {name}!",
 *     media_url:      null,
 *     delay_ms:       3000,           // fallback if humanizer is off
 *     session_id:     "default",
 *     humanize:       true,           // NEW — enable humanizer
 *     aggressiveness: "moderate",     // NEW — conservative | moderate | aggressive
 *     language:       "en",           // NEW — for message variations
 *     proxy:          null,           // NEW — proxy URL
 *     track_delivery: true,           // NEW — track read/delivered status
 *     stop_on_block:  true,           // NEW — stop campaign if blocked
 *     max_block_rate: 0.05,           // NEW — max 5% block rate before stopping
 *   }
 */

'use strict';

// ── IPC helpers ──────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, message) => emit('log', { level, message });
const progress = (percent, message) => emit('progress', { percent, message });

// ── Shared Modules ───────────────────────────────────────────────────────────
const SessionManager = require('../_shared/session-manager');
const Humanizer      = require('../_shared/humanizer');
const HealthScorer   = require('../_shared/health-scorer');
const { SELECTORS }  = require('../_shared/session-manager');

// ── Main ─────────────────────────────────────────────────────────────────────
async function main() {
    log('info', '🟢 WhatsApp Sender v2 starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const {
        action         = 'send_bulk',
        contacts       = [],
        message        = '',
        media_url      = null,
        delay_ms       = 3000,
        session_id     = 'default',
        humanize       = true,
        aggressiveness = 'moderate',
        language       = 'en',
        proxy          = null,
        track_delivery = true,
        stop_on_block  = true,
        max_block_rate = 0.05,
    } = params;

    log('info', `Action: ${action} | Contacts: ${contacts.length} | Session: ${session_id} | Humanize: ${humanize}`);

    // Initialize shared modules
    const sessionMgr = new SessionManager({ log });
    const humanizer  = humanize ? new Humanizer({ aggressiveness, log }) : null;
    const scorer     = new HealthScorer({ log });

    // Check active hours (if humanizer enabled)
    if (humanizer && !humanizer.isActiveHour()) {
        log('warn', '⏰ Outside active hours — sending may increase ban risk');
    }

    // ── Action dispatch ─────────────────────────────────────────────────────
    switch (action) {
        case 'check_session':
            await checkSession(sessionMgr, session_id, proxy);
            break;
        case 'send_single':
            if (!contacts.length) { emit('error', { message: 'No contact provided' }); process.exit(1); }
            await sendSingle(sessionMgr, humanizer, scorer, contacts[0], message, media_url, session_id, proxy, track_delivery);
            break;
        case 'send_bulk':
            if (!contacts.length) { emit('error', { message: 'No contacts provided' }); process.exit(1); }
            await sendBulk(sessionMgr, humanizer, scorer, contacts, message, media_url, delay_ms, session_id, proxy, track_delivery, stop_on_block, max_block_rate);
            break;
        default:
            emit('error', { message: `Unknown action: ${action}` });
            process.exit(1);
    }
}

// ── Actions ──────────────────────────────────────────────────────────────────

async function checkSession(sessionMgr, sessionId, proxy) {
    progress(10, 'Checking session...');
    try {
        await sessionMgr.acquire(sessionId, { proxy });
        const health = await sessionMgr.checkHealth(sessionId);
        progress(100, 'Session OK');
        emit('result', { data: {
            status: health.alive ? 'connected' : 'disconnected',
            state: health.state,
            session_id: sessionId,
            health: health.health,
        }});
    } finally {
        await sessionMgr.release(sessionId);
    }
}

async function sendSingle(sessionMgr, humanizer, scorer, contact, message, mediaUrl, sessionId, proxy, trackDelivery) {
    progress(10, `Sending to ${contact.phone}...`);
    try {
        const session = await sessionMgr.acquire(sessionId, { proxy });
        const personalMsg = personalizeMessage(message, contact);
        const finalMsg = humanizer ? humanizer.varyMessage(personalMsg) : personalMsg;

        const result = await sendMessage(session.page, contact, finalMsg, mediaUrl, humanizer, trackDelivery);

        progress(100, 'Done');
        emit('result', { data: {
            sent: result.sent ? 1 : 0,
            failed: result.sent ? 0 : 1,
            total: 1,
            deliverability: result.deliverability ? [result.deliverability] : [],
        }});
    } finally {
        await sessionMgr.release(sessionId);
    }
}

async function sendBulk(sessionMgr, humanizer, scorer, contacts, messageTemplate, mediaUrl, delayMs, sessionId, proxy, trackDelivery, stopOnBlock, maxBlockRate) {
    progress(5, 'Launching browser...');

    let session;
    try {
        session = await sessionMgr.acquire(sessionId, { proxy });
    } catch (err) {
        emit('error', { message: `Failed to connect: ${err.message}` });
        process.exit(1);
    }

    const { page } = session;
    let sent = 0, failed = 0, skipped = 0, blocked = 0;
    const deliverability = [];

    progress(10, `Connected — starting campaign (${contacts.length} contacts)`);

    try {
        for (let i = 0; i < contacts.length; i++) {
            const contact = contacts[i];
            const pct = Math.round(10 + (i / contacts.length) * 85);
            progress(pct, `Sending ${i + 1}/${contacts.length}: ${contact.phone}`);

            // ── Check daily limit ────────────────────────────────────────
            if (humanizer?.isDailyLimitReached()) {
                log('warn', '⚠ Daily message limit reached — stopping campaign');
                skipped += contacts.length - i;
                break;
            }

            // ── Check block rate ─────────────────────────────────────────
            if (stopOnBlock && sent > 10) {
                const currentBlockRate = blocked / sent;
                if (currentBlockRate > maxBlockRate) {
                    log('error', `🛑 Block rate ${(currentBlockRate * 100).toFixed(1)}% exceeds threshold ${maxBlockRate * 100}% — stopping campaign`);
                    skipped += contacts.length - i;
                    break;
                }
            }

            // ── Personalize & vary message ───────────────────────────────
            const personalMsg = personalizeMessage(messageTemplate, contact);

            // Use humanizer for message variation (typo + correction support)
            let messagesToSend;
            if (humanizer) {
                messagesToSend = humanizer.generateWithCorrection(humanizer.varyMessage(personalMsg));
            } else {
                messagesToSend = [personalMsg];
            }

            try {
                let result;
                for (const msg of messagesToSend) {
                    result = await sendMessage(page, contact, msg, mediaUrl, humanizer, trackDelivery);

                    // Small delay between correction messages
                    if (messagesToSend.length > 1) {
                        await sleep(humanizer ? 1500 + Math.random() * 2000 : 1000);
                    }
                }

                if (result.sent) {
                    sent++;
                    humanizer?.recordMessageSent();
                    log('info', `✓ Sent to ${contact.phone}`);

                    if (result.deliverability) {
                        deliverability.push(result.deliverability);
                        if (result.deliverability.status === 'blocked') {
                            blocked++;
                            log('warn', `🚫 Blocked by ${contact.phone}`);
                        }
                    }
                } else {
                    skipped++;
                    log('warn', `⚠ Skipped ${contact.phone}`);
                }
            } catch (err) {
                failed++;
                log('error', `✗ Failed ${contact.phone}: ${err.message}`);
            }

            // ── Delay between messages ───────────────────────────────────
            if (i < contacts.length - 1) {
                if (humanizer) {
                    await humanizer.humanDelay('between_messages');
                } else {
                    // Legacy delay with jitter
                    const jitter = Math.floor(Math.random() * 1000);
                    await sleep(delayMs + jitter);
                }
            }
        }

        // ── Calculate health after campaign ──────────────────────────────
        const healthMetrics = {
            ageDays:          session.health?.ageDays || 7,
            messagesSent:     sent,
            messagesReceived: deliverability.filter(d => d.status === 'replied').length,
            conversations:    contacts.length,
            biDirectional:    deliverability.filter(d => d.status === 'replied').length,
            blocksReceived:   blocked,
            activeDays:       1,
            totalDays:        1,
            avgDailyMessages: sent,
        };

        const health = scorer.calculate(healthMetrics);

        progress(100, 'Campaign complete');
        log('info', `📊 Campaign done — Sent: ${sent}, Failed: ${failed}, Skipped: ${skipped}, Blocked: ${blocked}`);
        log('info', `🏥 Post-campaign health: ${health.score}/100 (${health.grade}) — Risk: ${health.risk}`);

        emit('result', { data: {
            sent,
            failed,
            skipped,
            blocked,
            total: contacts.length,
            blockRate: sent > 0 ? `${(blocked / sent * 100).toFixed(1)}%` : '0%',
            healthScore: health.score,
            healthGrade: health.grade,
            risk: health.risk,
            banProbability: health.banProbability,
            recommendation: health.recommendation,
            deliverability: trackDelivery ? deliverability : [],
        }});

    } finally {
        await sessionMgr.release(sessionId);
    }
}

// ── Message Sending ──────────────────────────────────────────────────────────

async function sendMessage(page, contact, message, mediaUrl, humanizer, trackDelivery) {
    const phone = contact.phone.replace(/[^0-9]/g, '');
    if (!phone || phone.length < 7) {
        return { sent: false, deliverability: null };
    }

    // Navigate to contact chat
    const chatUrl = `https://web.whatsapp.com/send?phone=${phone}`;
    await page.goto(chatUrl, { waitUntil: 'domcontentloaded', timeout: 20000 });

    // Wait for compose box
    try {
        await page.waitForSelector(SELECTORS.composeBox, { timeout: 15000 });
    } catch {
        log('warn', `Could not find compose box for ${phone} — number may not be on WhatsApp`);
        return { sent: false, deliverability: { phone, status: 'not_found' } };
    }

    // Read delay (humanizer)
    if (humanizer) {
        await humanizer.humanDelay('read_delay');
    }

    // Type message
    if (humanizer) {
        await humanizer.simulateTyping(page, message);
    } else {
        // Legacy: paste entire message at once
        await page.click(SELECTORS.composeBox);
        await page.keyboard.type(message, { delay: 30 });
    }

    // If media, handle attachment (basic support)
    if (mediaUrl) {
        log('info', `Media attachment: ${mediaUrl} (attach logic TBD)`);
    }

    // Small pause before sending (human hesitation)
    if (humanizer) {
        await humanizer.humanDelay('before_typing');
    }

    // Send via Enter key (more natural than clicking button)
    await page.keyboard.press('Enter');
    await sleep(1000);

    // ── Track delivery status ────────────────────────────────────────────
    let deliverability = null;
    if (trackDelivery) {
        deliverability = await trackMessageDelivery(page, phone);
    }

    return { sent: true, deliverability };
}

// ── Deliverability Tracking ──────────────────────────────────────────────────

async function trackMessageDelivery(page, phone) {
    const result = { phone, status: 'sent', timestamp: Date.now() };

    try {
        // Wait a moment for delivery indicators
        await sleep(3000);

        // Check for double check (delivered)
        const delivered = await page.$('[data-testid="msg-dblcheck"]');
        if (delivered) {
            result.status = 'delivered';
        }

        // Check for blue double check (read)
        const read = await page.$('[data-testid="msg-dblcheck-ack"]');
        if (read) {
            result.status = 'read';
        }

        // Check for error/blocked indicators
        const error = await page.$('[data-testid="alert-notification"]');
        if (error) {
            const text = await page.evaluate(el => el?.textContent || '', error);
            if (text.includes('block') || text.includes('حظر')) {
                result.status = 'blocked';
            }
        }

    } catch (err) {
        log('debug', `Delivery tracking error for ${phone}: ${err.message}`);
    }

    return result;
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function personalizeMessage(template, contact) {
    return template
        .replace(/{name}/g,  contact.name  || '')
        .replace(/{phone}/g, contact.phone || '')
        .replace(/{first}/g, (contact.name || '').split(' ')[0] || '')
        .replace(/{company}/g, contact.company || '')
        .replace(/{city}/g, contact.city || '')
        .replace(/{custom1}/g, contact.custom1 || '')
        .replace(/{custom2}/g, contact.custom2 || '');
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

// ── Run ──────────────────────────────────────────────────────────────────────
main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
