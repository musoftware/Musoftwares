const fs = require('fs');
const path = require('path');
const Storage = require('../core/storage');

describe('Storage', () => {
    let storage;
    let testDir;

    beforeEach(() => {
        testDir = path.join(__dirname, 'test_storage');
        if (fs.existsSync(testDir)) fs.rmSync(testDir, { recursive: true, force: true });
        
        const config = { storageDir: testDir };
        const logger = { debug: jest.fn(), info: jest.fn(), warn: jest.fn() };
        storage = new Storage(config, logger);
        storage.init();
    });

    afterEach(() => {
        storage.close();
        if (fs.existsSync(testDir)) fs.rmSync(testDir, { recursive: true, force: true });
    });

    test('can create and get a task', () => {
        storage.createTask('task_1', 'plugin_1', 'nodejs', { foo: 'bar' });
        
        const task = storage.getTask('task_1');
        expect(task.id).toBe('task_1');
        expect(task.plugin_id).toBe('plugin_1');
        expect(task.status).toBe('running');
        expect(task.params.foo).toBe('bar');
    });

    test('can update task status and result', () => {
        storage.createTask('task_1', 'plugin_1', 'nodejs', {});
        storage.updateTask('task_1', 'done', { result: 'success' });
        
        const task = storage.getTask('task_1');
        expect(task.status).toBe('done');
        expect(task.result.result).toBe('success');
    });

    test('can manage license cache', () => {
        storage.upsertLicense('test_slug', { status: 'active', expiresAt: null });
        
        expect(storage.checkLicense('test_slug')).toBe('active');
        
        // Revoke
        storage.revokeLicense('test_slug');
        expect(storage.checkLicense('test_slug')).toBe('not_found');
    });

    test('license cache expires', () => {
        // Set expiry to past
        storage.upsertLicense('test_slug', { status: 'active', expiresAt: Date.now() - 10000 });
        expect(storage.checkLicense('test_slug')).toBe('expired');
    });
});
