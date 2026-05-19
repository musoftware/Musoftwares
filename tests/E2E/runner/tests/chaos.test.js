const { execSync } = require('child_process');
const axios = require('axios');

const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost:8001';

describe('Chaos Tests (Docker Kill/Pause)', () => {
    it('backend recovers when redis is paused and resumed', async () => {
        try {
            // Pause redis
            execSync('docker pause musoftware_redis_test');
            
            // Try an API request that requires redis (e.g. queue check or login)
            // It should either timeout or fail gracefully
            try {
                await axios.get(`${BACKEND_URL}/api/runtime/plugins`, { timeout: 2000 });
            } catch (e) {
                // Ignore timeout
            }

            // Resume redis
            execSync('docker unpause musoftware_redis_test');
            
            // API should work again
            const res = await axios.get(`${BACKEND_URL}/api/runtime/plugins`);
            expect(res.status).toBe(200);
        } catch (err) {
            // Ensure redis is unpaused if test fails
            try { execSync('docker unpause musoftware_redis_test'); } catch (e) {}
            throw err;
        }
    });

    it('agent recovers if connection drops', async () => {
        // This simulates a network disconnect by restarting the agent container
        execSync('docker restart musoftware_agent_test');
        
        // Wait a few seconds for it to boot back up
        await new Promise(resolve => setTimeout(resolve, 5000));

        const AGENT_URL = process.env.AGENT_URL || 'http://localhost:18400';
        const res = await axios.get(`${AGENT_URL}/health`);
        expect(res.status).toBe(200);
        expect(res.data.ok).toBe(true);
    });
});
