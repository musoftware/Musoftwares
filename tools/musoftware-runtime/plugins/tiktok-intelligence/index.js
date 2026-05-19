const path = require('path');
const IntelligenceDB = require('./db');
const JobScheduler = require('./scheduler');
// Placeholder for playwright logic
// const { chromium } = require('playwright-extra');
// const stealth = require('puppeteer-extra-plugin-stealth')();
// chromium.use(stealth);

let db;

function initDB(pluginDir) {
    if (!db) {
        db = new IntelligenceDB(path.join(pluginDir, 'tiktok_intelligence.sqlite'));
    }
    return db;
}

function registerRoutes(app, pluginDir) {
    initDB(pluginDir);

    // Start job scheduler
    const scheduler = new JobScheduler(pluginDir);
    scheduler.start();

    // --- Creators API ---
    app.get('/plugins/tiktok-intelligence/api/creators', (req, res) => {
        try {
            const filters = req.query;
            const creators = db.getCreators(filters);
            res.json({ success: true, creators });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });

    app.post('/plugins/tiktok-intelligence/api/creators/:id/save', (req, res) => {
        try {
            const { is_saved } = req.body;
            db.toggleCreatorSaved(req.params.id, is_saved);
            res.json({ success: true });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });

    // --- Videos / Vault API ---
    app.get('/plugins/tiktok-intelligence/api/vault', (req, res) => {
        try {
            const videos = db.getSavedVideos();
            res.json({ success: true, videos });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });

    app.post('/plugins/tiktok-intelligence/api/videos/:id/save', (req, res) => {
        try {
            const { is_saved } = req.body;
            db.toggleVideoSaved(req.params.id, is_saved);
            res.json({ success: true });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });

    // --- Monitoring Jobs API ---
    app.get('/plugins/tiktok-intelligence/api/jobs', (req, res) => {
        try {
            const jobs = db.getJobs();
            res.json({ success: true, jobs });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });

    app.post('/plugins/tiktok-intelligence/api/jobs', (req, res) => {
        try {
            const { type, target, interval_hours } = req.body;
            db.addJob({ type, target, interval_hours });
            res.json({ success: true });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });

    app.delete('/plugins/tiktok-intelligence/api/jobs/:id', (req, res) => {
        try {
            db.deleteJob(req.params.id);
            res.json({ success: true });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });

    app.post('/plugins/tiktok-intelligence/api/jobs/:id/status', (req, res) => {
        try {
            const { status } = req.body;
            db.toggleJobStatus(req.params.id, status);
            res.json({ success: true });
        } catch (e) {
            res.status(500).json({ success: false, error: e.message });
        }
    });
}

// Main execution function for active tasks (discover, scrape, etc)
async function runTask(params, ctx) {
    const { action, query, max_count } = params;
    initDB(ctx.pluginDir);

    ctx.log(\`Initializing TikTok Intelligence Engine...\`);
    ctx.log(\`Mode: \${action}, Target: \${query || 'Trending'}\`);
    ctx.progress(10);

    // TODO: Implement actual Playwright stealth scraping here
    // For now, returning mock data to build the UI
    
    ctx.log('Connecting to TikTok (simulated)...');
    await new Promise(r => setTimeout(r, 1000));
    ctx.progress(30);
    
    ctx.log('Extracting creator data...');
    await new Promise(r => setTimeout(r, 1000));
    ctx.progress(60);

    const mockCreatorId = 'mock_creator_' + Date.now();
    const mockCreator = {
        id: mockCreatorId,
        username: query || 'mock_user',
        nickname: 'Mock User',
        avatar_url: 'https://ui-avatars.com/api/?name=' + (query || 'Mock'),
        bio: 'This is a mock bio for ' + query,
        followers: Math.floor(Math.random() * 1000000),
        following: 120,
        likes: Math.floor(Math.random() * 5000000),
        videos_count: 45,
        is_verified: 0,
        email: 'contact@mockuser.com',
        external_links: 'https://mockuser.com',
        trust_score: 85.5
    };

    db.upsertCreator(mockCreator);
    ctx.log(\`Saved creator profile: @\${mockCreator.username}\`);
    ctx.progress(80);

    const mockVideos = [];
    for (let i = 0; i < Math.min(max_count, 5); i++) {
        const vid = {
            id: 'vid_' + Date.now() + '_' + i,
            creator_id: mockCreatorId,
            description: \`Awesome mock video #\${i} about \${query}\`,
            plays: Math.floor(Math.random() * 100000),
            likes: Math.floor(Math.random() * 10000),
            comments: Math.floor(Math.random() * 500),
            shares: Math.floor(Math.random() * 200),
            duration_sec: Math.floor(Math.random() * 60) + 15,
            cover_url: '',
            download_url: '',
            engagement_rate: (Math.random() * 15).toFixed(2),
            hashtags: '#mock #viral',
            posted_at: new Date().toISOString()
        };
        mockVideos.push(vid);
        db.upsertVideo(vid);
    }
    
    ctx.log(\`Extracted and saved \${mockVideos.length} videos to Vault.\`);
    ctx.progress(100);

    return {
        profile: mockCreator,
        videos: mockVideos
    };
}

module.exports = {
    registerRoutes,
    runTask
};
