const { spawn } = require('child_process');
const path = require('path');

const workerPath = path.join(__dirname, 'tools', 'musoftware-runtime', 'plugins', 'viral-autopsy', 'worker.js');

const params = {
    url: 'https://www.tiktok.com/@tiktok/video/7106594312292453675' // Valid TikTok URL format
};

console.log('Testing viral-autopsy worker.js...');
console.log('Worker path:', workerPath);

const env = { ...process.env, MUSOFTWARE_PARAMS: JSON.stringify(params) };

const worker = spawn('node', [workerPath], { env });

worker.stdout.on('data', (data) => {
    const lines = data.toString().split('\n');
    for (const line of lines) {
        if (!line.trim()) continue;
        try {
            const parsed = JSON.parse(line);
            console.log(`[STDOUT] type: ${parsed.type}`);
            if (parsed.type === 'error') {
                console.error('Error emitted:', parsed.message);
            }
        } catch (e) {
            console.log(`[STDOUT - raw] ${line}`);
        }
    }
});

worker.stderr.on('data', (data) => {
    console.error(`[STDERR] ${data.toString()}`);
});

worker.on('close', (code) => {
    console.log(`Worker process exited with code ${code}`);
    if (code !== 0) {
        process.exit(1);
    }
});
