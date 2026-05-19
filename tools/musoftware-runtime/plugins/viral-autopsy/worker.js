/**
 * Viral Autopsy — Plugin Worker (Node.js)
 * ========================================
 * Paste any TikTok URL → get a full breakdown of WHY it went viral.
 *
 * Uses TikWM API to fetch video data, then runs multi-dimensional analysis:
 *   - Engagement metrics vs. niche benchmarks
 *   - Caption psychology (hook type, CTA, emotional triggers)
 *   - Sound velocity & timing strategy
 *   - Posting timing analysis
 *   - Viral score (0-100) with per-dimension breakdown
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   { url: "https://tiktok.com/@user/video/123..." }
 */

'use strict';

const https = require('https');

// ── IPC helpers ──────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, msg) => emit('log', { level, message: msg });
const progress = (pct, msg)   => emit('progress', { percent: pct, message: msg });

// ── HTTP helpers ─────────────────────────────────────────────────────────────
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

// ── Extract video ID from URL ────────────────────────────────────────────────
function extractVideoId(url) {
    // Matches: /video/1234, /v/1234, vm.tiktok.com/ABC
    const patterns = [
        /video\/(\d+)/,
        /\/v\/(\d+)/,
        /\/@[\w.]+\/video\/(\d+)/,
    ];
    for (const p of patterns) {
        const m = url.match(p);
        if (m) return m[1];
    }
    return null;
}

// ── Caption Analysis ─────────────────────────────────────────────────────────
function analyzeCaption(text) {
    if (!text) return { score: 0, hook_type: 'none', triggers: [], suggestions: [] };

    const lower = text.toLowerCase();
    const words = text.split(/\s+/).length;
    const firstLine = text.split('\n')[0] || text.slice(0, 80);

    // Hook type detection
    let hook_type = 'statement';
    if (/\?/.test(firstLine)) hook_type = 'question';
    if (/^(wait|stop|don'?t|never|nobody|you won'?t|this is why|here'?s|the secret)/i.test(firstLine)) hook_type = 'curiosity_gap';
    if (/^(pov|imagine|what if|when you)/i.test(firstLine)) hook_type = 'pov_story';
    if (/^(how to|how i|step|tip|hack|trick)/i.test(firstLine)) hook_type = 'tutorial';
    if (/^(\d|top \d|best \d|\d things)/i.test(firstLine)) hook_type = 'listicle';
    if (/not|wrong|lie|myth|fake|scam|truth|secret|hidden/i.test(firstLine)) hook_type = 'controversy';

    // Emotional triggers
    const triggers = [];
    if (/\b(shocking|insane|crazy|unbelievable|mind.?blow)/i.test(text)) triggers.push('shock');
    if (/\b(secret|hidden|nobody|they don|don.?t want you)/i.test(text)) triggers.push('exclusivity');
    if (/\b(hurry|limited|last chance|before it|now|today only)/i.test(text)) triggers.push('urgency');
    if (/\b(free|save|discount|deal|cheap)/i.test(text)) triggers.push('value');
    if (/\b(you need|must|have to|should|stop)/i.test(text)) triggers.push('authority');
    if (/\b(funny|lol|😂|🤣|dead|crying)/i.test(text)) triggers.push('humor');
    if (/\b(story|journey|struggle|dream|finally)/i.test(text)) triggers.push('narrative');

    // CTA detection
    const cta = [];
    if (/follow/i.test(text)) cta.push('follow');
    if (/comment|let me know|thoughts/i.test(text)) cta.push('comment');
    if (/share|send|tag/i.test(text)) cta.push('share');
    if (/link|bio|shop|buy|order/i.test(text)) cta.push('commerce');
    if (/save|bookmark/i.test(text)) cta.push('save');
    if (/like/i.test(text)) cta.push('like');

    // Hashtag analysis
    const hashtags = (text.match(/#\w+/g) || []);
    const emojiCount = (text.match(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F1E0}-\u{1F1FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]/gu) || []).length;

    // Score calculation (0-30 points for caption)
    let score = 5; // base
    if (hook_type === 'curiosity_gap' || hook_type === 'controversy') score += 8;
    else if (hook_type === 'question') score += 5;
    else if (hook_type === 'pov_story') score += 6;
    else if (hook_type === 'listicle') score += 4;
    else if (hook_type === 'tutorial') score += 4;
    if (triggers.length >= 2) score += 5;
    else if (triggers.length === 1) score += 3;
    if (cta.length > 0) score += 4;
    if (hashtags.length >= 3 && hashtags.length <= 7) score += 3;
    if (emojiCount >= 1 && emojiCount <= 5) score += 2;
    if (words <= 30) score += 2; // concise
    score = Math.min(score, 30);

    // Suggestions
    const suggestions = [];
    if (hook_type === 'statement') suggestions.push('Start with a question or curiosity gap instead of a plain statement');
    if (triggers.length === 0) suggestions.push('Add an emotional trigger word (e.g., "secret", "nobody talks about...")');
    if (cta.length === 0) suggestions.push('Add a CTA — ask viewers to comment, follow, or share');
    if (hashtags.length === 0) suggestions.push('Add 3-5 relevant hashtags for discoverability');
    if (hashtags.length > 10) suggestions.push('Too many hashtags — reduce to 5-7 targeted ones');
    if (words > 50) suggestions.push('Caption is long — keep it under 30 words for higher completion');

    return {
        score,
        hook_type,
        hook_type_label: {
            question: '❓ Question Hook',
            curiosity_gap: '🧲 Curiosity Gap',
            pov_story: '🎬 POV / Story',
            tutorial: '📚 Tutorial / How-To',
            listicle: '📋 Listicle',
            controversy: '🔥 Controversy / Hot Take',
            statement: '📝 Plain Statement',
            none: '⚠️ No Hook Detected',
        }[hook_type] || hook_type,
        triggers,
        cta,
        hashtag_count: hashtags.length,
        emoji_count: emojiCount,
        word_count: words,
        first_line: firstLine,
        suggestions,
    };
}

// ── Engagement Analysis ──────────────────────────────────────────────────────
function analyzeEngagement(video) {
    const plays    = Number(video.play_count   || video.plays    || 1);
    const likes    = Number(video.digg_count   || video.likes    || 0);
    const comments = Number(video.comment_count || video.comments || 0);
    const shares   = Number(video.share_count  || video.shares   || 0);
    const saves    = Number(video.collect_count || video.saves    || 0);

    const likeRate    = (likes / Math.max(plays, 1)) * 100;
    const commentRate = (comments / Math.max(plays, 1)) * 100;
    const shareRate   = (shares / Math.max(plays, 1)) * 100;
    const saveRate    = (saves / Math.max(plays, 1)) * 100;
    const totalEngRate = ((likes + comments + shares + saves) / Math.max(plays, 1)) * 100;

    // TikTok benchmarks (approximate)
    // Average: like 4-5%, comment 0.1%, share 0.3%, save 0.5%
    // Viral:   like >8%, comment >0.5%, share >1%, save >1%

    let score = 5; // base
    if (likeRate > 10)    score += 7; else if (likeRate > 6) score += 5; else if (likeRate > 3) score += 3;
    if (commentRate > 1)  score += 5; else if (commentRate > 0.3) score += 3; else if (commentRate > 0.1) score += 1;
    if (shareRate > 2)    score += 5; else if (shareRate > 0.5) score += 3; else if (shareRate > 0.2) score += 1;
    if (saveRate > 2)     score += 3; else if (saveRate > 0.5) score += 2;
    score = Math.min(score, 25);

    // Virality signals
    const signals = [];
    if (likeRate > 8)      signals.push('🔥 Exceptionally high like rate');
    if (commentRate > 0.5) signals.push('💬 Strong comment engagement — triggers debate/discussion');
    if (shareRate > 1)     signals.push('🔁 High share rate — content people want others to see');
    if (saveRate > 1)      signals.push('🔖 High save rate — educational/reference value');
    if (commentRate > likeRate * 0.1) signals.push('🗣️ Comment-to-like ratio suggests controversy or strong opinion');
    if (plays > 1_000_000) signals.push('📈 Mega-viral: 1M+ plays');
    else if (plays > 100_000) signals.push('📈 Viral: 100K+ plays');

    const weaknesses = [];
    if (likeRate < 3)      weaknesses.push('Like rate below average — weak initial hook');
    if (commentRate < 0.05) weaknesses.push('Very low comments — no conversation driver');
    if (shareRate < 0.1)   weaknesses.push('Low share rate — content not share-worthy');

    return {
        score,
        plays, likes, comments, shares, saves,
        like_rate:     likeRate.toFixed(2) + '%',
        comment_rate:  commentRate.toFixed(3) + '%',
        share_rate:    shareRate.toFixed(3) + '%',
        save_rate:     saveRate.toFixed(3) + '%',
        total_eng_rate: totalEngRate.toFixed(2) + '%',
        signals,
        weaknesses,
        benchmark_comparison: {
            likes:    likeRate > 5 ? 'above_avg' : likeRate > 3 ? 'average' : 'below_avg',
            comments: commentRate > 0.3 ? 'above_avg' : commentRate > 0.1 ? 'average' : 'below_avg',
            shares:   shareRate > 0.5 ? 'above_avg' : shareRate > 0.2 ? 'average' : 'below_avg',
        },
    };
}

// ── Sound Analysis ───────────────────────────────────────────────────────────
function analyzeSound(music) {
    if (!music) return { score: 0, analysis: 'No sound data available', suggestions: [] };

    const title  = music.title || '';
    const author = music.author || '';

    let score = 5;
    const analysis = [];
    const suggestions = [];

    // Original sound detection
    const isOriginal = /original sound/i.test(title) || author.toLowerCase().includes('original');

    if (isOriginal) {
        analysis.push('🎤 Original sound — creator voice/audio');
        score += 3;
    } else {
        analysis.push(`🎵 Sound: "${title}" by ${author}`);
        score += 5; // Using a real sound is generally better for discovery
    }

    if (music.duration && music.duration < 15) {
        analysis.push('⚡ Short sound clip — optimized for quick loops');
        score += 2;
    }

    score = Math.min(score, 15);

    if (isOriginal) {
        suggestions.push('Original sounds limit discoverability — consider adding a trending sound');
    }

    return {
        score,
        title: title || 'Unknown',
        author: author || 'Unknown',
        is_original: isOriginal,
        duration: music.duration || 0,
        analysis,
        suggestions,
    };
}

// ── Content Structure Analysis ───────────────────────────────────────────────
function analyzeStructure(video) {
    const duration = video.duration || 0;
    let score = 5;
    const analysis = [];
    const suggestions = [];

    // Duration analysis
    if (duration > 0 && duration <= 15) {
        analysis.push('⚡ Ultra-short format (≤15s) — optimized for full watches');
        score += 5;
    } else if (duration <= 30) {
        analysis.push('🎯 Short format (15-30s) — sweet spot for engagement');
        score += 5;
    } else if (duration <= 60) {
        analysis.push('📹 Medium format (30-60s) — good for storytelling');
        score += 3;
    } else {
        analysis.push('📺 Long format (60s+) — requires strong hook to retain');
        score += 1;
        suggestions.push('Long videos need an exceptionally strong hook in first 2 seconds');
    }

    // Replay value estimation
    const plays = Number(video.play_count || video.plays || 0);
    const likes = Number(video.digg_count || video.likes || 0);
    if (plays > 0 && likes > 0) {
        const likeRate = likes / plays;
        if (likeRate > 0.1 && duration <= 15) {
            analysis.push('🔄 High replay probability — short + high engagement');
            score += 3;
        }
    }

    score = Math.min(score, 15);

    return {
        score,
        duration_seconds: duration,
        duration_label: duration <= 15 ? 'ultra_short' : duration <= 30 ? 'short' : duration <= 60 ? 'medium' : 'long',
        analysis,
        suggestions,
    };
}

// ── Timing Analysis ──────────────────────────────────────────────────────────
function analyzeTiming(video) {
    const ts = video.create_time || video.createTime || 0;
    if (!ts) return { score: 5, analysis: ['⚠️ Posting time unknown'], suggestions: [] };

    const date = new Date(ts * 1000);
    const hour = date.getUTCHours();
    const day  = date.getUTCDay(); // 0=Sun

    let score = 5;
    const analysis = [];
    const suggestions = [];

    // Peak posting hours (UTC rough equivalents for US prime time)
    const isPeakHour = (hour >= 11 && hour <= 14) || (hour >= 18 && hour <= 22);
    const isWeekday  = day >= 1 && day <= 5;

    if (isPeakHour) {
        analysis.push('⏰ Posted during peak engagement hours');
        score += 5;
    } else {
        analysis.push('⏰ Posted during off-peak hours');
        suggestions.push('Try posting between 11AM-2PM or 6PM-10PM (audience timezone)');
    }

    const dayName = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][day];
    analysis.push(`📅 Posted on ${dayName} at ${hour}:00 UTC`);

    if ((day === 2 || day === 4) && isPeakHour) { // Tue/Thu
        analysis.push('🎯 Tuesday/Thursday peak — historically high engagement days');
        score += 3;
    }

    score = Math.min(score, 15);

    return {
        score,
        posted_at: date.toISOString(),
        day: dayName,
        hour_utc: hour,
        is_peak: isPeakHour,
        analysis,
        suggestions,
    };
}

// ── Main ─────────────────────────────────────────────────────────────────────
async function main() {
    log('info', '🔬 Viral Autopsy starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const { url = '' } = params;

    if (!url) {
        emit('error', { message: 'No TikTok URL provided. Paste a video URL to analyze.' });
        process.exit(1);
    }

    log('info', `🔗 Analyzing: ${url}`);
    progress(10, 'Extracting video data...');

    // ── Step 1: Fetch video data ─────────────────────────────────────────────
    let videoData;
    try {
        const videoId = extractVideoId(url);
        let apiUrl;

        if (videoId) {
            apiUrl = `${BASE}/api/feed/detail?url=${encodeURIComponent(url)}&hd=1`;
        } else {
            // Try with full URL for short links (vm.tiktok.com)
            apiUrl = `${BASE}/api/feed/detail?url=${encodeURIComponent(url)}&hd=1`;
        }

        const res = await get(apiUrl);

        if (!res || res.code !== 0 || !res.data) {
            emit('error', { message: `Could not fetch video data. API response: ${res?.msg || 'Unknown error'}. Make sure the URL is a valid TikTok video link.` });
            process.exit(1);
        }

        videoData = res.data;
    } catch (err) {
        emit('error', { message: `Failed to fetch video: ${err.message}` });
        process.exit(1);
    }

    progress(30, 'Analyzing caption & hook...');

    // ── Step 2: Run all analyses ─────────────────────────────────────────────
    const caption    = videoData.title || videoData.desc || '';
    const author     = videoData.author || {};

    const captionAnalysis   = analyzeCaption(caption);
    progress(45, 'Analyzing engagement metrics...');

    const engagementAnalysis = analyzeEngagement(videoData);
    progress(60, 'Analyzing sound strategy...');

    const soundAnalysis      = analyzeSound(videoData.music_info || videoData.music || null);
    progress(75, 'Analyzing content structure...');

    const structureAnalysis  = analyzeStructure(videoData);
    progress(85, 'Analyzing posting timing...');

    const timingAnalysis     = analyzeTiming(videoData);
    progress(90, 'Calculating viral score...');

    // ── Step 3: Calculate Viral Score ─────────────────────────────────────────
    // Total: 100 points across 5 dimensions
    // Caption: 30, Engagement: 25, Sound: 15, Structure: 15, Timing: 15
    const viralScore = Math.min(100,
        captionAnalysis.score +
        engagementAnalysis.score +
        soundAnalysis.score +
        structureAnalysis.score +
        timingAnalysis.score
    );

    // Viral verdict
    let verdict, verdict_emoji;
    if (viralScore >= 80) { verdict = 'VIRAL MASTERPIECE'; verdict_emoji = '🏆'; }
    else if (viralScore >= 65) { verdict = 'STRONG PERFORMER'; verdict_emoji = '🔥'; }
    else if (viralScore >= 50) { verdict = 'ABOVE AVERAGE'; verdict_emoji = '📈'; }
    else if (viralScore >= 35) { verdict = 'AVERAGE'; verdict_emoji = '📊'; }
    else { verdict = 'NEEDS WORK'; verdict_emoji = '⚠️'; }

    // Collect all suggestions
    const allSuggestions = [
        ...captionAnalysis.suggestions,
        ...engagementAnalysis.weaknesses,
        ...soundAnalysis.suggestions,
        ...structureAnalysis.suggestions,
        ...timingAnalysis.suggestions,
    ];

    // ── Step 4: Build result ─────────────────────────────────────────────────
    const result = {
        // Video info
        video: {
            id: videoData.video_id || videoData.id || '',
            url: url,
            caption: caption.slice(0, 300),
            author: author.unique_id || videoData.author_id || '',
            author_name: author.nickname || '',
            author_avatar: author.avatar || '',
            cover_url: videoData.origin_cover || videoData.cover || '',
            duration: videoData.duration || 0,
            created_at: videoData.create_time || 0,
        },

        // Viral score
        viral_score: {
            total: viralScore,
            verdict,
            verdict_emoji,
            breakdown: {
                caption:    { score: captionAnalysis.score,    max: 30, label: 'Caption & Hook' },
                engagement: { score: engagementAnalysis.score, max: 25, label: 'Engagement' },
                sound:      { score: soundAnalysis.score,      max: 15, label: 'Sound Strategy' },
                structure:  { score: structureAnalysis.score,  max: 15, label: 'Content Structure' },
                timing:     { score: timingAnalysis.score,     max: 15, label: 'Posting Timing' },
            },
        },

        // Detailed analyses
        caption: captionAnalysis,
        engagement: engagementAnalysis,
        sound: soundAnalysis,
        structure: structureAnalysis,
        timing: timingAnalysis,

        // Action items
        suggestions: allSuggestions,
        suggestion_count: allSuggestions.length,
    };

    progress(100, `Done — Viral Score: ${viralScore}/100`);
    log('info', `${verdict_emoji} Viral Score: ${viralScore}/100 — ${verdict}`);
    log('info', `📊 ${engagementAnalysis.plays.toLocaleString()} plays | ${engagementAnalysis.total_eng_rate} engagement`);

    emit('result', { data: result });
}

main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
