const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');

class DatabaseManager {
    constructor(pluginPath) {
        const dbDir = path.join(pluginPath, 'data');
        if (!fs.existsSync(dbDir)) {
            fs.mkdirSync(dbDir, { recursive: true });
        }
        
        const dbPath = path.join(dbDir, 'prospecting.db');
        this.db = new Database(dbPath, { verbose: null });
        
        this.init();
    }

    init() {
        // Create Campaigns Table
        this.db.exec(`
            CREATE TABLE IF NOT EXISTS campaigns (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                keyword TEXT NOT NULL,
                country TEXT,
                sources TEXT,
                status TEXT DEFAULT 'draft',
                daily_limit INTEGER DEFAULT 100,
                extracted_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // Create Leads Table
        this.db.exec(`
            CREATE TABLE IF NOT EXISTS leads (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL,
                source TEXT NOT NULL,
                name TEXT,
                company TEXT,
                title TEXT,
                email TEXT,
                phone TEXT,
                website TEXT,
                social_links TEXT,
                dedupe_hash TEXT UNIQUE,
                email_status TEXT DEFAULT 'unverified',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
            )
        `);
    }

    createCampaign(campaignData) {
        const stmt = this.db.prepare(`
            INSERT INTO campaigns (id, name, keyword, country, sources, daily_limit, status)
            VALUES (@id, @name, @keyword, @country, @sources, @daily_limit, @status)
        `);
        stmt.run({
            id: campaignData.id,
            name: campaignData.name,
            keyword: campaignData.keyword,
            country: campaignData.country || null,
            sources: JSON.stringify(campaignData.sources || []),
            daily_limit: campaignData.daily_limit || 100,
            status: campaignData.status || 'draft'
        });
        return this.getCampaign(campaignData.id);
    }

    getCampaign(id) {
        const stmt = this.db.prepare('SELECT * FROM campaigns WHERE id = ?');
        const campaign = stmt.get(id);
        if (campaign && campaign.sources) {
            campaign.sources = JSON.parse(campaign.sources);
        }
        return campaign;
    }

    getAllCampaigns() {
        const stmt = this.db.prepare('SELECT * FROM campaigns ORDER BY created_at DESC');
        const campaigns = stmt.all();
        return campaigns.map(c => {
            if (c.sources) c.sources = JSON.parse(c.sources);
            return c;
        });
    }

    updateCampaignStatus(id, status) {
        const stmt = this.db.prepare('UPDATE campaigns SET status = ? WHERE id = ?');
        stmt.run(status, id);
    }

    incrementCampaignExtracted(id) {
        const stmt = this.db.prepare('UPDATE campaigns SET extracted_count = extracted_count + 1 WHERE id = ?');
        stmt.run(id);
    }

    addLead(leadData) {
        try {
            const stmt = this.db.prepare(`
                INSERT INTO leads (id, campaign_id, source, name, company, title, email, phone, website, social_links, dedupe_hash)
                VALUES (@id, @campaign_id, @source, @name, @company, @title, @email, @phone, @website, @social_links, @dedupe_hash)
            `);
            stmt.run({
                id: leadData.id,
                campaign_id: leadData.campaign_id,
                source: leadData.source,
                name: leadData.name || null,
                company: leadData.company || null,
                title: leadData.title || null,
                email: leadData.email || null,
                phone: leadData.phone || null,
                website: leadData.website || null,
                social_links: JSON.stringify(leadData.social_links || []),
                dedupe_hash: leadData.dedupe_hash || null
            });
            this.incrementCampaignExtracted(leadData.campaign_id);
            return true;
        } catch (error) {
            // Probably a UNIQUE constraint failure on dedupe_hash
            if (error.code === 'SQLITE_CONSTRAINT_UNIQUE') {
                return false; // Duplicate lead
            }
            throw error;
        }
    }

    getLeadsForCampaign(campaignId, limit = 100, offset = 0) {
        const stmt = this.db.prepare('SELECT * FROM leads WHERE campaign_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
        return stmt.all(campaignId, limit, offset);
    }
}

module.exports = DatabaseManager;
