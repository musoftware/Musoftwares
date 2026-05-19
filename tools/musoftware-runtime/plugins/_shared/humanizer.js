/**
 * WhatsApp Humanizer — Shared Module
 * ====================================
 * Makes automated WhatsApp activity indistinguishable from human behavior.
 *
 * Features:
 *   - Variable typing speed simulation (40-80 WPM)
 *   - Bell-curve delay distribution (not uniform random)
 *   - Message variation engine (synonym swaps, emoji rotation)
 *   - Intentional typo + correction simulation
 *   - Online/offline activity windows
 *   - Read-before-reply delay simulation
 *
 * Usage:
 *   const Humanizer = require('../_shared/humanizer');
 *   const h = new Humanizer({ aggressiveness: 'moderate' });
 *
 *   const msg = h.varyMessage('Hello {name}, check our deals!');
 *   await h.simulateTyping(page, msg);
 *   await h.humanDelay('between_messages');
 */

'use strict';

// ── Configuration Presets ────────────────────────────────────────────────────

const PRESETS = {
    conservative: {
        typingWPM:       [35, 55],       // words per minute range
        betweenMessages: [8000, 25000],  // ms between messages
        readDelay:       [2000, 8000],   // ms to "read" before replying
        typoChance:      0.03,           // 3% of messages get typos
        variationLevel:  'low',
        activeHours:     [8, 22],        // only active 8AM-10PM
        maxDailyMessages: 80,
    },
    moderate: {
        typingWPM:       [40, 70],
        betweenMessages: [4000, 15000],
        readDelay:       [1000, 5000],
        typoChance:      0.06,
        variationLevel:  'medium',
        activeHours:     [7, 23],
        maxDailyMessages: 200,
    },
    aggressive: {
        typingWPM:       [55, 85],
        betweenMessages: [2000, 8000],
        readDelay:       [500, 3000],
        typoChance:      0.04,
        variationLevel:  'medium',
        activeHours:     [6, 24],
        maxDailyMessages: 500,
    },
};

// ── Emoji Pools (grouped by intent) ─────────────────────────────────────────

const EMOJI_POOLS = {
    greeting:    ['👋', '✋', '🙌', '😊', '🤝', 'مرحبا 👋'],
    excitement:  ['🔥', '🚀', '💥', '⭐', '✨', '💪', '🎉'],
    positive:    ['👍', '✅', '💯', '👏', '🙏', '😎'],
    urgency:     ['⚡', '🏃', '⏰', '🔔', '📢', '❗'],
    money:       ['💰', '💵', '💲', '🤑', '📈', '💎'],
    love:        ['❤️', '😍', '🥰', '💕', '💖'],
    question:    ['🤔', '❓', '💭', '🧐'],
    cta:         ['👇', '📲', '🔗', '➡️', '📩'],
};

// ── Synonym / Variation Maps ─────────────────────────────────────────────────

const VARIATIONS = {
    'hello':       ['hello', 'hi', 'hey', 'hi there', 'hey there'],
    'مرحبا':       ['مرحبا', 'أهلا', 'هلا', 'السلام عليكم', 'أهلين'],
    'how are you': ['how are you', "how's it going", 'how you doing', "what's up"],
    'كيف حالك':    ['كيف حالك', 'كيفك', 'شلونك', 'عامل ايه'],
    'great':       ['great', 'awesome', 'amazing', 'fantastic', 'excellent', 'wonderful'],
    'thanks':      ['thanks', 'thank you', 'thx', 'many thanks', 'appreciate it'],
    'شكرا':        ['شكرا', 'مشكور', 'يعطيك العافية', 'الله يعطيك العافية'],
    'check out':   ['check out', 'take a look at', 'have a look at', 'see'],
    'deal':        ['deal', 'offer', 'special', 'promotion', 'discount'],
    'عرض':         ['عرض', 'تخفيض', 'خصم', 'فرصة', 'عرض خاص'],
    'buy':         ['buy', 'get', 'grab', 'order', 'pick up'],
    'now':         ['now', 'today', 'right now', 'right away', 'ASAP'],
    'please':      ['please', 'pls', 'kindly', 'if you can'],
    'amazing':     ['amazing', 'incredible', 'insane', 'unreal', 'mind-blowing'],
};

// ── Typo Maps (key → common typo) ───────────────────────────────────────────

const TYPO_MAP = {
    'the':   ['teh', 'hte', 'th'],
    'and':   ['adn', 'nad', 'anf'],
    'you':   ['yuo', 'yoi', 'ypu'],
    'your':  ['yoru', 'yuor', 'yoir'],
    'have':  ['hvae', 'ahve', 'hve'],
    'this':  ['tihs', 'thsi', 'thia'],
    'that':  ['taht', 'tath', 'tht'],
    'with':  ['wiht', 'wtih', 'wth'],
    'for':   ['fro', 'ofr', 'fo'],
    'are':   ['aer', 'rae', 'ar'],
    'from':  ['form', 'fomr', 'frm'],
    'will':  ['wil', 'wlil', 'wll'],
    'just':  ['jsut', 'juts', 'jst'],
    'can':   ['cna', 'acn', 'cn'],
    'here':  ['heer', 'hre', 'heer'],
    'want':  ['wnat', 'watn', 'wnt'],
    'price': ['pirce', 'prcei', 'prce'],
    'order': ['oredr', 'oder', 'ordr'],
};

// ── Punctuation Variations ───────────────────────────────────────────────────

const PUNCTUATION_VARIANTS = {
    '!':  ['!', '!!', ' !', ''],
    '?':  ['?', '??', ' ?'],
    '.':  ['.', '..', '...', ''],
    ',':  [',', ' ,', ', '],
};

// ── Class ────────────────────────────────────────────────────────────────────

class Humanizer {

    /**
     * @param {object} opts
     * @param {string} opts.aggressiveness — 'conservative' | 'moderate' | 'aggressive'
     * @param {function} opts.log — optional log function (level, message)
     */
    constructor(opts = {}) {
        const presetName = opts.aggressiveness || 'moderate';
        this.config = { ...PRESETS[presetName], ...(opts.overrides || {}) };
        this.log    = opts.log || (() => {});
        this._messagesSentToday = 0;
        this._dayStart = this._todayKey();
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Generate a human-like delay between messages.
     * Uses a bell-curve distribution centered on the midpoint.
     * @param {'between_messages'|'read_delay'|'before_typing'} context
     * @returns {Promise<number>} actual ms waited
     */
    async humanDelay(context = 'between_messages') {
        let [min, max] = this.config.betweenMessages;

        if (context === 'read_delay') {
            [min, max] = this.config.readDelay;
        } else if (context === 'before_typing') {
            min = 300;
            max = 1500;
        }

        const ms = this._bellCurveRandom(min, max);
        this.log('debug', `[humanizer] ${context} delay: ${ms}ms`);
        await this._sleep(ms);
        return ms;
    }

    /**
     * Simulate human typing on a Puppeteer page input.
     * Types character by character with variable speed.
     * @param {import('puppeteer').Page} page
     * @param {string} text
     * @param {string} selector — CSS selector for the input element
     */
    async simulateTyping(page, text, selector = '[data-testid="conversation-compose-box-input"]') {
        const [minWPM, maxWPM] = this.config.typingWPM;
        const wpm = this._bellCurveRandom(minWPM, maxWPM);

        // Average word = 5 chars → chars per minute → ms per char
        const charsPerMin = wpm * 5;
        const baseMsPerChar = 60000 / charsPerMin;

        await page.focus(selector);
        await this._sleep(this._bellCurveRandom(200, 600)); // pause before starting

        for (let i = 0; i < text.length; i++) {
            const char = text[i];

            // Variable speed per character
            let delay = baseMsPerChar * (0.5 + Math.random());

            // Pauses after punctuation (like humans think)
            if ('.!?,;:'.includes(char)) {
                delay += this._bellCurveRandom(200, 800);
            }

            // Slight pause at word boundaries
            if (char === ' ') {
                delay += this._bellCurveRandom(50, 200);
            }

            // Occasional longer pauses (thinking)
            if (Math.random() < 0.02) {
                delay += this._bellCurveRandom(500, 2000);
            }

            await page.keyboard.type(char, { delay: Math.round(delay) });
        }

        this.log('debug', `[humanizer] Typed ${text.length} chars at ~${wpm} WPM`);
    }

    /**
     * Apply variations to a message to make it look unique.
     * @param {string} message
     * @returns {string} varied message
     */
    varyMessage(message) {
        let result = message;
        const level = this.config.variationLevel;

        // 1. Synonym replacement
        if (level !== 'none') {
            result = this._applySynonyms(result);
        }

        // 2. Emoji variation
        if (level === 'medium' || level === 'high') {
            result = this._varyEmojis(result);
        }

        // 3. Punctuation variation
        if (level === 'medium' || level === 'high') {
            result = this._varyPunctuation(result);
        }

        // 4. Capitalization variation (sometimes lowercase, sometimes sentence case)
        if (Math.random() < 0.15 && level !== 'low') {
            // Occasionally send in all lowercase (very human on WhatsApp)
            result = result.toLowerCase();
        }

        // 5. Typo simulation (then correct it)
        if (Math.random() < this.config.typoChance) {
            result = this._injectTypo(result);
        }

        return result;
    }

    /**
     * Generate a typo + correction message pair.
     * Returns an array: ['message with typo', 'corrected*'] or ['message'] if no typo.
     * @param {string} message
     * @returns {string[]}
     */
    generateWithCorrection(message) {
        if (Math.random() > this.config.typoChance) {
            return [message];
        }

        const words = message.split(' ');
        const candidates = words.filter(w => TYPO_MAP[w.toLowerCase()]);

        if (!candidates.length) return [message];

        const target = this._pick(candidates);
        const typo = this._pick(TYPO_MAP[target.toLowerCase()]);
        const typoMsg = message.replace(target, typo);

        // Correction format varies
        const corrections = [
            `${target}*`,
            `*${target}`,
            `sorry, ${target}`,
            `I meant ${target}`,
        ];

        return [typoMsg, this._pick(corrections)];
    }

    /**
     * Check if now is within active hours.
     * @returns {boolean}
     */
    isActiveHour() {
        const hour = new Date().getHours();
        const [start, end] = this.config.activeHours;
        return hour >= start && hour < end;
    }

    /**
     * Check if daily message limit is reached.
     * @returns {boolean}
     */
    isDailyLimitReached() {
        if (this._todayKey() !== this._dayStart) {
            this._messagesSentToday = 0;
            this._dayStart = this._todayKey();
        }
        return this._messagesSentToday >= this.config.maxDailyMessages;
    }

    /**
     * Increment daily message counter.
     */
    recordMessageSent() {
        if (this._todayKey() !== this._dayStart) {
            this._messagesSentToday = 0;
            this._dayStart = this._todayKey();
        }
        this._messagesSentToday++;
    }

    /**
     * Get a random message opener for warmup conversations.
     * @param {'en'|'ar'} lang
     * @returns {string}
     */
    getWarmupOpener(lang = 'en') {
        const openers = {
            en: [
                'Hey, how are you?',
                'Hi! What\'s up?',
                'Hello 👋',
                'Hey there, long time!',
                'Hi, hope you\'re doing well',
                'Hey! How\'s everything going?',
                'What\'s new?',
                'Hello! How was your day?',
            ],
            ar: [
                'مرحبا كيفك؟',
                'أهلا وسهلا 👋',
                'هلا والله، شلونك؟',
                'السلام عليكم',
                'أهلين، كيف الحال؟',
                'هاي، شخبارك؟',
                'كيفك اليوم؟',
                'مرحبا، عساك بخير',
            ],
        };
        return this._pick(openers[lang] || openers.en);
    }

    /**
     * Get a random warmup reply to continue conversation.
     * @param {'en'|'ar'} lang
     * @returns {string}
     */
    getWarmupReply(lang = 'en') {
        const replies = {
            en: [
                'Good to hear!',
                'That\'s great 👍',
                'Nice, same here',
                'Yeah totally',
                'Haha for real',
                'I know right!',
                'Sounds good',
                'Cool cool',
                'That makes sense',
                'Oh interesting',
            ],
            ar: [
                'الحمد لله 😊',
                'تمام الحمد لله',
                'حلو والله',
                'ههههه فعلا',
                'صح كلامك',
                'إن شاء الله',
                'ممتاز 👍',
                'الله يعطيك العافية',
                'تسلم والله',
                'اي والله',
            ],
        };
        return this._pick(replies[lang] || replies.en);
    }

    /**
     * Calculate optimal wait time until next active window.
     * @returns {number} ms to wait (0 if currently active)
     */
    msUntilActiveWindow() {
        if (this.isActiveHour()) return 0;

        const now = new Date();
        const [startHour] = this.config.activeHours;
        const next = new Date(now);
        next.setHours(startHour, 0, 0, 0);

        if (next <= now) next.setDate(next.getDate() + 1);

        return next.getTime() - now.getTime();
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Generate a bell-curve (normal) distributed random number between min and max.
     * Uses the Box-Muller transform for a more natural distribution.
     */
    _bellCurveRandom(min, max) {
        // Box-Muller transform: two uniform randoms → one normal random
        const u1 = Math.random();
        const u2 = Math.random();
        const normal = Math.sqrt(-2.0 * Math.log(u1)) * Math.cos(2.0 * Math.PI * u2);

        // Map to [0,1] with mean 0.5, then scale to [min, max]
        const normalized = Math.max(0, Math.min(1, 0.5 + normal * 0.15));
        return Math.round(min + normalized * (max - min));
    }

    _applySynonyms(text) {
        let result = text;
        for (const [word, alternatives] of Object.entries(VARIATIONS)) {
            const regex = new RegExp(`\\b${this._escapeRegex(word)}\\b`, 'gi');
            if (regex.test(result) && Math.random() < 0.4) {
                const replacement = this._pick(alternatives);
                result = result.replace(regex, replacement);
            }
        }
        return result;
    }

    _varyEmojis(text) {
        // Replace existing emojis with alternatives from the same pool
        for (const [, pool] of Object.entries(EMOJI_POOLS)) {
            for (const emoji of pool) {
                if (text.includes(emoji) && Math.random() < 0.3) {
                    text = text.replace(emoji, this._pick(pool));
                }
            }
        }
        return text;
    }

    _varyPunctuation(text) {
        for (const [punct, variants] of Object.entries(PUNCTUATION_VARIANTS)) {
            if (text.includes(punct) && Math.random() < 0.2) {
                // Only replace the last occurrence
                const idx = text.lastIndexOf(punct);
                text = text.substring(0, idx) + this._pick(variants) + text.substring(idx + 1);
            }
        }
        return text;
    }

    _injectTypo(text) {
        const words = text.split(' ');
        for (let i = 0; i < words.length; i++) {
            const lower = words[i].toLowerCase();
            if (TYPO_MAP[lower] && Math.random() < 0.5) {
                words[i] = this._pick(TYPO_MAP[lower]);
                break; // only one typo per message
            }
        }
        return words.join(' ');
    }

    _pick(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }

    _escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    _todayKey() {
        return new Date().toISOString().slice(0, 10);
    }

    _sleep(ms) {
        return new Promise(r => setTimeout(r, ms));
    }
}

// ── Export ────────────────────────────────────────────────────────────────────

module.exports = Humanizer;
module.exports.PRESETS = PRESETS;
module.exports.EMOJI_POOLS = EMOJI_POOLS;
module.exports.VARIATIONS = VARIATIONS;
