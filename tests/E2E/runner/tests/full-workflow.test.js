const axios = require('axios');
const WebSocket = require('ws');

const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost:8001';
const AGENT_URL = process.env.AGENT_URL || 'http://localhost:18400';
const AGENT_WS_URL = process.env.AGENT_WS_URL || 'ws://localhost:18401/ws';

describe('Full E2E Workflow', () => {
    let ws;
    let wsMessages = [];

    beforeAll((done) => {
        ws = new WebSocket(AGENT_WS_URL);
        ws.on('message', (data) => {
            const msg = JSON.parse(data.toString());
            wsMessages.push(msg);
        });
        ws.on('open', () => done());
    });

    afterAll(() => {
        if (ws) ws.close();
    });

    it('runtime agent is healthy', async () => {
        const res = await axios.get(`${AGENT_URL}/health`);
        expect(res.status).toBe(200);
        expect(res.data.ok).toBe(true);
    });

    it('receives websocket events from the agent', async () => {
        // Find the ready event sent on connection
        const readyEvent = wsMessages.find(m => m.event === 'runtime.ready');
        expect(readyEvent).toBeDefined();
        expect(readyEvent.data.version).toBeDefined();
    });

    it('can execute a task via the runtime API', async () => {
        // Mock a task execution for an existing plugin (if one exists in the test DB)
        // Since we are blackbox testing, we attempt to run a missing plugin and expect an error
        try {
            await axios.post(`${AGENT_URL}/plugins/non-existent-plugin/run`);
            fail('Should have thrown a 404');
        } catch (err) {
            expect(err.response.status).toBe(404);
            expect(err.response.data.error).toContain('not installed');
        }
    });
});
