import http from 'k6/http';
import { check, sleep } from 'k6';

// This script simulates 1000 concurrent agents calling the license check endpoint

export const options = {
    stages: [
        { duration: '30s', target: 200 }, // Ramp up to 200 users
        { duration: '1m', target: 500 },  // Ramp up to 500
        { duration: '30s', target: 1000 },// Spike to 1000
        { duration: '1m', target: 1000 }, // Hold 1000
        { duration: '30s', target: 0 },   // Ramp down
    ],
};

const BACKEND_URL = __ENV.BACKEND_URL || 'http://localhost:8001';
// In a real test, this token would be seeded or obtained during a setup phase
const API_TOKEN = __ENV.API_TOKEN || 'testing_token';

export default function () {
    const payload = JSON.stringify({
        license_key: 'test-uuid-license-key',
        hardware_fingerprint: `hw-fingerprint-${__VU}`, // Unique per virtual user
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${API_TOKEN}`,
            'Accept': 'application/json',
        },
    };

    const res = http.post(`${BACKEND_URL}/api/tools/license/check`, payload, params);

    check(res, {
        'status is 200 or 403': (r) => r.status === 200 || r.status === 403,
        'response time < 500ms': (r) => r.timings.duration < 500,
    });

    sleep(1);
}
