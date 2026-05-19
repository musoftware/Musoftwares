const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');

class IntelligenceDB {
    constructor(dbPath) {
        // Ensure directory exists
        const dir = path.dirname(dbPath);
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }

        this.db = new Database(dbPath);
        this.init();
    }

    init() {
        this.db.pragma('journal_mode = WAL');
        
        // Creators Table
        this.db.exec(`
            CREATE TABLE IF NOT EXISTS creators (
                id TEXT PRIMARY KEY,
                username TEXT UNIQUE NOT NULL,
                nickname TEXT,
                avatar_url TEXT,
                bio TEXT,
                followers INTEGER DEFAULT 0,
                following INTEGER DEFAULT 0,
                likes INTEGER DEFAULT 0,
                videos_count INTEGER DEFAULT 0,
                is_verified BOOLEAN DEFAULT 0,
                email TEXT,
                external_links TEXT,
                trust_score REAL DEFAULT 0,
                discovered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
                is_saved BOOLEAN DEFAULT 0
            )
        `);

        // Videos Table (UGC Vault)
        this.db.exec(`
            CREATE TABLE IF NOT EXISTS videos (
                id TEXT PRIMARY KEY,
                creator_id TEXT,
                description TEXT,
                plays INTEGER DEFAULT 0,
                likes INTEGER DEFAULT 0,
                comments INTEGER DEFAULT 0,
                shares INTEGER DEFAULT 0,
                duration_sec INTEGER DEFAULT 0,
                cover_url TEXT,
                download_url TEXT,
                engagement_rate REAL DEFAULT 0,
                hashtags TEXT,
                posted_at DATETIME,
                discovered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                is_saved BOOLEAN DEFAULT 0,
                FOREIGN KEY(creator_id) REFERENCES creators(id)
            )
        `);

        // Monitoring Jobs Table
        this.db.exec(`
            CREATE TABLE IF NOT EXISTS monitoring_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL, -- 'hashtag', 'competitor', 'keyword'
                target TEXT NOT NULL, -- e.g., 'fitness', '@competitor'
                interval_hours INTEGER DEFAULT 24,
                last_run DATETIME,
                next_run DATETIME,
                status TEXT DEFAULT 'active', -- 'active', 'paused', 'error'
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // Job Logs Table
        this.db.exec(`
            CREATE TABLE IF NOT EXISTS job_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id INTEGER,
                status TEXT, -- 'success', 'failed'
                items_found INTEGER DEFAULT 0,
                error_message TEXT,
                run_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(job_id) REFERENCES monitoring_jobs(id)
            )
        `);
    }

    // --- Creators ---
    upsertCreator(creator) {
        const stmt = this.db.prepare(`
            INSERT INTO creators (
                id, username, nickname, avatar_url, bio, followers, following, 
                likes, videos_count, is_verified, email, external_links, trust_score, last_updated
            ) VALUES (
                @id, @username, @nickname, @avatar_url, @bio, @followers, @following,
                @likes, @videos_count, @is_verified, @email, @external_links, @trust_score, CURRENT_TIMESTAMP
            )
            ON CONFLICT(username) DO UPDATE SET
                nickname = excluded.nickname,
                avatar_url = excluded.avatar_url,
                bio = excluded.bio,
                followers = excluded.followers,
                following = excluded.following,
                likes = excluded.likes,
                videos_count = excluded.videos_count,
                is_verified = excluded.is_verified,
                email = coalesce(excluded.email, creators.email),
                external_links = coalesce(excluded.external_links, creators.external_links),
                trust_score = excluded.trust_score,
                last_updated = CURRENT_TIMESTAMP
        `);
        return stmt.run(creator);
    }

    getCreators(filters = {}) {
        let query = "SELECT * FROM creators WHERE 1=1";
        const params = {};

        if (filters.is_saved) {
            query += " AND is_saved = 1";
        }
        if (filters.search) {
            query += " AND (username LIKE @search OR nickname LIKE @search OR bio LIKE @search)";
            params.search = `%${filters.search}%`;
        }

        query += " ORDER BY followers DESC LIMIT @limit OFFSET @offset";
        params.limit = filters.limit || 50;
        params.offset = filters.offset || 0;

        return this.db.prepare(query).all(params);
    }
    
    toggleCreatorSaved(id, isSaved) {
        return this.db.prepare("UPDATE creators SET is_saved = @isSaved WHERE id = @id").run({ id, isSaved: isSaved ? 1 : 0 });
    }

    // --- Videos (UGC Vault) ---
    upsertVideo(video) {
        const stmt = this.db.prepare(`
            INSERT INTO videos (
                id, creator_id, description, plays, likes, comments, shares, 
                duration_sec, cover_url, download_url, engagement_rate, hashtags, posted_at
            ) VALUES (
                @id, @creator_id, @description, @plays, @likes, @comments, @shares,
                @duration_sec, @cover_url, @download_url, @engagement_rate, @hashtags, @posted_at
            )
            ON CONFLICT(id) DO UPDATE SET
                plays = excluded.plays,
                likes = excluded.likes,
                comments = excluded.comments,
                shares = excluded.shares,
                engagement_rate = excluded.engagement_rate
        `);
        return stmt.run(video);
    }
    
    getSavedVideos() {
        return this.db.prepare(`
            SELECT v.*, c.username as author, c.nickname as author_name, c.avatar_url 
            FROM videos v 
            LEFT JOIN creators c ON v.creator_id = c.id 
            WHERE v.is_saved = 1 
            ORDER BY v.discovered_at DESC
        `).all();
    }
    
    toggleVideoSaved(id, isSaved) {
        return this.db.prepare("UPDATE videos SET is_saved = @isSaved WHERE id = @id").run({ id, isSaved: isSaved ? 1 : 0 });
    }

    // --- Monitoring Jobs ---
    addJob(job) {
        const stmt = this.db.prepare(`
            INSERT INTO monitoring_jobs (type, target, interval_hours, next_run)
            VALUES (@type, @target, @interval_hours, datetime('now'))
        `);
        return stmt.run(job);
    }

    getJobs() {
        return this.db.prepare("SELECT * FROM monitoring_jobs ORDER BY created_at DESC").all();
    }
    
    deleteJob(id) {
        return this.db.prepare("DELETE FROM monitoring_jobs WHERE id = @id").run({ id });
    }
    
    toggleJobStatus(id, status) {
        return this.db.prepare("UPDATE monitoring_jobs SET status = @status WHERE id = @id").run({ id, status });
    }
}

module.exports = IntelligenceDB;
