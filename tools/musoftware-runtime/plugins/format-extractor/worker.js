/**
 * Format DNA Extractor — Plugin Worker (Node.js)
 * ================================================
 * Extracts the "viral format blueprint" from any TikTok video.
 * Not copying content — copying the psychology + structure.
 *
 * Output: A reusable Format Blueprint with:
 *   - Hook type & template
 *   - Content structure (intro/body/CTA)
 *   - Sound strategy recommendation
 *   - Caption template
 *   - Engagement pattern analysis
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   { url: "https://tiktok.com/...", niche: "fitness" }
 */

'use strict';

const https = require('https');

// ── IPC helpers ──────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, msg) => emit('log', { level, message: msg });
const progress = (pct, msg)   => emit('progress', { percent: pct, message: msg });

process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

function get(url) {
    return new Promise((resolve, reject) => {
        const req = https.get(url, {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept': 'application/json',
                'Referer': 'https://www.tikwm.com/',
            },
            timeout: 20000,
        }, res => {
            let data = '';
            res.on('data', c => data += c);
            res.on('end', () => {
                try { resolve(JSON.parse(data)); }
                catch (e) { reject(new Error('Invalid JSON: ' + data.slice(0, 200))); }
            });
        });
        req.on('error', reject);
        req.on('timeout', () => { req.destroy(); reject(new Error('Request timed out')); });
    });
}

const BASE = 'https://www.tikwm.com';

// ── Content Structure Templates ──────────────────────────────────────────────
const STRUCTURES = {
    hook_body_cta: {
        name: 'Hook → Body → CTA',
        description: 'Classic structure: attention grab, deliver value, call to action',
        template: '1. HOOK (0-3s): [Attention grabber]\n2. BODY (3-20s): [Deliver the promise]\n3. CTA (last 3s): [Tell them what to do]',
    },
    problem_agitate_solve: {
        name: 'Problem → Agitate → Solve',
        description: 'Show a problem, make it feel urgent, reveal the solution',
        template: '1. PROBLEM (0-3s): [Show the pain point]\n2. AGITATE (3-10s): [Make it worse / show consequences]\n3. SOLVE (10s+): [Reveal your solution]',
    },
    reveal_format: {
        name: 'Setup → Reveal',
        description: 'Build curiosity, then drop the reveal',
        template: '1. SETUP (0-5s): [Create curiosity / "Wait for it..."]\n2. BUILD (5-15s): [Escalate tension]\n3. REVEAL (last 3s): [The payoff moment]',
    },
    list_format: {
        name: 'List / Countdown',
        description: 'Numbered items, each one a mini-hook',
        template: '1. INTRO (0-2s): "[Number] things that..." \n2. ITEMS (2-25s): [Each item = 3-5 seconds]\n3. BEST ITEM: [Save strongest for last]',
    },
    story_format: {
        name: 'Story Arc',
        description: 'Personal narrative with emotional journey',
        template: '1. HOOK (0-3s): [In media res — start in the middle]\n2. CONTEXT (3-8s): [Quick backstory]\n3. CONFLICT (8-15s): [The struggle/challenge]\n4. RESOLUTION (15s+): [How it ended + lesson]',
    },
    tutorial_format: {
        name: 'Tutorial / How-To',
        description: 'Step-by-step value delivery',
        template: '1. RESULT (0-3s): [Show the end result first]\n2. STEPS (3-20s): [Step-by-step process]\n3. CTA (20s+): [Follow for more / save this]',
    },
};

// ── Detect content structure ─────────────────────────────────────────────────
function detectStructure(caption, duration) {
    const lower = (caption || '').toLowerCase();

    if (/\d+ (things|tips|ways|hacks|reasons|mistakes|steps)/i.test(caption)) return 'list_format';
    if (/how (to|i)|step \d|tutorial|guide/i.test(caption)) return 'tutorial_format';
    if (/story|journey|happened|finally|never thought/i.test(caption)) return 'story_format';
    if (/wait for|watch till|reveal|surprise/i.test(caption)) return 'reveal_format';
    if (/problem|solution|fix|wrong|mistake/i.test(caption)) return 'problem_agitate_solve';

    return 'hook_body_cta'; // default
}

// ── Extract hook template ────────────────────────────────────────────────────
function extractHookTemplate(caption) {
    const firstLine = (caption || '').split('\n')[0] || (caption || '').slice(0, 80);
    const lower = firstLine.toLowerCase();

    // Abstract the specific content into a template
    let template = firstLine;
    let hook_type = 'custom';

    if (/^(nobody|no one) (talks about|knows|mentions)/i.test(firstLine)) {
        template = 'Nobody [talks about/knows about] [YOUR TOPIC]...';
        hook_type = 'forbidden_knowledge';
    } else if (/^(you'?re|you are) doing .* wrong/i.test(firstLine)) {
        template = "You're doing [ACTIVITY] wrong. Here's why...";
        hook_type = 'correction';
    } else if (/^(how (to|i))/i.test(firstLine)) {
        template = 'How [to/I] [ACHIEVE RESULT] in [TIMEFRAME]';
        hook_type = 'tutorial';
    } else if (/^(pov|imagine)/i.test(firstLine)) {
        template = 'POV: [RELATABLE SITUATION]';
        hook_type = 'pov';
    } else if (/^(stop|wait|don'?t)/i.test(firstLine)) {
        template = '[STOP/WAIT/DON\'T] [do this] until you [hear/see/know] this';
        hook_type = 'scroll_stopper';
    } else if (/^(why|the reason)/i.test(firstLine)) {
        template = 'Why [COMMON BELIEF] is [wrong/a lie/killing your results]';
        hook_type = 'explanation';
    } else if (/^(\d+) (things|tips|ways)/i.test(firstLine)) {
        const num = firstLine.match(/^(\d+)/)?.[1] || '5';
        template = `${num} [things/tips/ways] to [DESIRED OUTCOME]`;
        hook_type = 'listicle';
    } else if (/\?/.test(firstLine)) {
        template = '[PROVOCATIVE QUESTION about YOUR TOPIC]?';
        hook_type = 'question';
    } else if (/^(this|these|that) /i.test(firstLine)) {
        template = 'This [THING] changed [my life/everything/the game]...';
        hook_type = 'demonstrative';
    }

    return {
        original: firstLine,
        template,
        hook_type,
        hook_type_label: {
            forbidden_knowledge: '🤫 Forbidden Knowledge',
            correction: '❌ Correction',
            tutorial: '📚 Tutorial',
            pov: '🎬 POV',
            scroll_stopper: '🛑 Scroll Stopper',
            explanation: '🧠 Explanation',
            listicle: '📋 Listicle',
            question: '❓ Question',
            demonstrative: '👆 Demonstrative',
            custom: '✍️ Custom',
        }[hook_type] || hook_type,
    };
}

// ── Extract caption template ─────────────────────────────────────────────────
function extractCaptionTemplate(caption) {
    if (!caption) return { template: '', components: [] };

    const components = [];
    const hashtags = caption.match(/#\w+/g) || [];
    const emojis = caption.match(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{2600}-\u{26FF}]/gu) || [];
    const lines = caption.split('\n').filter(l => l.trim());

    // Detect CTA
    const hasCTA = /follow|comment|share|save|link|bio|shop|dm/i.test(caption);
    const hasEmojis = emojis.length > 0;
    const lineCount = lines.length;

    // Build template
    let template = '';

    if (lineCount === 1) {
        template = '[HOOK LINE] [HASHTAGS]';
        components.push('single_line');
    } else if (lineCount === 2) {
        template = '[HOOK LINE]\n[CTA or CONTEXT] [HASHTAGS]';
        components.push('two_line');
    } else {
        template = '[HOOK LINE]\n[BODY/CONTEXT]\n[CTA] [HASHTAGS]';
        components.push('multi_line');
    }

    if (hashtags.length > 0) components.push(`${hashtags.length}_hashtags`);
    if (hasEmojis) components.push('uses_emojis');
    if (hasCTA) components.push('has_cta');

    return {
        template,
        components,
        hashtag_count: hashtags.length,
        hashtag_strategy: hashtags.length === 0 ? 'none'
            : hashtags.length <= 3 ? 'minimal'
            : hashtags.length <= 7 ? 'balanced'
            : 'heavy',
        emoji_usage: emojis.length === 0 ? 'none'
            : emojis.length <= 3 ? 'subtle'
            : 'heavy',
        has_cta: hasCTA,
        line_count: lineCount,
    };
}

// ── Sound strategy extraction ────────────────────────────────────────────────
function extractSoundStrategy(music, videoData) {
    if (!music) return { strategy: 'no_sound', recommendation: 'Add a sound — videos with sound get more reach' };

    const isOriginal = /original sound/i.test(music.title || '');

    return {
        sound_title: music.title || 'Unknown',
        sound_author: music.author || 'Unknown',
        is_original: isOriginal,
        strategy: isOriginal ? 'original_audio' : 'trending_sound',
        recommendation: isOriginal
            ? 'Original sound works for storytelling and personality-based content. For discoverability, consider pairing with a trending sound.'
            : `Using "${music.title}" — trending sounds boost discoverability. Make sure the sound fits your niche.`,
        template: isOriginal
            ? 'SOUND: Original voiceover/audio'
            : `SOUND: Use trending sound similar to "${(music.title || '').slice(0, 40)}"`,
    };
}

// ── Main ─────────────────────────────────────────────────────────────────────
async function main() {
    log('info', '🧬 Format DNA Extractor starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const { url = '', niche = '' } = params;

    if (!url) {
        emit('error', { message: 'No TikTok URL provided. Paste a viral video URL to extract its format blueprint.' });
        process.exit(1);
    }

    log('info', `🔗 Extracting format from: ${url}`);
    if (niche) log('info', `📂 Niche context: ${niche}`);
    progress(10, 'Fetching video data...');

    // ── Fetch video ──────────────────────────────────────────────────────────
    let videoData;
    try {
        const res = await get(`${BASE}/api/feed/detail?url=${encodeURIComponent(url)}&hd=1`);
        if (!res || res.code !== 0 || !res.data) {
            emit('error', { message: `Could not fetch video: ${res?.msg || 'Unknown error'}` });
            process.exit(1);
        }
        videoData = res.data;
    } catch (err) {
        emit('error', { message: `Failed to fetch video: ${err.message}` });
        process.exit(1);
    }

    progress(30, 'Extracting hook pattern...');

    const caption = videoData.title || videoData.desc || '';
    const duration = videoData.duration || 0;
    const plays  = Number(videoData.play_count || 0);
    const likes  = Number(videoData.digg_count || 0);
    const shares = Number(videoData.share_count || 0);

    // ── Extract all components ───────────────────────────────────────────────
    const hookTemplate = extractHookTemplate(caption);
    progress(45, 'Analyzing content structure...');

    const structureKey = detectStructure(caption, duration);
    const structure = STRUCTURES[structureKey];
    progress(60, 'Extracting caption format...');

    const captionTemplate = extractCaptionTemplate(caption);
    progress(75, 'Analyzing sound strategy...');

    const soundStrategy = extractSoundStrategy(
        videoData.music_info || videoData.music || null,
        videoData
    );
    progress(85, 'Building blueprint...');

    // ── Engagement snapshot ──────────────────────────────────────────────────
    const likeRate = plays > 0 ? ((likes / plays) * 100).toFixed(2) : '0';
    const shareRate = plays > 0 ? ((shares / plays) * 100).toFixed(3) : '0';

    // ── Performance tier ─────────────────────────────────────────────────────
    let performanceTier;
    if (plays > 1_000_000) performanceTier = '🔥 Mega-Viral (1M+)';
    else if (plays > 100_000) performanceTier = '📈 Viral (100K+)';
    else if (plays > 10_000) performanceTier = '👍 Strong (10K+)';
    else if (plays > 1_000) performanceTier = '📊 Average (1K+)';
    else performanceTier = '🌱 Small (<1K)';

    // ── Build the Format Blueprint ───────────────────────────────────────────
    const blueprint = {
        // Source video info
        source: {
            url,
            author: videoData.author?.unique_id || '',
            author_name: videoData.author?.nickname || '',
            cover_url: videoData.origin_cover || videoData.cover || '',
            plays,
            likes,
            performance_tier: performanceTier,
            like_rate: likeRate + '%',
            share_rate: shareRate + '%',
        },

        // The Blueprint
        blueprint: {
            // Hook
            hook: {
                type: hookTemplate.hook_type,
                type_label: hookTemplate.hook_type_label,
                original: hookTemplate.original,
                template: hookTemplate.template,
            },

            // Structure
            structure: {
                type: structureKey,
                name: structure.name,
                description: structure.description,
                template: structure.template,
            },

            // Duration
            duration: {
                seconds: duration,
                format: duration <= 15 ? 'ultra_short' : duration <= 30 ? 'short' : duration <= 60 ? 'medium' : 'long',
                recommendation: duration <= 15
                    ? `Keep at ≤15s — this format works for quick, looping content`
                    : duration <= 30
                    ? `Sweet spot: 15-30s. Long enough for value, short enough for completion`
                    : duration <= 60
                    ? `Medium length — make sure each section has its own mini-hook`
                    : `Long format — front-load value and use pattern interrupts every 10s`,
            },

            // Caption
            caption: {
                template: captionTemplate.template,
                hashtag_strategy: captionTemplate.hashtag_strategy,
                emoji_usage: captionTemplate.emoji_usage,
                has_cta: captionTemplate.has_cta,
                line_count: captionTemplate.line_count,
            },

            // Sound
            sound: soundStrategy,
        },

        // Quick-use summary
        quick_summary: {
            hook_template: hookTemplate.template,
            structure: structure.name,
            duration_target: `${Math.max(10, duration - 5)}-${duration + 5} seconds`,
            sound_strategy: soundStrategy.strategy === 'original_audio' ? 'Original audio' : 'Trending sound',
            caption_format: captionTemplate.template,
        },

        // Niche adaptation
        niche_context: niche || 'general',
    };

    progress(100, 'Blueprint extracted!');
    log('info', `✅ Format Blueprint extracted`);
    log('info', `🎯 Hook: ${hookTemplate.hook_type_label} | Structure: ${structure.name} | Duration: ${duration}s`);

    emit('result', { data: blueprint });
}

main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
