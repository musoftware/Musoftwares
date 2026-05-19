const { v4: uuidv4 } = require('uuid');

module.exports = async function(pluginContext) {
    const { onEvent, emitEvent, logger } = pluginContext;

    logger.info('Google Maps Extractor Plugin loaded.');

    onEvent('google_maps.extract.start', async (payload, respond) => {
        const { keyword, country, limit, campaignId } = payload;
        
        logger.info(`Starting Google Maps extraction for: ${keyword} in ${country}`);
        
        // Respond immediately that the task has started
        respond({ success: true, message: 'Extraction started' });

        // Mock Puppeteer Extraction Logic
        let extracted = 0;
        
        const scrapeInterval = setInterval(() => {
            if (extracted >= limit) {
                clearInterval(scrapeInterval);
                emitEvent('google_maps.extract.completed', { campaignId, total: extracted });
                return;
            }

            const mockLead = {
                id: uuidv4(),
                campaign_id: campaignId,
                source: 'google_maps',
                name: `${keyword} Business ${extracted + 1}`,
                company: `${keyword} Co.`,
                phone: `+1-555-010${extracted}`,
                website: `https://business${extracted}.example.com`,
                social_links: [],
                dedupe_hash: `gmaps_${keyword}_${extracted}`
            };

            // Send extracted lead to the lead-intelligence coordinator
            emitEvent('prospecting.lead.extracted', { lead: mockLead });
            extracted++;

        }, 1500);
    });

    return new Promise((resolve) => {
        onEvent('shutdown', () => {
            logger.info('Shutting down Google Maps Extractor...');
            resolve();
        });
    });
};
