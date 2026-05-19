/**
 * TikTok Scraper Pro — Plugin Worker (Node.js) v2
 * ==============================================
 * Uses TikWM public API (tikwm.com) instead of Playwright.
 * - No browser launch, no bot detection, much faster
 * - Supports: keyword, hashtag, profile, trending
 * - Free tier: no API key required
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   {
 *     action:     "keyword" | "hashtag" | "profile" | "trending",
 *     query:      "fitness",
 *     max_count:  50,
 *     export_csv: false
 *   }
 */

'use strict';

const fs   = require('fs');
const path = require('path');
const https = require('https');

// ── IPC helpers ──────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, msg) => emit('log', { level, message: msg });
const progress = (pct, msg)   => emit('progress', { percent: pct, message: msg });

// ── HTTP helper ──────────────────────────────────────────────────────────────
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

function post(url, body) {
    return new Promise((resolve, reject) => {
        const postData = new URLSearchParams(body).toString();
        const u = new URL(url);
        const options = {
            hostname: u.hostname,
            path: u.pathname + u.search,
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Content-Length': Buffer.byteLength(postData),
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Referer': 'https://www.tikwm.com/',
            },
            timeout: 20000,
        };
        const req = https.request(options, res => {
            let data = '';
            res.on('data', c => data += c);
            res.on('end', () => {
                try { resolve(JSON.parse(data)); }
                catch (e) { reject(new Error('Invalid JSON: ' + data.slice(0, 200))); }
            });
        });
        req.on('error', reject);
        req.on('timeout', () => { req.destroy(); reject(new Error('Request timed out')); });
        req.write(postData);
        req.end();
    });
}

const BASE = 'https://www.tikwm.com';

// ── Parsers ──────────────────────────────────────────────────────────────────
function parseVideo(item) {
    if (!item) return null;
    const stats  = item.statistics || item.stats || {};
    const author = item.author || {};

    return {
        id:             item.video_id || item.id || '',
        description:    (item.title || item.desc || '').slice(0, 250),
        author:         item.author?.unique_id || item.author_id || '',
        author_name:    item.author?.nickname  || item.author_name || '',
        likes:          Number(stats.digg_count  || stats.diggCount   || item.digg_count   || 0),
        comments:       Number(stats.comment_count || stats.commentCount || item.comment_count || 0),
        shares:         Number(stats.share_count   || stats.shareCount  || item.share_count  || 0),
        plays:          Number(stats.play_count    || stats.playCount   || item.play_count   || 0),
        duration_sec:   item.duration || 0,
        cover_url:      item.origin_cover || item.cover || '',
        download_url:   item.play || '',
        hashtags:       (item.music_info?.title || ''),
        created_at:     item.create_time || 0,
        engagement_rate: (() => {
            const plays = Number(stats.play_count || stats.playCount || item.play_count || 1);
            const eng   = Number(stats.digg_count || 0) + Number(stats.comment_count || 0) + Number(stats.share_count || 0);
            return ((eng / Math.max(plays, 1)) * 100).toFixed(2) + '%';
        })(),
    };
}

// ── Scrapers ─────────────────────────────────────────────────────────────────

async function scrapeKeyword(query, maxCount) {
    log('info', `🔍 Searching keyword: "${query}"`);
    const videos = [];
    let cursor = 0;

    while (videos.length < maxCount) {
        const remaining = maxCount - videos.length;
        const count = Math.min(remaining, 30);
        progress(
            10 + Math.round((videos.length / maxCount) * 80),
            `Fetching results ${videos.length + 1}–${videos.length + count}...`
        );

        const res = await get(
            `${BASE}/api/feed/search?keywords=${encodeURIComponent(query)}&count=${count}&cursor=${cursor}&hd=1`
        );

        if (!res || res.code !== 0) {
            log('warn', `API returned code ${res?.code}: ${res?.msg || 'unknown error'}`);
            break;
        }

        const items = res.data?.videos || res.data?.items || [];
        if (items.length === 0) break;

        for (const item of items) {
            const v = parseVideo(item);
            if (v) videos.push(v);
        }

        cursor = res.data?.cursor || 0;
        if (!res.data?.hasMore && res.data?.hasMore !== undefined) break;
        if (items.length < count) break;
    }

    return videos.slice(0, maxCount);
}

async function scrapeHashtag(hashtag, maxCount) {
    hashtag = hashtag.replace(/^#/, '');
    log('info', `#️⃣ Scraping hashtag: #${hashtag}`);

    // Step 1: get challenge/hashtag ID
    progress(5, `Looking up #${hashtag}...`);
    const searchRes = await get(
        `${BASE}/api/feed/search?keywords=${encodeURIComponent('#' + hashtag)}&count=10&cursor=0`
    );

    // Fallback: try keyword search with hashtag
    if (!searchRes || searchRes.code !== 0) {
        log('warn', 'Hashtag lookup failed, falling back to keyword search');
        return scrapeKeyword(hashtag, maxCount);
    }

    const videos = [];
    const items = searchRes.data?.videos || [];
    for (const item of items) {
        const v = parseVideo(item);
        if (v) videos.push(v);
    }

    // Fetch more if needed
    if (videos.length < maxCount) {
        progress(50, 'Fetching more results...');
        const more = await get(
            `${BASE}/api/feed/search?keywords=${encodeURIComponent('#' + hashtag)}&count=${maxCount - videos.length}&cursor=${searchRes.data?.cursor || 0}`
        );
        for (const item of (more.data?.videos || [])) {
            const v = parseVideo(item);
            if (v) videos.push(v);
        }
    }

    return videos.slice(0, maxCount);
}

async function scrapeProfile(username, maxCount) {
    username = username.replace(/^@/, '');
    log('info', `👤 Fetching profile: @${username}`);

    // Step 1: get user info
    progress(5, `Looking up @${username}...`);
    const userRes = await get(`${BASE}/api/user/info?unique_id=${encodeURIComponent(username)}`);

    let profileInfo = null;
    if (userRes?.code === 0 && userRes.data) {
        const u = userRes.data;
        profileInfo = {
            username:  u.unique_id || username,
            nickname:  u.nickname,
            bio:       u.signature,
            followers: u.follower_count,
            following: u.following_count,
            likes:     u.total_favorited,
            videos:    u.aweme_count,
            verified:  u.verified,
            avatar:    u.avatar_thumb?.url_list?.[0] || '',
        };
        log('info', `Found: @${profileInfo.username} — ${profileInfo.followers?.toLocaleString() || '?'} followers`);
    }

    // Step 2: get videos
    const videos = [];
    let cursor = 0;

    while (videos.length < maxCount) {
        const remaining = maxCount - videos.length;
        progress(
            20 + Math.round((videos.length / maxCount) * 70),
            `Fetching videos ${videos.length + 1}–${videos.length + Math.min(remaining, 20)}...`
        );

        const res = await get(
            `${BASE}/api/user/posts?unique_id=${encodeURIComponent(username)}&count=${Math.min(remaining, 20)}&cursor=${cursor}`
        );

        if (!res || res.code !== 0) {
            log('warn', `User posts API: code ${res?.code}: ${res?.msg}`);
            break;
        }

        const items = res.data?.videos || [];
        if (items.length === 0) break;

        for (const item of items) {
            const v = parseVideo(item);
            if (v) videos.push(v);
        }

        cursor = res.data?.cursor || 0;
        if (!res.data?.hasMore) break;
    }

    return { profile: profileInfo, videos: videos.slice(0, maxCount) };
}

async function scrapeTrending(maxCount) {
    log('info', '🔥 Fetching trending videos');
    const videos = [];
    let cursor = 0;

    while (videos.length < maxCount) {
        progress(
            10 + Math.round((videos.length / maxCount) * 80),
            `Fetching trending ${videos.length + 1}–${Math.min(videos.length + 20, maxCount)}...`
        );

        const res = await get(`${BASE}/api/recommend/feed?count=20&cursor=${cursor}`);

        if (!res || res.code !== 0) {
            log('warn', `Trending API: code ${res?.code}`);
            break;
        }

        const items = res.data?.videos || res.data?.items || [];
        if (items.length === 0) break;

        for (const item of items) {
            const v = parseVideo(item);
            if (v) videos.push(v);
        }

        cursor = res.data?.cursor || 0;
        if (items.length < 20) break;
    }

    return videos.slice(0, maxCount);
}

// ── CSV Export ───────────────────────────────────────────────────────────────
function exportCSV(videos, fileName) {
    const exportDir = path.join(__dirname, 'exports');
    if (!fs.existsSync(exportDir)) fs.mkdirSync(exportDir, { recursive: true });

    const filePath = path.join(exportDir, fileName);
    const headers  = Object.keys(videos[0] || {});
    const rows     = videos.map(v => headers.map(h => `"${String(v[h] || '').replace(/"/g, '""')}"`).join(','));

    fs.writeFileSync(filePath, [headers.join(','), ...rows].join('\n'), 'utf8');
    return filePath;
}

// ── Main ─────────────────────────────────────────────────────────────────────
async function main() {
    log('info', '🎵 TikTok Scraper Pro starting...');

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const {
        action     = 'keyword',
        query      = '',
        max_count  = 30,
        export_csv = false,
    } = params;

    if (!query && action !== 'trending') {
        emit('error', { message: 'No query provided. Enter a keyword, hashtag, or username.' });
        process.exit(1);
    }

    log('info', `Action=${action} | Query="${query}" | Max=${max_count}`);
    progress(5, 'Connecting to TikTok data API...');

    let videos = [];
    let profileInfo = null;

    try {
        switch (action) {
            case 'keyword':
                videos = await scrapeKeyword(query, max_count);
                break;
            case 'hashtag':
                videos = await scrapeHashtag(query, max_count);
                break;
            case 'profile': {
                const result = await scrapeProfile(query, max_count);
                videos = Array.isArray(result) ? result : (result.videos || []);
                profileInfo = Array.isArray(result) ? null : result.profile;
                break;
            }
            case 'trending':
                videos = await scrapeTrending(max_count);
                break;
            default:
                emit('error', { message: `Unknown action: ${action}` });
                process.exit(1);
        }
    } catch (err) {
        emit('error', { message: `Scraper error: ${err.message}` });
        process.exit(1);
    }

    // ── CSV export ────────────────────────────────────────────────────────────
    let csvPath = null;
    if (export_csv && videos.length > 0) {
        csvPath = exportCSV(videos, `tiktok-${action}-${query || 'trending'}-${Date.now()}.csv`);
        log('info', `📁 CSV exported: ${csvPath}`);
    }

    progress(100, `Done — ${videos.length} videos`);
    log('info', `✅ Scraped ${videos.length} videos`);

    if (videos.length === 0) {
        log('warn', 'No results found. TikWM API may be rate-limiting. Try a different query or wait a moment.');
    }

    emit('result', { data: { action, query, count: videos.length, videos, profile: profileInfo, csvPath } });
}

main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
