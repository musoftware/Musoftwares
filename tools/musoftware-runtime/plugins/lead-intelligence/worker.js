const { v4: uuidv4 } = require('uuid');
const DatabaseManager = require('./database');

module.exports = async function(pluginContext) {
    const { onEvent, emitEvent, config, pluginPath, logger } = pluginContext;

    logger.info('Initializing Lead Intelligence Engine locally...');
    
    // Initialize Local SQLite
    const db = new DatabaseManager(pluginPath);
    
    // In-memory queue to manage running tasks
    const activeTasks = new Map();

    // Event: List Campaigns (Requested by Frontend)
    onEvent('prospecting.campaigns.list', async (payload, respond) => {
        try {
            const campaigns = db.getAllCampaigns();
            respond({ success: true, data: campaigns });
        } catch (error) {
            logger.error(`Error listing campaigns: ${error.message}`);
            respond({ success: false, error: error.message });
        }
    });

    // Event: Create Campaign
    onEvent('prospecting.campaign.create', async (payload, respond) => {
        try {
            const campaignData = {
                id: uuidv4(),
                name: payload.name,
                keyword: payload.keyword,
                country: payload.country,
                sources: payload.sources,
                daily_limit: payload.daily_limit,
                status: 'draft'
            };
            
            const newCampaign = db.createCampaign(campaignData);
            emitEvent('prospecting.campaign.created', { campaign: newCampaign });
            respond({ success: true, data: newCampaign });
        } catch (error) {
            logger.error(`Error creating campaign: ${error.message}`);
            respond({ success: false, error: error.message });
        }
    });

    // Event: Start Campaign
    onEvent('prospecting.campaign.start', async (payload, respond) => {
        const { campaignId } = payload;
        try {
            const campaign = db.getCampaign(campaignId);
            if (!campaign) {
                return respond({ success: false, error: 'Campaign not found' });
            }

            db.updateCampaignStatus(campaignId, 'running');
            emitEvent('prospecting.campaign.updated', { campaignId, status: 'running' });
            
            // Mock Extraction Process
            // Instead of doing it here, we broadcast the start event to the respective source workers
            activeTasks.set(campaignId, true);
            
            campaign.sources.forEach(source => {
                // Emits an event that e.g. the google_maps plugin will listen to
                emitEvent(`${source}.extract.start`, {
                    campaignId,
                    keyword: campaign.keyword,
                    country: campaign.country,
                    limit: campaign.daily_limit
                });
            });

            respond({ success: true, message: 'Campaign started locally and dispatched to workers' });
        } catch (error) {
            logger.error(`Error starting campaign: ${error.message}`);
            respond({ success: false, error: error.message });
        }
    });

    // Event: Stop Campaign
    onEvent('prospecting.campaign.stop', async (payload, respond) => {
        const { campaignId } = payload;
        activeTasks.delete(campaignId);
        db.updateCampaignStatus(campaignId, 'paused');
        emitEvent('prospecting.campaign.updated', { campaignId, status: 'paused' });
        respond({ success: true });
    });

    // Event: Get Leads
    onEvent('prospecting.leads.list', async (payload, respond) => {
        const { campaignId, limit, offset } = payload;
        try {
            const leads = db.getLeadsForCampaign(campaignId, limit || 100, offset || 0);
            respond({ success: true, data: leads });
        } catch (error) {
            respond({ success: false, error: error.message });
        }
    });

    // Event: Receive extracted leads from workers
    onEvent('prospecting.lead.extracted', (payload) => {
        const { lead } = payload;
        if (activeTasks.has(lead.campaign_id)) {
            // Check deduplication (optional local DB handled)
            const added = db.addLead(lead);
            if (added) {
                // Optionally validate email here before emitting to frontend
                if (lead.email) {
                    lead.email_status = validateEmailLocal(lead.email) ? 'valid' : 'invalid';
                }
                
                // Route to UI
                emitEvent('prospecting.lead.extracted.ui', { lead });
                
                const campaign = db.getCampaign(lead.campaign_id);
                if (campaign.extracted_count >= campaign.daily_limit) {
                    activeTasks.delete(campaign.id);
                    db.updateCampaignStatus(campaign.id, 'completed');
                    emitEvent('prospecting.campaign.updated', { campaignId: campaign.id, status: 'completed' });
                }
            }
        }
    });

    // Keep the worker alive
    return new Promise((resolve, reject) => {
        onEvent('shutdown', () => {
            logger.info('Shutting down Lead Intelligence Engine...');
            db.db.close();
            resolve();
        });
    });

    // ----- Helper Functions for Local Engine -----
    function validateEmailLocal(email) {
        // Mock email validation
        return email.includes('@');
    }
};
