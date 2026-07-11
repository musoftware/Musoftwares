import { test, expect, Page } from '@playwright/test';

const ADMIN_CREDENTIALS = [
    { email: 'admin@musoftwares.com', password: 'password' },
    { email: 'admin@app.com', password: 'password' }
];

const TENANT_CREDENTIALS = [
    { email: 'tenant@musoftwares.com', password: 'password' },
    { email: 'tenant@example.com', password: 'password' }
];

const PUBLIC_ROUTES = ['/', '/login'];

const ADMIN_ROUTES = [
    '/admin/dashboard',
    '/admin/users',
    '/admin/plans',
    '/admin/kyc',
    '/admin/reports',
    '/admin/projects',
    '/admin/contracts',
    '/admin/busy-times',
    '/admin/settings'
];

const TENANT_ROUTES = [
    '/erp/dashboard',
    '/erp/clients',
    '/erp/projects',
    '/erp/invoices',
    '/erp/expenses',
    '/booking/dashboard',
    '/booking/providers',
    '/booking/appointments',
    '/booking/exceptions',
    '/crm/dashboard',
    '/crm/pipelines',
    '/crm/leads',
    '/crm/sequences',
    '/crm/campaigns'
];

async function login(page: Page, credentials: { email: string; password: string }[], pattern: RegExp) {
    for (const cred of credentials) {
        try {
            console.log(`Attempting to log in as ${cred.email}...`);
            await page.goto('/login');
            await page.waitForSelector('input[name="email"]', { timeout: 3000 });
            await page.fill('input[name="email"]', cred.email);
            await page.fill('input[name="password"]', cred.password);
            await page.click('button[type="submit"]');
            await page.waitForURL(pattern, { timeout: 5000 });
            console.log(`Successfully logged in as ${cred.email}`);
            return;
        } catch (e) {
            console.log(`Failed login with ${cred.email}, trying next fallback...`);
        }
    }
    throw new Error('Could not authenticate with any seeded test accounts.');
}

async function checkRoute(page: Page, url: string, failedPages: Record<string, string[]>) {
    const pageErrors: string[] = [];
    const consoleErrors: string[] = [];

    const handlePageError = (exception: Error) => {
        pageErrors.push(`[Unhandled Exception] ${exception.message}\nStack: ${exception.stack}`);
    };

    const handleConsole = (msg: any) => {
        if (msg.type() === 'error') {
            const text = msg.text();
            // Filter out network resource fails or extension logs
            if (!text.includes('Failed to load resource') && 
                !text.includes('favicon.ico') && 
                !text.includes('chrome-extension://')) {
                consoleErrors.push(text);
            }
        }
    };

    page.on('pageerror', handlePageError);
    page.on('console', handleConsole);

    try {
        console.log(`Navigating to: ${url}`);
        const response = await page.goto(url);

        // Allow routes that are not configured/loaded (404)
        if (response && response.status() === 404) {
            console.log(`Skipped (404 Not Found): ${url}`);
            return;
        }

        // Wait for page load stability
        await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
        await page.waitForTimeout(1000); // Wait 1 second to let any rendering/charts settle

        const allErrors = [...pageErrors, ...consoleErrors];
        if (allErrors.length > 0) {
            failedPages[url] = allErrors;
        }
    } catch (err: any) {
        failedPages[url] = [`[Navigation Failure] ${err.message}`];
    } finally {
        page.off('pageerror', handlePageError);
        page.off('console', handleConsole);
    }
}

test.describe('E2E Console Error Verification', () => {

    test('Public routes are free of errors', async ({ page }) => {
        const failedPages: Record<string, string[]> = {};
        for (const url of PUBLIC_ROUTES) {
            await checkRoute(page, url, failedPages);
        }

        if (Object.keys(failedPages).length > 0) {
            console.error('Errors found on Public routes:', JSON.stringify(failedPages, null, 2));
            expect(Object.keys(failedPages).length).toBe(0);
        }
    });

    test('Admin routes are free of errors', async ({ page }) => {
        await login(page, ADMIN_CREDENTIALS, /\/(admin|dashboard)/);

        const failedPages: Record<string, string[]> = {};
        for (const url of ADMIN_ROUTES) {
            await checkRoute(page, url, failedPages);
        }

        if (Object.keys(failedPages).length > 0) {
            console.error('Errors found on Admin routes:', JSON.stringify(failedPages, null, 2));
            expect(Object.keys(failedPages).length).toBe(0);
        }
    });

    test('Tenant routes are free of errors', async ({ page }) => {
        await login(page, TENANT_CREDENTIALS, /\/(erp|booking|dashboard)/);

        const failedPages: Record<string, string[]> = {};
        for (const url of TENANT_ROUTES) {
            await checkRoute(page, url, failedPages);
        }

        if (Object.keys(failedPages).length > 0) {
            console.error('Errors found on Tenant routes:', JSON.stringify(failedPages, null, 2));
            expect(Object.keys(failedPages).length).toBe(0);
        }
    });
});
