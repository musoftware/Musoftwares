const IntelligenceDB = require('./db');
const path = require('path');

class JobScheduler {
    constructor(pluginDir) {
        this.db = new IntelligenceDB(path.join(pluginDir, 'tiktok_intelligence.sqlite'));
        this.interval = null;
    }

    start() {
        if (this.interval) return;
        console.log('[TikTok Intelligence] Starting job scheduler...');
        
        // Check jobs every minute
        this.interval = setInterval(() => {
            this.checkJobs();
        }, 60 * 1000);
        
        // Initial check
        this.checkJobs();
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
            console.log('[TikTok Intelligence] Job scheduler stopped.');
        }
    }

    async checkJobs() {
        try {
            // Find jobs that are active and whose next_run is <= now
            const jobs = this.db.db.prepare("SELECT * FROM monitoring_jobs WHERE status = 'active' AND (next_run IS NULL OR next_run <= datetime('now'))").all();
            
            for (const job of jobs) {
                console.log(`[TikTok Intelligence] Running job #${job.id}: ${job.type} -> ${job.target}`);
                
                try {
                    // MOCK EXECUTION logic
                    // In real-world, we'd spawn a playwright worker here
                    await new Promise(r => setTimeout(r, 2000));
                    
                    const itemsFound = Math.floor(Math.random() * 5);
                    
                    // Log success
                    this.db.db.prepare("INSERT INTO job_logs (job_id, status, items_found) VALUES (?, 'success', ?)").run(job.id, itemsFound);
                    
                    // Update next run time
                    this.db.db.prepare("UPDATE monitoring_jobs SET last_run = datetime('now'), next_run = datetime('now', '+' || ? || ' hours') WHERE id = ?").run(job.interval_hours, job.id);
                    
                    console.log(`[TikTok Intelligence] Job #${job.id} completed. Found ${itemsFound} items.`);
                } catch (e) {
                    console.error(`[TikTok Intelligence] Job #${job.id} failed:`, e);
                    this.db.db.prepare("INSERT INTO job_logs (job_id, status, error_message) VALUES (?, 'failed', ?)").run(job.id, e.message);
                }
            }
        } catch (e) {
            console.error('[TikTok Intelligence] Scheduler check failed:', e);
        }
    }
}

module.exports = JobScheduler;
