const { v4: uuidv4 } = require('uuid');

module.exports = async function(pluginContext) {
    const { onEvent, emitEvent, logger } = pluginContext;

    logger.info('telegram Extractor Plugin loaded.');

    onEvent('telegram.extract.start', async (payload, respond) => {
        const { keyword, country, limit, campaignId } = payload;
        
        logger.info(`Starting telegram extraction for: ${keyword} in ${country}`);
        respond({ success: true, message: 'Extraction started' });

        let extracted = 0;
        const scrapeInterval = setInterval(() => {
            if (extracted >= limit) {
                clearInterval(scrapeInterval);
                emitEvent('telegram.extract.completed', { campaignId, total: extracted });
                return;
            }

            const mockLead = {
                id: uuidv4(),
                campaign_id: campaignId,
                source: 'telegram',
                name: `telegram Lead ${extracted + 1}`,
                email: `lead_${extracted}@telegram.test`,
                dedupe_hash: `telegram_${keyword}_${extracted}`
            };

            emitEvent('prospecting.lead.extracted', { lead: mockLead });
            extracted++;
        }, 1500);
    });

    return new Promise((resolve) => {
        onEvent('shutdown', () => {
            logger.info('Shutting down telegram Extractor...');
            resolve();
        });
    });
};