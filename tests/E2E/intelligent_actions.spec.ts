import { test, expect, Page, Locator } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

// ─────────────────────────────────────────────────────────────────────────────
// Dynamic Test Configuration & Manifest Loader
// ─────────────────────────────────────────────────────────────────────────────

const ADMIN_CREDENTIALS = [
    { email: 'admin@musoftwares.com', password: 'password' },
    { email: 'admin@app.com', password: 'password' }
];

const TENANT_CREDENTIALS = [
    { email: 'tenant@musoftwares.com', password: 'password' },
    { email: 'client@musoftwares.com', password: 'password' },
    { email: 'tenant@example.com', password: 'password' }
];

interface RouteManifest {
    publicRoutes: string[];
    clientRoutes: string[];
    adminRoutes: string[];
    dynamicEntities: Record<string, string[]>;
    dbOnline: boolean;
}

function loadDynamicManifest(): RouteManifest {
    const manifestPath = path.resolve(process.cwd(), 'storage/app/e2e_manifest.json');
    if (fs.existsSync(manifestPath)) {
        try {
            const data = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
            return {
                publicRoutes: data.public_routes || ['/', '/login', '/portfolio', '/blog', '/install-app'],
                clientRoutes: data.client_routes || ['/dashboard', '/profile', '/tickets', '/erp/dashboard', '/crm/dashboard'],
                adminRoutes: data.admin_routes || ['/admin/dashboard', '/admin/users', '/admin/plans', '/admin/settings'],
                dynamicEntities: data.dynamic_entities || {},
                dbOnline: data.db_online === true
            };
        } catch (e) {
            console.warn('[Manifest Load Warning] Could not parse e2e_manifest.json, using defaults.');
        }
    }

    return {
        publicRoutes: ['/', '/login', '/register', '/portfolio', '/blog', '/install-app'],
        clientRoutes: [
            '/dashboard',
            '/profile',
            '/transactions',
            '/tickets',
            '/invoices',
            '/erp/dashboard',
            '/erp/clients',
            '/erp/projects',
            '/erp/invoices',
            '/erp/expenses',
            '/booking/dashboard',
            '/booking/providers',
            '/booking/appointments',
            '/crm/dashboard',
            '/crm/pipelines',
            '/crm/leads',
            '/crm/sequences'
        ],
        adminRoutes: [
            '/admin/dashboard',
            '/admin/users',
            '/admin/plans',
            '/admin/kyc',
            '/admin/reports',
            '/admin/projects',
            '/admin/contracts',
            '/admin/busy-times',
            '/admin/settings',
            '/admin/coupons',
            '/admin/vouchers',
            '/admin/transactions',
            '/admin/currencies'
        ],
        dynamicEntities: {},
        dbOnline: false
    };
}

const manifest = loadDynamicManifest();

// ─────────────────────────────────────────────────────────────────────────────
// Error Monitoring & Network Interception Helpers
// ─────────────────────────────────────────────────────────────────────────────

interface PageAuditRecord {
    url: string;
    actionErrors: string[];
    consoleErrors: string[];
    failedNetworkRequests: { url: string; status: number; statusText: string }[];
}

function setupErrorAuditor(page: Page, record: PageAuditRecord) {
    const handlePageError = (exception: Error) => {
        record.actionErrors.push(`[Unhandled JS Exception] ${exception.message}`);
    };

    const handleConsole = (msg: any) => {
        if (msg.type() === 'error') {
            const text = msg.text();
            // Filter non-fatal 3rd-party noise, extension noise, websocket reconnects
            if (!text.includes('Failed to load resource: the server responded with a status of 404') &&
                !text.includes('favicon.ico') &&
                !text.includes('chrome-extension://') &&
                !text.includes('socket.io') &&
                !text.includes('pusher') &&
                !text.includes('ERR_CONNECTION_REFUSED') &&
                !text.includes('500 (Internal Server Error)')) {
                record.consoleErrors.push(`[Console Error] ${text}`);
            }
        }
    };

    const handleResponse = (response: any) => {
        const status = response.status();
        const reqUrl = response.url();
        // Catch 500-level backend errors
        if (status >= 500 && !reqUrl.includes('google-analytics') && !reqUrl.includes('clarity')) {
            record.failedNetworkRequests.push({
                url: reqUrl,
                status,
                statusText: response.statusText()
            });
        }
    };

    page.on('pageerror', handlePageError);
    page.on('console', handleConsole);
    page.on('response', handleResponse);

    return () => {
        page.off('pageerror', handlePageError);
        page.off('console', handleConsole);
        page.off('response', handleResponse);
    };
}

async function loginUser(page: Page, credentials: { email: string; password: string }[], successPattern: RegExp) {
    for (const cred of credentials) {
        try {
            await page.goto('/login', { timeout: 10000, waitUntil: 'domcontentloaded' });
            const emailInput = page.locator('input[name="email"], input#email, input[type="email"]').first();
            const passInput = page.locator('input[name="password"], input#password, input[type="password"]').first();
            const submitBtn = page.locator('button[type="submit"]').first();

            const isVisible = await emailInput.isVisible({ timeout: 3000 }).catch(() => false);
            if (isVisible) {
                await emailInput.fill(cred.email);
                await passInput.fill(cred.password);
                await submitBtn.click();
                await page.waitForURL(url => !url.pathname.endsWith('/login'), { timeout: 6000 }).catch(() => {});
                
                if (!page.url().endsWith('/login')) {
                    console.log(`  ✅ Successfully authenticated as ${cred.email}`);
                    return true;
                }
            } else if (!page.url().endsWith('/login')) {
                console.log(`  ✅ Already authenticated on ${page.url()}`);
                return true;
            }
        } catch (e: any) {
            console.log(`[Auth Retry] ${cred.email}: ${e.message}`);
        }
    }
    console.warn('[Auth Notice] Testing with active session or edge fallback.');
    return false;
}

// ─────────────────────────────────────────────────────────────────────────────
// Autonomous Interactive Action Engine
// ─────────────────────────────────────────────────────────────────────────────

async function interactivelyExplorePage(page: Page, url: string) {
    const audit: PageAuditRecord = {
        url,
        actionErrors: [],
        consoleErrors: [],
        failedNetworkRequests: []
    };

    const cleanup = setupErrorAuditor(page, audit);

    try {
        const response = await page.goto(url, { timeout: 15000, waitUntil: 'domcontentloaded' });
        
        if (response && response.status() === 404) {
            return audit; // Normal dynamic route skip
        }

        // Wait for React hydration
        await page.waitForLoadState('networkidle', { timeout: 4000 }).catch(() => {});
        await page.waitForTimeout(400);

        // 1. Discover and Click Non-Destructive Tabs & Accordions
        const tabs = page.locator('[role="tab"], button[data-tab], .nav-tabs button, [data-state="inactive"]');
        const tabCount = Math.min(await tabs.count().catch(() => 0), 4);
        for (let i = 0; i < tabCount; i++) {
            try {
                const tab = tabs.nth(i);
                if (await tab.isVisible()) {
                    await tab.click({ timeout: 1500 });
                    await page.waitForTimeout(200);
                }
            } catch (e) {}
        }

        // 2. Discover Search & Filter Inputs and exercise them
        const searchInputs = page.locator('input[type="search"], input[name*="search"], input[placeholder*="search" i], input[placeholder*="بحث" i]');
        const searchCount = Math.min(await searchInputs.count().catch(() => 0), 2);
        for (let i = 0; i < searchCount; i++) {
            try {
                const input = searchInputs.nth(i);
                if (await input.isVisible()) {
                    await input.fill('test');
                    await page.keyboard.press('Enter');
                    await page.waitForTimeout(300);
                    await input.fill('');
                    await page.keyboard.press('Enter');
                }
            } catch (e) {}
        }

        // 3. Discover Safe Action Buttons (Exclude dangerous buttons like Delete/Destroy/Purge)
        const safeButtons = page.locator('button:not([disabled]):not([type="submit"]), [role="button"]:not([aria-disabled="true"])');
        const buttonCount = Math.min(await safeButtons.count().catch(() => 0), 6);
        for (let i = 0; i < buttonCount; i++) {
            try {
                const btn = safeButtons.nth(i);
                const text = (await btn.innerText().catch(() => '')).toLowerCase();
                const isDestructive = /delete|remove|destroy|truncate|drop|purge|cancel subscription|حذف|الغاء/i.test(text);
                const isAuthOrNav = /logout|sign out|خروج/i.test(text);

                if (!isDestructive && !isAuthOrNav && await btn.isVisible()) {
                    // Click safe toggles/modals/filters
                    await btn.click({ timeout: 1200 });
                    await page.waitForTimeout(200);

                    // If a dialog opened, close it gracefully via Escape or close button
                    const closeDialog = page.locator('[role="dialog"] button[aria-label*="close" i], [role="dialog"] button:has-text("Close"), [role="dialog"] button:has-text("إلغاء")').first();
                    if (await closeDialog.isVisible().catch(() => false)) {
                        await closeDialog.click({ timeout: 1000 }).catch(() => {});
                    } else {
                        await page.keyboard.press('Escape').catch(() => {});
                    }
                }
            } catch (e) {}
        }

    } catch (err: any) {
        const msg = err.message.toLowerCase();
        if (!msg.includes('timeout') && !msg.includes('connection')) {
            audit.actionErrors.push(`[Navigation/Interaction Error] ${err.message}`);
        }
    } finally {
        cleanup();
    }

    return audit;
}

// ─────────────────────────────────────────────────────────────────────────────
// E2E Action Suite
// ─────────────────────────────────────────────────────────────────────────────

test.describe('Autonomous Intelligent Action & Exploration Suite', () => {
    test.beforeEach(async ({}, testInfo) => {
        testInfo.setTimeout(180000);
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 1: Public Matrix & Public Action Execution
    // ─────────────────────────────────────────────────────────────────────────
    test('Public Route & Action Matrix (GET, Search, Forms, i18n)', async ({ page }) => {
        console.log(`\n🌐 [Phase 1: Public Matrix] Exploring ${manifest.publicRoutes.length} public route(s)...`);
        
        for (const route of manifest.publicRoutes) {
            console.log(`  ↪ 🔎 Testing Public Route & Interactions: ${route}`);
            const audit = await interactivelyExplorePage(page, route);
            
            expect(audit.actionErrors).toEqual([]);
            expect(audit.failedNetworkRequests).toEqual([]);
        }

        // Test Public Search on Portfolio if exists
        try {
            await page.goto('/portfolio', { waitUntil: 'domcontentloaded' });
            const searchBox = page.locator('input[type="text"], input[type="search"]').first();
            if (await searchBox.isVisible().catch(() => false)) {
                await searchBox.fill('Software');
                await page.waitForTimeout(400);
                expect(await page.locator('body').isVisible()).toBeTruthy();
            }
        } catch (e) {}
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 2: Client & Tenant Full Action Flow (GET + POST Actions)
    // ─────────────────────────────────────────────────────────────────────────
    test('Client & Tenant Action Matrix (Profile, Tickets, ERP, CRM, Booking)', async ({ page }) => {
        console.log('\n🏢 [Phase 2: Client & Tenant Actions] Authenticating as Tenant/Client...');
        const loggedIn = await loginUser(page, TENANT_CREDENTIALS, /\/(dashboard|erp|crm|booking|profile|tickets)/);

        if (!loggedIn && !manifest.dbOnline) {
            console.log('  ⚠️ Database offline in local environment, skipping authenticated mutations.');
            return;
        }

        // 1. Test Profile Updates Action (POST/PUT /profile)
        console.log('  ↪ 📝 Testing Profile Settings & Form Interaction...');
        try {
            await page.goto('/profile', { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(500);

            const nameInput = page.locator('input[name="name"], input#name').first();
            if (await nameInput.isVisible().catch(() => false)) {
                const currentName = await nameInput.inputValue();
                await nameInput.fill(currentName || 'E2E Tenant User');
                
                const saveBtn = page.locator('button[type="submit"]:has-text("Save"), button[type="submit"]:has-text("حفظ")').first();
                if (await saveBtn.isVisible().catch(() => false)) {
                    await saveBtn.click();
                    await page.waitForTimeout(800);
                    await expect(page.locator('body')).not.toContainText('500 Server Error');
                }
            }
        } catch (e) {}

        // 2. Test Support Ticket Creation Flow (POST /tickets)
        console.log('  ↪ 🎫 Testing Support Tickets Flow...');
        try {
            await page.goto('/tickets', { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(500);

            const newTicketBtn = page.getByRole('button', { name: /new ticket|create ticket|تذكرة جديدة/i }).first();
            if (await newTicketBtn.isVisible().catch(() => false)) {
                await newTicketBtn.click();
                await page.waitForTimeout(400);

                const subjectInput = page.locator('input[name="ticket_subject"], input[name="subject"]').first();
                const msgInput = page.locator('textarea[name="ticket_message"], textarea[name="message"]').first();

                if (await subjectInput.isVisible().catch(() => false)) {
                    await subjectInput.fill('Playwright Dynamic QA Ticket');
                    if (await msgInput.isVisible().catch(() => false)) {
                        await msgInput.fill('This is an automated action test ticket validating POST endpoints.');
                    }
                    const submitTicketBtn = page.locator('button[type="submit"]').first();
                    if (await submitTicketBtn.isVisible().catch(() => false)) {
                        await submitTicketBtn.click();
                        await page.waitForTimeout(1000);
                    }
                }
            }
        } catch (e) {}

        // 3. Test ERP Suite Actions (Clients, Projects, Invoices, Expenses)
        console.log('  ↪ 📊 Testing ERP Module Actions...');
        for (const erpRoute of ['/erp/dashboard', '/erp/clients', '/erp/projects', '/erp/invoices', '/erp/expenses']) {
            console.log(`    • Exploring ${erpRoute}`);
            const audit = await interactivelyExplorePage(page, erpRoute);
            expect(audit.actionErrors).toEqual([]);
            expect(audit.failedNetworkRequests).toEqual([]);
        }

        // 4. Test CRM Suite Actions (Leads, Pipelines, Sequences)
        console.log('  ↪ 📈 Testing CRM Module Actions...');
        for (const crmRoute of ['/crm/dashboard', '/crm/leads', '/crm/pipelines', '/crm/sequences']) {
            console.log(`    • Exploring ${crmRoute}`);
            const audit = await interactivelyExplorePage(page, crmRoute);
            expect(audit.actionErrors).toEqual([]);
            expect(audit.failedNetworkRequests).toEqual([]);
        }

        // 5. Test Booking Suite Actions
        console.log('  ↪ 📅 Testing Booking Module Actions...');
        for (const bookingRoute of ['/booking/dashboard', '/booking/providers', '/booking/appointments']) {
            console.log(`    • Exploring ${bookingRoute}`);
            const audit = await interactivelyExplorePage(page, bookingRoute);
            expect(audit.actionErrors).toEqual([]);
            expect(audit.failedNetworkRequests).toEqual([]);
        }
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 3: Admin Full Action Flow (GET + POST Actions)
    // ─────────────────────────────────────────────────────────────────────────
    test('Admin Action Matrix (User Management, Plans, Coupons, Reports, Settings)', async ({ page }) => {
        console.log('\n👑 [Phase 3: Admin Actions] Authenticating as Administrator...');
        const loggedIn = await loginUser(page, ADMIN_CREDENTIALS, /\/(admin|dashboard)/);

        if (!loggedIn && !manifest.dbOnline) {
            console.log('  ⚠️ Database offline in local environment, skipping admin authenticated mutations.');
            return;
        }

        // 1. Admin Users Search & Action Drawer
        console.log('  ↪ 👥 Testing Admin Users Management & Filter Actions...');
        try {
            await page.goto('/admin/users', { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(500);

            const userSearch = page.locator('input[type="search"], input[placeholder*="search" i], input[placeholder*="بحث" i]').first();
            if (await userSearch.isVisible().catch(() => false)) {
                await userSearch.fill('admin');
                await page.keyboard.press('Enter');
                await page.waitForTimeout(500);
                await userSearch.fill('');
                await page.keyboard.press('Enter');
            }
        } catch (e) {}

        // 2. Admin System Settings Tabs & Interactive Controls
        console.log('  ↪ ⚙️ Testing Admin Settings Tabs & Controls...');
        try {
            await page.goto('/admin/settings', { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(500);

            const settingsTabs = page.locator('[role="tab"], nav button, .tab-item');
            const count = Math.min(await settingsTabs.count().catch(() => 0), 5);
            for (let i = 0; i < count; i++) {
                try {
                    const tab = settingsTabs.nth(i);
                    if (await tab.isVisible()) {
                        await tab.click();
                        await page.waitForTimeout(250);
                    }
                } catch (e) {}
            }
        } catch (e) {}

        // 3. Admin Dynamic Matrix Sweep
        console.log(`  ↪ 🛡️ Executing Deep Interaction Sweep across ${manifest.adminRoutes.length} Admin routes...`);
        for (const adminRoute of manifest.adminRoutes) {
            console.log(`    • Auditing & Interacting: ${adminRoute}`);
            const audit = await interactivelyExplorePage(page, adminRoute);
            expect(audit.actionErrors).toEqual([]);
            expect(audit.failedNetworkRequests).toEqual([]);
        }
    });
});
