/**
 * Hook Analyzer — Plugin Worker (Node.js)
 * ========================================
 * Analyze TikTok video hooks — score the caption, opening strategy,
 * and predict retention based on measurable signals.
 *
 * Supports two modes:
 *   1. Single URL analysis (detailed report)
 *   2. Batch analysis (compare multiple videos' hooks)
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   { mode: "single"|"batch", urls: ["..."], url: "..." }
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

// ── Hook Pattern Database ────────────────────────────────────────────────────
const HOOK_PATTERNS = [
    { pattern: /^(wait|hold on|stop|don'?t scroll)/i, type: 'scroll_stopper', label: '🛑 Scroll Stopper', power: 9 },
    { pattern: /^(nobody|no one|they don'?t want|the truth|secret)/i, type: 'forbidden_knowledge', label: '🤫 Forbidden Knowledge', power: 9 },
    { pattern: /^(you'?re doing .* wrong|stop doing|you need to stop)/i, type: 'correction', label: '❌ Correction Hook', power: 8 },
    { pattern: /^(pov|imagine|picture this|what if|when you)/i, type: 'pov_immersion', label: '🎬 POV Immersion', power: 8 },
    { pattern: /^(how (to|i)|step|here'?s how|\d+ (ways|tips|hacks|things))/i, type: 'value_promise', label: '💡 Value Promise', power: 7 },
    { pattern: /^(why|the reason|this is why|here'?s why)/i, type: 'explanation', label: '🧠 Explanation Hook', power: 7 },
    { pattern: /^(I (just|finally|never|can'?t)|my|when I)/i, type: 'personal_story', label: '📖 Personal Story', power: 7 },
    { pattern: /^(this|these|that|the best|the worst)/i, type: 'demonstrative', label: '👆 Demonstrative', power: 5 },
    { pattern: /^(hey|hi|so|ok so|guys)/i, type: 'casual_opener', label: '👋 Casual Opener', power: 3 },
    { pattern: /\?$/, type: 'question', label: '❓ Question', power: 6 },
];

const WEAK_OPENERS = [
    /^(hey guys|hi everyone|hello|what'?s up|good morning)/i,
    /^(so basically|um|uh|ok so like)/i,
    /^(in this video|today I|I want to)/i,
];

// ── Analyze a single video's hook ────────────────────────────────────────────
function analyzeHook(video) {
    const caption = (video.title || video.desc || '').trim();
    const firstLine = caption.split('\n')[0] || caption.slice(0, 100);
    const words = firstLine.split(/\s+/);
    const duration = video.duration || 0;

    const plays    = Number(video.play_count || 0);
    const likes    = Number(video.digg_count || 0);
    const comments = Number(video.comment_count || 0);
    const shares   = Number(video.share_count || 0);

    // ── Hook Pattern Matching ────────────────────────────────────────────────
    let matchedPattern = null;
    for (const hp of HOOK_PATTERNS) {
        if (hp.pattern.test(firstLine)) {
            matchedPattern = hp;
            break;
        }
    }

    // ── Weak opener detection ────────────────────────────────────────────────
    let isWeakOpener = false;
    for (const wp of WEAK_OPENERS) {
        if (wp.test(firstLine)) {
            isWeakOpener = true;
            break;
        }
    }

    // ── Word power analysis ──────────────────────────────────────────────────
    const powerWords = {
        high: ['secret','free','new','proven','shocking','instant','exclusive','limited','hidden','truth','hack','trick','mistake','warning','urgent'],
        medium: ['best','top','simple','easy','fast','amazing','perfect','ultimate','essential','powerful'],
        low: ['nice','good','cool','great','interesting','okay'],
    };

    let wordPowerScore = 0;
    const firstThreeWords = words.slice(0, 3).join(' ').toLowerCase();
    for (const w of words.slice(0, 5)) {
        const lower = w.toLowerCase().replace(/[^a-z]/g, '');
        if (powerWords.high.includes(lower)) wordPowerScore += 3;
        else if (powerWords.medium.includes(lower)) wordPowerScore += 2;
        else if (powerWords.low.includes(lower)) wordPowerScore += 1;
    }

    // ── Engagement-based retention proxy ─────────────────────────────────────
    const likeRate = plays > 0 ? (likes / plays) * 100 : 0;
    const commentRate = plays > 0 ? (comments / plays) * 100 : 0;
    const shareRate = plays > 0 ? (shares / plays) * 100 : 0;

    // Higher like rate with short videos = good hook (people watched to end and liked)
    let retentionEstimate = 'unknown';
    if (plays > 1000) {
        if (likeRate > 8 && duration <= 30)       retentionEstimate = 'excellent';
        else if (likeRate > 5)                    retentionEstimate = 'good';
        else if (likeRate > 3)                    retentionEstimate = 'average';
        else                                      retentionEstimate = 'poor';
    }

    // ── Hook Score Calculation (0-100) ───────────────────────────────────────
    let hookScore = 20; // base

    // Pattern strength (0-25)
    if (matchedPattern) hookScore += Math.min(matchedPattern.power * 3, 25);
    else hookScore += 5;

    // Word power (0-15)
    hookScore += Math.min(wordPowerScore * 3, 15);

    // Engagement signal (0-20)
    if (retentionEstimate === 'excellent') hookScore += 20;
    else if (retentionEstimate === 'good') hookScore += 14;
    else if (retentionEstimate === 'average') hookScore += 8;
    else if (retentionEstimate === 'poor') hookScore += 2;

    // Conciseness bonus (0-10)
    if (words.length >= 3 && words.length <= 8) hookScore += 10;
    else if (words.length <= 12) hookScore += 5;

    // Penalties
    if (isWeakOpener) hookScore -= 15;

    // Duration-hook alignment (0-10)
    if (duration <= 15) hookScore += 5; // short videos survive weak hooks better
    if (duration > 60 && (!matchedPattern || matchedPattern.power < 7)) hookScore -= 5;

    hookScore = Math.max(0, Math.min(100, hookScore));

    // ── Grade ────────────────────────────────────────────────────────────────
    let grade, gradeEmoji;
    if (hookScore >= 85) { grade = 'S'; gradeEmoji = '🏆'; }
    else if (hookScore >= 70) { grade = 'A'; gradeEmoji = '🔥'; }
    else if (hookScore >= 55) { grade = 'B'; gradeEmoji = '👍'; }
    else if (hookScore >= 40) { grade = 'C'; gradeEmoji = '😐'; }
    else if (hookScore >= 25) { grade = 'D'; gradeEmoji = '👎'; }
    else { grade = 'F'; gradeEmoji = '💀'; }

    // ── Specific suggestions ─────────────────────────────────────────────────
    const suggestions = [];
    if (isWeakOpener) {
        suggestions.push({ priority: 'high', text: `"${firstLine.slice(0, 30)}..." is a weak opener. Replace with a curiosity gap or bold claim.` });
    }
    if (!matchedPattern) {
        suggestions.push({ priority: 'high', text: 'No hook pattern detected. Start with "Nobody talks about..." or "You\'re doing X wrong" for stronger retention.' });
    }
    if (wordPowerScore === 0) {
        suggestions.push({ priority: 'medium', text: 'No power words in first 5 words. Add urgency or exclusivity words.' });
    }
    if (words.length > 15) {
        suggestions.push({ priority: 'medium', text: 'First line is too long. Keep it under 10 words for maximum impact.' });
    }
    if (duration > 30 && hookScore < 60) {
        suggestions.push({ priority: 'high', text: 'Long video + weak hook = high skip rate. The first 2 seconds must be irresistible.' });
    }

    // ── Alternative hooks ────────────────────────────────────────────────────
    const alternativeHooks = [];
    const topic = caption.slice(0, 50);
    alternativeHooks.push(`"Nobody talks about this..." (then reveal your topic)`);
    alternativeHooks.push(`"Stop doing [common mistake]. Here's why..."`);
    alternativeHooks.push(`"I tested [thing] for 30 days. Results shocked me."`);
    if (matchedPattern?.type !== 'question') {
        alternativeHooks.push(`"Why does nobody know about [your topic]?"`);
    }

    return {
        hook_score: hookScore,
        grade,
        grade_emoji: gradeEmoji,
        hook_pattern: matchedPattern ? {
            type: matchedPattern.type,
            label: matchedPattern.label,
            power: matchedPattern.power,
        } : { type: 'none', label: '⚠️ No Pattern', power: 0 },
        first_line: firstLine,
        word_count: words.length,
        is_weak_opener: isWeakOpener,
        word_power_score: wordPowerScore,
        retention_estimate: retentionEstimate,
        engagement: {
            plays, likes, comments, shares,
            like_rate: likeRate.toFixed(2) + '%',
            comment_rate: commentRate.toFixed(3) + '%',
            share_rate: shareRate.toFixed(3) + '%',
        },
        video_info: {
            id: video.video_id || video.id || '',
            author: video.author?.unique_id || '',
            author_name: video.author?.nickname || '',
            cover_url: video.origin_cover || video.cover || '',
            duration: duration,
        },
        suggestions,
        alternative_hooks: alternativeHooks,
    };
}

// ── Main ─────────────────────────────────────────────────────────────────────
async function main() {
    log('info', '🎯 Hook Analyzer starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const { mode = 'single', url = '', urls = [] } = params;

    const targetUrls = mode === 'batch' ? urls : (url ? [url] : []);

    if (targetUrls.length === 0) {
        emit('error', { message: 'No TikTok URL(s) provided. Paste a video URL to analyze its hook.' });
        process.exit(1);
    }

    log('info', `Mode: ${mode} | URLs: ${targetUrls.length}`);

    const results = [];
    let processed = 0;

    for (const videoUrl of targetUrls) {
        progress(
            10 + Math.round((processed / targetUrls.length) * 80),
            `Analyzing ${processed + 1}/${targetUrls.length}...`
        );

        try {
            const res = await get(`${BASE}/api/feed/detail?url=${encodeURIComponent(videoUrl)}&hd=1`);

            if (!res || res.code !== 0 || !res.data) {
                log('warn', `Could not fetch: ${videoUrl} — ${res?.msg || 'unknown error'}`);
                results.push({ url: videoUrl, error: res?.msg || 'Video not found' });
                processed++;
                continue;
            }

            const analysis = analyzeHook(res.data);
            analysis.url = videoUrl;
            results.push(analysis);

            log('info', `${analysis.grade_emoji} ${analysis.hook_pattern.label} — Score: ${analysis.hook_score}/100 (Grade ${analysis.grade})`);
        } catch (err) {
            log('warn', `Error analyzing ${videoUrl}: ${err.message}`);
            results.push({ url: videoUrl, error: err.message });
        }

        processed++;
    }

    // ── Summary stats ────────────────────────────────────────────────────────
    const scored = results.filter(r => r.hook_score != null);
    const avgScore = scored.length > 0
        ? Math.round(scored.reduce((s, r) => s + r.hook_score, 0) / scored.length)
        : 0;

    const bestHook = scored.length > 0
        ? scored.reduce((a, b) => a.hook_score > b.hook_score ? a : b)
        : null;

    const worstHook = scored.length > 0
        ? scored.reduce((a, b) => a.hook_score < b.hook_score ? a : b)
        : null;

    progress(100, `Done — Avg Hook Score: ${avgScore}/100`);
    log('info', `✅ Analyzed ${results.length} video(s) — Average Hook Score: ${avgScore}/100`);

    emit('result', {
        data: {
            mode,
            count: results.length,
            average_score: avgScore,
            best_hook: bestHook ? { url: bestHook.url, score: bestHook.hook_score, pattern: bestHook.hook_pattern?.label } : null,
            worst_hook: worstHook ? { url: worstHook.url, score: worstHook.hook_score, pattern: worstHook.hook_pattern?.label } : null,
            analyses: results,
        }
    });
}

main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
