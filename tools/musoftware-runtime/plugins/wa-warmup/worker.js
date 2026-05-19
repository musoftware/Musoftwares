/**
 * WhatsApp Number Warmup — Plugin Worker
 * ========================================
 * Gradually warms up WhatsApp numbers to build trust scores
 * and prevent bans when transitioning to bulk campaigns.
 *
 * Protocol (stdout → agent):
 *   { type: "log",      level: "info|warn|error", message }
 *   { type: "progress", percent: 0-100, message }
 *   { type: "result",   data: { ... } }
 *   { type: "error",    message }
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   {
 *     action:       "warmup_start" | "warmup_status" | "health_check",
 *     session_id:   "number-1",
 *     pool_numbers: ["+1...", "+2..."],  // warmup conversation partners
 *     language:     "en" | "ar",
 *     warmup_day:   1,                   // current day in schedule (1-14)
 *     max_messages: 10,                  // override for this session
 *     proxy:        null                 // optional proxy URL
 *   }
 *
 * Warmup Schedule:
 *   Day 1-3:   5-10 msgs/day, 100% reply rate, simple conversations
 *   Day 4-7:   20-30 msgs/day, 80% reply rate, add media/emojis
 *   Day 8-14:  50-80 msgs/day, 60% reply rate, group activity
 *   Day 15+:   Ready for campaigns (100-200/day with humanizer)
 */

'use strict';

// ── IPC helpers ──────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, message) => emit('log', { level, message });
const progress = (percent, message) => emit('progress', { percent, message });

// ── Dependencies ─────────────────────────────────────────────────────────────
const SessionManager = require('../_shared/session-manager');
const Humanizer      = require('../_shared/humanizer');
const HealthScorer   = require('../_shared/health-scorer');
const { SELECTORS }  = require('../_shared/session-manager');

// ── Warmup Schedule ──────────────────────────────────────────────────────────

const WARMUP_SCHEDULE = {
    // Day range → configuration
    1:  { msgsPerDay: [5, 10],   replyRate: 1.0, mediaChance: 0.0, groupActivity: false, aggressiveness: 'conservative' },
    2:  { msgsPerDay: [5, 10],   replyRate: 1.0, mediaChance: 0.0, groupActivity: false, aggressiveness: 'conservative' },
    3:  { msgsPerDay: [8, 12],   replyRate: 1.0, mediaChance: 0.05, groupActivity: false, aggressiveness: 'conservative' },
    4:  { msgsPerDay: [15, 25],  replyRate: 0.9, mediaChance: 0.10, groupActivity: false, aggressiveness: 'moderate' },
    5:  { msgsPerDay: [20, 30],  replyRate: 0.85, mediaChance: 0.15, groupActivity: false, aggressiveness: 'moderate' },
    6:  { msgsPerDay: [25, 35],  replyRate: 0.80, mediaChance: 0.15, groupActivity: true,  aggressiveness: 'moderate' },
    7:  { msgsPerDay: [25, 40],  replyRate: 0.80, mediaChance: 0.20, groupActivity: true,  aggressiveness: 'moderate' },
    8:  { msgsPerDay: [40, 55],  replyRate: 0.70, mediaChance: 0.20, groupActivity: true,  aggressiveness: 'moderate' },
    9:  { msgsPerDay: [45, 60],  replyRate: 0.70, mediaChance: 0.25, groupActivity: true,  aggressiveness: 'moderate' },
    10: { msgsPerDay: [50, 65],  replyRate: 0.65, mediaChance: 0.25, groupActivity: true,  aggressiveness: 'moderate' },
    11: { msgsPerDay: [55, 70],  replyRate: 0.60, mediaChance: 0.25, groupActivity: true,  aggressiveness: 'moderate' },
    12: { msgsPerDay: [60, 75],  replyRate: 0.60, mediaChance: 0.30, groupActivity: true,  aggressiveness: 'moderate' },
    13: { msgsPerDay: [65, 80],  replyRate: 0.55, mediaChance: 0.30, groupActivity: true,  aggressiveness: 'moderate' },
    14: { msgsPerDay: [70, 85],  replyRate: 0.55, mediaChance: 0.30, groupActivity: true,  aggressiveness: 'aggressive' },
};

// ── Main ─────────────────────────────────────────────────────────────────────

async function main() {
    log('info', '🟢 WhatsApp Warmup starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const {
        action       = 'warmup_start',
        session_id   = 'warmup-default',
        pool_numbers = [],
        language     = 'en',
        warmup_day   = 1,
        max_messages = null,
        proxy        = null,
    } = params;

    log('info', `Action: ${action} | Session: ${session_id} | Day: ${warmup_day} | Lang: ${language}`);

    switch (action) {
        case 'warmup_start':
            await runWarmup(session_id, pool_numbers, language, warmup_day, max_messages, proxy);
            break;
        case 'warmup_status':
            await getWarmupStatus(session_id);
            break;
        case 'health_check':
            await healthCheck(session_id, proxy);
            break;
        default:
            emit('error', { message: `Unknown action: ${action}` });
            process.exit(1);
    }
}

// ── Warmup Execution ─────────────────────────────────────────────────────────

async function runWarmup(sessionId, poolNumbers, language, day, maxOverride, proxy) {
    if (!poolNumbers.length) {
        emit('error', { message: 'No pool numbers provided. Warmup requires partner numbers to converse with.' });
        process.exit(1);
    }

    const schedule = WARMUP_SCHEDULE[Math.min(day, 14)] || WARMUP_SCHEDULE[14];
    const humanizer = new Humanizer({
        aggressiveness: schedule.aggressiveness,
        log,
    });
    const scorer = new HealthScorer({ log });

    // Check if within active hours
    if (!humanizer.isActiveHour()) {
        const waitMs = humanizer.msUntilActiveWindow();
        const waitHours = Math.round(waitMs / 3600000 * 10) / 10;
        log('info', `⏰ Outside active hours. Next window in ${waitHours} hours.`);
        emit('result', { data: {
            status: 'waiting',
            reason: 'outside_active_hours',
            nextWindowMs: waitMs,
            day,
        }});
        return;
    }

    // Calculate messages for today
    const [minMsgs, maxMsgs] = schedule.msgsPerDay;
    const targetMessages = maxOverride || Math.floor(minMsgs + Math.random() * (maxMsgs - minMsgs));

    log('info', `📊 Day ${day} schedule: ${targetMessages} messages, ${Math.round(schedule.replyRate * 100)}% reply rate`);
    progress(5, `Starting warmup — Day ${day}, target: ${targetMessages} messages`);

    // Launch session
    const sessionMgr = new SessionManager({ log });
    let session;

    try {
        session = await sessionMgr.acquire(sessionId, { proxy });
        progress(15, 'Connected to WhatsApp');
    } catch (err) {
        emit('error', { message: `Failed to connect: ${err.message}` });
        process.exit(1);
    }

    const { page } = session;
    let sent = 0, received = 0, failed = 0;

    try {
        // ── Send warmup messages ─────────────────────────────────────────
        for (let i = 0; i < targetMessages; i++) {
            const pct = Math.round(15 + (i / targetMessages) * 75);
            const targetPhone = poolNumbers[i % poolNumbers.length];
            progress(pct, `Message ${i + 1}/${targetMessages} → ${targetPhone}`);

            // Check daily limit
            if (humanizer.isDailyLimitReached()) {
                log('info', '⚠ Daily message limit reached — stopping warmup');
                break;
            }

            try {
                // Generate appropriate warmup message
                const isOpener = i === 0 || Math.random() < 0.3;
                let message;

                if (isOpener) {
                    message = humanizer.getWarmupOpener(language);
                } else {
                    message = humanizer.getWarmupReply(language);
                }

                // Apply message variation
                message = humanizer.varyMessage(message);

                // Navigate to contact
                const phone = targetPhone.replace(/[^0-9]/g, '');
                const chatUrl = `https://web.whatsapp.com/send?phone=${phone}`;
                await page.goto(chatUrl, { waitUntil: 'domcontentloaded', timeout: 20000 });

                // Wait for compose box
                try {
                    await page.waitForSelector(SELECTORS.composeBox, { timeout: 15000 });
                } catch {
                    log('warn', `Could not open chat for ${phone} — skipping`);
                    failed++;
                    continue;
                }

                // Read delay — simulate reading existing messages
                await humanizer.humanDelay('read_delay');

                // Type message with human simulation
                await humanizer.simulateTyping(page, message);

                // Small pause before sending
                await humanizer.humanDelay('before_typing');

                // Press Enter to send
                await page.keyboard.press('Enter');
                await sleep(1000);

                sent++;
                humanizer.recordMessageSent();
                log('info', `✓ Sent warmup message ${i + 1} to ${phone}`);

                // ── Simulate receiving a reply (if pool partner responds) ────
                if (Math.random() < schedule.replyRate) {
                    // Wait for potential reply (simulated delay)
                    await humanizer.humanDelay('between_messages');

                    // Check if a new message appeared (basic detection)
                    const hasNewMessage = await detectNewMessage(page);
                    if (hasNewMessage) {
                        received++;
                        log('info', `↩ Received reply from ${phone}`);

                        // Sometimes reply back to create bi-directional conversation
                        if (Math.random() < 0.6) {
                            const reply = humanizer.getWarmupReply(language);
                            await humanizer.humanDelay('read_delay');
                            await humanizer.simulateTyping(page, humanizer.varyMessage(reply));
                            await page.keyboard.press('Enter');
                            sent++;
                            humanizer.recordMessageSent();
                            log('info', `↪ Replied back to ${phone}`);
                        }
                    }
                }

                // Delay between contacts
                if (i < targetMessages - 1) {
                    await humanizer.humanDelay('between_messages');
                }

            } catch (err) {
                failed++;
                log('error', `✗ Warmup message ${i + 1} failed: ${err.message}`);
            }
        }

        // ── Calculate health score ───────────────────────────────────────
        const metrics = {
            ageDays:              day,
            messagesSent:         sent,
            messagesReceived:     received,
            conversations:        poolNumbers.length,
            biDirectional:        Math.min(received, poolNumbers.length),
            blocksReceived:       0,
            activeDays:           day,
            totalDays:            day,
            warmupDaysCompleted:  day,
            warmupDaysTotal:      14,
            avgDailyMessages:     sent,
        };

        const healthResult = scorer.calculate(metrics);

        progress(100, 'Warmup session complete');
        log('info', `📊 Warmup Day ${day} complete — Sent: ${sent}, Received: ${received}, Failed: ${failed}`);
        log('info', `🏥 Health Score: ${healthResult.score}/100 (${healthResult.grade}) — Risk: ${healthResult.risk}`);

        emit('result', { data: {
            status:         'completed',
            day,
            sent,
            received,
            failed,
            replyRate:      sent > 0 ? Math.round((received / sent) * 100) : 0,
            healthScore:    healthResult.score,
            healthGrade:    healthResult.grade,
            risk:           healthResult.risk,
            banProbability: healthResult.banProbability,
            recommendation: healthResult.recommendation,
            maxDailyRecommended: healthResult.maxDailyRecommended,
            nextDay:        Math.min(day + 1, 14),
            isReady:        day >= 14 && healthResult.score >= 70,
            breakdown:      healthResult.breakdown,
        }});

    } finally {
        await sessionMgr.release(sessionId);
    }
}

// ── Health Check ─────────────────────────────────────────────────────────────

async function healthCheck(sessionId, proxy) {
    progress(10, 'Running health check...');

    const sessionMgr = new SessionManager({ log });
    let session;

    try {
        session = await sessionMgr.acquire(sessionId, { proxy });
        progress(50, 'Connected — checking health...');

        const result = await sessionMgr.checkHealth(sessionId);

        progress(100, 'Health check complete');
        emit('result', { data: {
            status:    'completed',
            alive:     result.alive,
            state:     result.state,
            health:    result.health,
        }});

    } catch (err) {
        emit('error', { message: `Health check failed: ${err.message}` });
        process.exit(1);
    } finally {
        await sessionMgr.release(sessionId);
    }
}

// ── Warmup Status ────────────────────────────────────────────────────────────

async function getWarmupStatus(sessionId) {
    const scorer = new HealthScorer({ log });
    const sessionMgr = new SessionManager({ log });
    const sessions = sessionMgr.listSessions();

    const sessionInfo = sessions.find(s => s.sessionId === sessionId);
    if (!sessionInfo) {
        emit('result', { data: {
            status: 'not_found',
            sessionId,
        }});
        return;
    }

    const health = sessionInfo.health;
    const result = scorer.calculate({
        ageDays:              health.ageDays || 0,
        messagesSent:         health.messagesSent || 0,
        messagesReceived:     health.messagesRecv || 0,
        conversations:        0,
        biDirectional:        0,
        blocksReceived:       health.warningCount || 0,
        activeDays:           health.connectCount || 0,
        totalDays:            health.ageDays || 1,
        warmupDaysCompleted:  health.warmupDaysCompleted || 0,
        warmupDaysTotal:      14,
        avgDailyMessages:     health.messagesSent ? Math.round(health.messagesSent / Math.max(1, health.connectCount)) : 0,
    });

    emit('result', { data: {
        status:      'found',
        sessionId,
        state:       sessionInfo.state,
        health,
        score:       result.score,
        grade:       result.grade,
        risk:        result.risk,
        isReady:     result.score >= 70 && (health.warmupDaysCompleted || 0) >= 14,
        recommendation: result.recommendation,
    }});
}

// ── Helpers ──────────────────────────────────────────────────────────────────

async function detectNewMessage(page) {
    try {
        // Check for unread message indicators in the current chat
        const unread = await page.$('[data-testid="msg-meta"] [data-icon="msg-dblcheck-ack"]');
        return !!unread;
    } catch {
        return false;
    }
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

// ── Run ──────────────────────────────────────────────────────────────────────
main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
