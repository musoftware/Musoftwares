import { test, expect, Page } from '@playwright/test';

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

const TENANT_CREDENTIALS = { email: 'tenant@musoftwares.com', password: 'password' }; // Assuming seeded

async function loginTenant(page: Page) {
    await page.goto('/login');
    await page.waitForSelector('input[name="email"]');
    await page.fill('input[name="email"]', TENANT_CREDENTIALS.email);
    await page.fill('input[name="password"]', TENANT_CREDENTIALS.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(erp|crm|dashboard)/);
}

// ─────────────────────────────────────────────
// CRM Suite
// ─────────────────────────────────────────────

test.describe('CRM — E2E Tests', () => {

    test.beforeEach(async ({ page }) => {
        await loginTenant(page);
    });

    test('CRM Dashboard loads without errors', async ({ page }) => {
        // Fallback to leads if no CRM dashboard route
        await page.goto('/crm/dashboard').catch(() => page.goto('/crm/leads'));
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).not.toContainText('Whoops!');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('Tenant can navigate to Pipelines page', async ({ page }) => {
        await page.goto('/crm/pipelines');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/crm\/pipelines/);
    });

    test('Tenant can navigate to Leads page', async ({ page }) => {
        await page.goto('/crm/leads');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/crm\/leads/);
        
        // Ensure table is loaded
        const tableVisible = await page.locator('table').first().isVisible().catch(() => false);
        expect(tableVisible).toBeTruthy();
    });

    test('Tenant can navigate to Sequences page', async ({ page }) => {
        await page.goto('/crm/sequences');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/crm\/sequences/);
    });

    test('Tenant can navigate to Campaigns page', async ({ page }) => {
        await page.goto('/crm/campaigns');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/crm\/campaigns/);
    });
});
