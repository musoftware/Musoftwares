const path = require('path');
const fs = require('fs');

async function runE2ETests() {
    console.log('🚀 Starting Lead Intelligence Engine E2E Tests...');
    
    const pluginPath = path.join(__dirname, 'tools', 'musoftware-runtime', 'plugins', 'lead-intelligence');
    const workerModule = require(path.join(pluginPath, 'worker.js'));
    const DatabaseManager = require(path.join(pluginPath, 'database.js'));

    const emittedEvents = [];
    
    // Intercept console.log to capture JSON emit events from worker
    const originalConsoleLog = console.log;
    console.log = (...args) => {
        if (typeof args[0] === 'string' && args[0].startsWith('{')) {
            try {
                const parsed = JSON.parse(args[0]);
                if (parsed.type === 'wa_event') {
                    emittedEvents.push({ event: parsed.event, payload: parsed.data });
                }
            } catch (e) {}
        } else {
            originalConsoleLog(...args);
        }
    };

    // Simulate HTTP request to worker
    const simulateRequest = async (action, payload) => {
        try {
            const data = await workerModule(action, payload);
            return { success: true, data };
        } catch (e) {
            return { success: false, error: e.message };
        }
    };

    try {
        // --- TEST 1: Create Campaign ---
        console.log('\n▶️ TEST 1: Create Campaign');
        const createRes = await simulateRequest('prospecting.campaign.create', {
            name: 'E2E Test Campaign',
            keyword: 'CEO',
            country: 'USA',
            sources: ['linkedin'],
            daily_limit: 2
        });
        
        if (!createRes.success || !createRes.data.id) throw new Error('Failed to create campaign');
        const campaignId = createRes.data.id;
        console.log(`✅ Campaign created: ${campaignId}`);

        // --- TEST 2: Add Inbox (for Outreach testing) ---
        console.log('\n▶️ TEST 2: Add SMTP Inbox');
        const uniqueEmail = `test_${Date.now()}@example.com`;
        const inboxRes = await simulateRequest('prospecting.inbox.add', {
            email: uniqueEmail,
            smtp_host: 'smtp.mailtrap.io',
            smtp_port: 2525,
            smtp_user: 'user',
            smtp_pass: 'pass'
        });
        if (!inboxRes.success) throw new Error('Failed to add inbox');
        console.log(`✅ Inbox added`);

        // --- TEST 3: Start Campaign (Triggers Scraper -> Verification -> Enrichment -> Save) ---
        console.log('\n▶️ TEST 3: Start Campaign Pipeline');
        const startRes = await simulateRequest('prospecting.campaign.start', { campaignId });
        if (!startRes.success) throw new Error('Failed to start campaign');
        
        console.log(`⏳ Waiting 5 seconds for Scraper engine to process limits and emit leads...`);
        
        // Wait for pipeline to finish
        await new Promise(r => setTimeout(r, 5000));

        // Check if leads reached the UI
        const uiEvents = emittedEvents.filter(e => e.event === 'prospecting.lead.extracted.ui');
        console.log(`✅ Emitted ${uiEvents.length} leads to UI`);
        
        if (uiEvents.length === 0) throw new Error('No leads were extracted and piped to UI');

        const firstLead = uiEvents[0].payload.lead;
        console.log(`Lead Data:`, {
            name: firstLead.name,
            email_status: firstLead.email_status,
            lead_score: firstLead.lead_score,
            enrichment_data: firstLead.enrichment_data
        });

        if (!firstLead.email_status || firstLead.lead_score === 0) {
            throw new Error('Lead pipeline failed to verify or enrich');
        }
        console.log(`✅ Deep Verification & Enrichment pipelines passed.`);

        // Check Database State
        const db = new DatabaseManager(pluginPath);
        const savedLeads = db.getLeadsForCampaign(campaignId);
        
        if (savedLeads.length === 0) throw new Error('Leads were not saved to local SQLite DB');
        console.log(`✅ SQLite DB verified: ${savedLeads.length} leads saved.`);

        // Restore console.log
        console.log = originalConsoleLog;
        console.log('\n🛑 Shutting down worker...');
        console.log('✅ E2E Tests Completed Successfully.');
        
        process.exit(0);
        
    } catch (error) {
        console.log = originalConsoleLog;
        console.error(`\n❌ E2E TEST FAILED: ${error.message}`);
        process.exit(1);
    }
}

runE2ETests();
