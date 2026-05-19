const fs = require('fs');
const path = require('path');
const IntelligenceDB = require('./db');
const JobScheduler = require('./scheduler');
const assert = require('assert');

async function runTests() {
    console.log('--- Running TikTok Intelligence Engine Tests ---');
    
    // Use a temp DB for testing
    const testDir = path.join(__dirname, 'test_tmp');
    const dbPath = path.join(testDir, 'tiktok_intelligence.sqlite');
    
    if (fs.existsSync(testDir)) {
        fs.rmSync(testDir, { recursive: true, force: true });
    }
    
    let db;
    try {
        db = new IntelligenceDB(dbPath);
        console.log('✅ DB initialized');
        
        // Test Creator Insert
        db.upsertCreator({
            id: 'test_creator_1',
            username: 'testuser',
            nickname: 'Test User',
            avatar_url: '',
            bio: '',
            followers: 1000,
            following: 0,
            likes: 0,
            videos_count: 0,
            is_verified: 0,
            email: '',
            external_links: '',
            trust_score: 95.5
        });
        
        let creators = db.getCreators();
        assert.strictEqual(creators.length, 1);
        assert.strictEqual(creators[0].username, 'testuser');
        console.log('✅ Creator upsert works');
        
        // Test Video Insert
        db.upsertVideo({
            id: 'test_video_1',
            creator_id: 'test_creator_1',
            description: 'Test description',
            plays: 500,
            likes: 0,
            comments: 0,
            shares: 0,
            duration_sec: 15,
            cover_url: '',
            download_url: '',
            engagement_rate: 0,
            hashtags: '',
            posted_at: new Date().toISOString()
        });
        
        db.toggleVideoSaved('test_video_1', true);
        
        let videos = db.getSavedVideos();
        assert.strictEqual(videos.length, 1);
        assert.strictEqual(videos[0].plays, 500);
        console.log('✅ Video upsert works');
        
        // Test Jobs
        db.addJob({ type: 'hashtag', target: '#test', interval_hours: 24 });
        let jobs = db.getJobs();
        assert.strictEqual(jobs.length, 1);
        assert.strictEqual(jobs[0].target, '#test');
        console.log('✅ Jobs insert works');
        
        // Test Scheduler
        const scheduler = new JobScheduler(testDir);
        await scheduler.checkJobs(); // Simulate one run
        
        jobs = db.getJobs();
        assert.notStrictEqual(jobs[0].last_run, null);
        console.log('✅ Scheduler job execution works');
        
        console.log('--- All tests passed! ---');
    } catch (e) {
        console.error('❌ Test failed:', e);
        process.exit(1);
    } finally {
        if (db && db.db) db.db.close();
        if (fs.existsSync(testDir)) {
            fs.rmSync(testDir, { recursive: true, force: true });
        }
    }
}

runTests();
