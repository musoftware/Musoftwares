const { v4: uuidv4 } = require('uuid');

module.exports = async function(pluginContext) {
    const { onEvent, emitEvent, logger } = pluginContext;

    logger.info('LinkedIn Extractor Plugin loaded.');

    onEvent('linkedin.extract.start', async (payload, respond) => {
        const { keyword, country, limit, campaignId } = payload;
        
        logger.info(`Starting LinkedIn extraction for: ${keyword} in ${country}`);
        
        // Respond immediately that the task has started
        respond({ success: true, message: 'Extraction started' });

        // Mock LinkedIn Extraction Logic
        let extracted = 0;
        
        const scrapeInterval = setInterval(() => {
            if (extracted >= limit) {
                clearInterval(scrapeInterval);
                emitEvent('linkedin.extract.completed', { campaignId, total: extracted });
                return;
            }

            const mockLead = {
                id: uuidv4(),
                campaign_id: campaignId,
                source: 'linkedin',
                name: `LinkedIn User ${extracted + 1}`,
                company: `${keyword} Solutions`,
                title: 'CEO',
                email: `ceo_${extracted}@${keyword.replace(/\s+/g, '')}.com`,
                social_links: [`https://linkedin.com/in/user${extracted}`],
                dedupe_hash: `linkedin_${keyword}_${extracted}`
            };

            // Send extracted lead to the lead-intelligence coordinator
            emitEvent('prospecting.lead.extracted', { lead: mockLead });
            extracted++;

        }, 1500);
    });

    return new Promise((resolve) => {
        onEvent('shutdown', () => {
            logger.info('Shutting down LinkedIn Extractor...');
            resolve();
        });
    });
};
