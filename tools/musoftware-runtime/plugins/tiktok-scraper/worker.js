/**
 * TikTok Scraper Pro — Plugin Worker (Node.js)
 * ==============================================
 * Uses Playwright to intercept TikTok's internal JSON API.
 * No API key required — works by loading TikTok in a headless browser
 * and capturing XHR responses with structured video/user data.
 *
 * Params (MUSOFTWARE_PARAMS env):
 *   {
 *     action:      "keyword" | "hashtag" | "profile" | "trending",
 *     query:       "fitness",
 *     max_count:   50,
 *     proxy_url:   "http://user:pass@proxy:port",
 *     headless:    true,
 *     export_csv:  false
 *   }
 */

'use strict';

const fs   = require('fs');
const path = require('path');

// ── IPC helpers ──────────────────────────────────────────────────────────────
const emit     = (type, data) => process.stdout.write(JSON.stringify({ type, ...data }) + '\n');
const log      = (level, msg) => emit('log', { level, message: msg });
const progress = (pct, msg)   => emit('progress', { percent: pct, message: msg });

// ── Main ─────────────────────────────────────────────────────────────────────
async function main() {
    log('info', '🎵 TikTok Scraper starting...');

    let playwright;
    try {
        playwright = require('playwright');
    } catch {
        emit('error', { message: 'playwright not installed. Run: npm install playwright' });
        process.exit(1);
    }

    const params = JSON.parse(process.env.MUSOFTWARE_PARAMS || '{}');
    const {
        action    = 'keyword',
        query     = '',
        max_count = 30,
        proxy_url = '',
        headless  = true,
        export_csv = false,
    } = params;

    if (!query && action !== 'trending') {
        emit('error', { message: 'No query provided' });
        process.exit(1);
    }

    log('info', `Action=${action} | Query="${query}" | Max=${max_count} | Headless=${headless}`);

    // ── Heartbeat — emits "still working" progress every 5s so UI doesn't freeze
    let heartbeatTick = 0;
    const HEARTBEAT_MSGS = [
        'Launching browser...', 'Waiting for TikTok...', 'Loading content...',
        'Processing data...', 'Scrolling feed...', 'Almost there...',
    ];
    const heartbeat = setInterval(() => {
        heartbeatTick++;
        const msg = HEARTBEAT_MSGS[heartbeatTick % HEARTBEAT_MSGS.length];
        progress(Math.min(heartbeatTick * 3, 15), msg);
    }, 5000);

    const clearHeartbeat = () => clearInterval(heartbeat);

    progress(5, 'Launching browser...');
    const launchOpts = { headless };
    if (proxy_url) launchOpts.proxy = { server: proxy_url };

    const browser = await playwright.chromium.launch(launchOpts);
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        viewport: { width: 1280, height: 720 },
        locale: 'en-US',
    });

    try {
        let videos = [];

        switch (action) {
            case 'keyword':
                videos = await scrapeKeyword(context, query, max_count);
                break;
            case 'hashtag':
                videos = await scrapeHashtag(context, query, max_count);
                break;
            case 'profile':
                videos = await scrapeProfile(context, query, max_count);
                break;
            case 'trending':
                videos = await scrapeTrending(context, max_count);
                break;
            default:
                emit('error', { message: `Unknown action: ${action}` });
                process.exit(1);
        }

        // ── CSV export ───────────────────────────────────────────────────────
        let csvPath = null;
        if (export_csv && videos.length > 0) {
            csvPath = exportCSV(videos, `tiktok-${action}-${Date.now()}.csv`);
            log('info', `CSV exported: ${csvPath}`);
        }

        clearHeartbeat();
        progress(100, 'Done');
        log('info', `Scraped ${videos.length} videos`);
        emit('result', { data: { action, query, count: videos.length, videos, csvPath } });

    } finally {
        await browser.close();
    }
}

// ── Scraping functions ───────────────────────────────────────────────────────

async function scrapeKeyword(context, keyword, maxCount) {
    progress(10, `Searching: "${keyword}"...`);
    const page = await context.newPage();
    const videos = [];

    const captured = [];
    page.on('response', async response => {
        if (response.url().includes('/api/search/item/full')) {
            try { captured.push(await response.json()); } catch {}
        }
    });

    const url = `https://www.tiktok.com/search/video?q=${encodeURIComponent(keyword)}`;
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(3000);

    // Scroll for more results
    for (let round = 0; round < Math.min(maxCount / 10, 6); round++) {
        await page.evaluate(() => window.scrollBy(0, window.innerHeight * 3));
        await page.waitForTimeout(1500);
        progress(10 + Math.round(round / 6 * 80), `Loading results...`);
    }

    for (const data of captured) {
        for (const item of (data.item_list || data.itemList || [])) {
            videos.push(parseVideo(item));
            if (videos.length >= maxCount) break;
        }
    }

    await page.close();
    return videos.slice(0, maxCount);
}

async function scrapeHashtag(context, hashtag, maxCount) {
    hashtag = hashtag.replace(/^#/, '');
    progress(10, `Scraping #${hashtag}...`);
    const page = await context.newPage();
    const videos = [];

    const captured = [];
    page.on('response', async response => {
        if (response.url().includes('/api/challenge/item_list')) {
            try { captured.push(await response.json()); } catch {}
        }
    });

    await page.goto(`https://www.tiktok.com/tag/${hashtag}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(2000);

    for (let round = 0; round < Math.min(maxCount / 6 + 1, 8); round++) {
        await page.evaluate(() => window.scrollBy(0, window.innerHeight * 3));
        await page.waitForTimeout(1200);
        progress(10 + Math.round(round / 8 * 80), `Scrolling feed...`);
    }

    for (const data of captured) {
        for (const item of (data.itemList || [])) {
            videos.push(parseVideo(item));
            if (videos.length >= maxCount) break;
        }
    }

    await page.close();
    return videos.slice(0, maxCount);
}

async function scrapeProfile(context, username, maxCount) {
    username = username.replace(/^@/, '');
    progress(10, `Fetching @${username}...`);
    const page = await context.newPage();
    const videos = [];

    const captured = [];
    page.on('response', async response => {
        const u = response.url();
        if (u.includes('/api/post/item_list') || u.includes('/api/user/detail')) {
            try { captured.push({ url: u, data: await response.json() }); } catch {}
        }
    });

    await page.goto(`https://www.tiktok.com/@${username}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(2000);

    // Extract profile info
    let profileInfo = null;
    for (const c of captured) {
        if (c.url.includes('/api/user/detail') && c.data.userInfo) {
            const u = c.data.userInfo.user || {};
            const s = c.data.userInfo.stats || {};
            profileInfo = {
                id:        u.id,
                username:  u.uniqueId,
                nickname:  u.nickname,
                bio:       u.signature,
                followers: s.followerCount,
                following: s.followingCount,
                likes:     s.heartCount,
                videos:    s.videoCount,
                verified:  u.verified,
            };
        }
    }

    progress(30, 'Scrolling videos...');
    for (let round = 0; round < Math.min(maxCount / 6 + 1, 10); round++) {
        await page.evaluate(() => window.scrollBy(0, window.innerHeight * 3));
        await page.waitForTimeout(1000);
        progress(30 + Math.round(round / 10 * 60), `Collecting videos...`);
    }

    for (const c of captured) {
        for (const item of (c.data?.itemList || [])) {
            videos.push(parseVideo(item));
        }
    }

    await page.close();
    return { profile: profileInfo, videos: videos.slice(0, maxCount) };
}

async function scrapeTrending(context, maxCount) {
    progress(10, 'Loading trending...');
    const page = await context.newPage();
    const videos = [];

    const captured = [];
    page.on('response', async response => {
        if (response.url().includes('/api/recommend/item_list')) {
            try { captured.push(await response.json()); } catch {}
        }
    });

    await page.goto('https://www.tiktok.com/foryou', { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(3000);

    for (let round = 0; round < Math.min(maxCount / 5, 8); round++) {
        await page.evaluate(() => window.scrollBy(0, window.innerHeight * 2));
        await page.waitForTimeout(1500);
        progress(10 + Math.round(round / 8 * 80), `Collecting trending...`);
    }

    for (const data of captured) {
        for (const item of (data.itemList || [])) {
            videos.push(parseVideo(item));
            if (videos.length >= maxCount) break;
        }
    }

    await page.close();
    return videos.slice(0, maxCount);
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function parseVideo(item) {
    const stats  = item.stats || {};
    const author = item.author || {};
    const video  = item.video || {};
    const tags   = (item.challenges || []).map(c => c.hashtagName).filter(Boolean);

    return {
        id:           item.id,
        description:  (item.desc || '').slice(0, 200),
        author:       author.uniqueId || '',
        author_name:  author.nickname || '',
        likes:        stats.diggCount     || 0,
        comments:     stats.commentCount  || 0,
        shares:       stats.shareCount    || 0,
        plays:        stats.playCount     || 0,
        duration_sec: video.duration      || 0,
        cover_url:    video.cover         || '',
        download_url: video.downloadAddr  || '',
        hashtags:     tags.join(', '),
        created_at:   item.createTime     || 0,
        engagement_rate: (
            ((stats.diggCount || 0) + (stats.commentCount || 0) + (stats.shareCount || 0))
            / Math.max(stats.playCount || 1, 1) * 100
        ).toFixed(2) + '%',
    };
}

function exportCSV(videos, fileName) {
    const exportDir = path.join(__dirname, 'exports');
    if (!fs.existsSync(exportDir)) fs.mkdirSync(exportDir, { recursive: true });

    const filePath = path.join(exportDir, fileName);
    const headers  = Object.keys(videos[0] || {});
    const rows     = videos.map(v => headers.map(h => `"${String(v[h] || '').replace(/"/g, '""')}"`).join(','));

    fs.writeFileSync(filePath, [headers.join(','), ...rows].join('\n'), 'utf8');
    return filePath;
}

// ── Run ──────────────────────────────────────────────────────────────────────
main().catch(err => {
    emit('error', { message: err.message });
    process.exit(1);
});
