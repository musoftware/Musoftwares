const { v4: uuidv4 } = require('uuid');

module.exports = async function(pluginContext) {
    const { onEvent, emitEvent, logger } = pluginContext;

    logger.info('snapchat Extractor Plugin loaded.');

    onEvent('snapchat.extract.start', async (payload, respond) => {
        const { keyword, country, limit, campaignId } = payload;
        
        logger.info(`Starting snapchat extraction for: ${keyword} in ${country}`);
        respond({ success: true, message: 'Extraction started' });

        let extracted = 0;
        const scrapeInterval = setInterval(() => {
            if (extracted >= limit) {
                clearInterval(scrapeInterval);
                emitEvent('snapchat.extract.completed', { campaignId, total: extracted });
                return;
            }

            const mockLead = {
                id: uuidv4(),
                campaign_id: campaignId,
                source: 'snapchat',
                name: `snapchat Lead ${extracted + 1}`,
                email: `lead_${extracted}@snapchat.test`,
                dedupe_hash: `snapchat_${keyword}_${extracted}`
            };

            emitEvent('prospecting.lead.extracted', { lead: mockLead });
            extracted++;
        }, 1500);
    });

    return new Promise((resolve) => {
        onEvent('shutdown', () => {
            logger.info('Shutting down snapchat Extractor...');
            resolve();
        });
    });
};