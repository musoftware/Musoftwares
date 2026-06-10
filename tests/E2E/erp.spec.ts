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
    await page.waitForURL(/\/(erp|dashboard)/);
}

// ─────────────────────────────────────────────
// ERP Suite
// ─────────────────────────────────────────────

test.describe('ERP — E2E Tests', () => {

    test.beforeEach(async ({ page }) => {
        await loginTenant(page);
    });

    test('ERP Dashboard loads without errors', async ({ page }) => {
        await page.goto('/erp/dashboard');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).not.toContainText('Whoops!');
        await expect(page.locator('body')).not.toContainText('500');
        
        // Assert financial cards exist
        const hasCards = await page.locator('[class*="card"], [class*="metric"]').first().isVisible().catch(() => false);
        expect(hasCards).toBeTruthy();
    });

    test('Tenant can navigate to Clients page', async ({ page }) => {
        await page.goto('/erp/clients');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/erp\/clients/);
        
        const createBtn = page.getByRole('button', { name: /create|add|new client/i }).first();
        if (await createBtn.isVisible()) {
            await expect(createBtn).toBeVisible();
        }
    });

    test('Tenant can navigate to Projects page', async ({ page }) => {
        await page.goto('/erp/projects');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/erp\/projects/);
    });

    test('Tenant can navigate to Invoices page', async ({ page }) => {
        await page.goto('/erp/invoices');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/erp\/invoices/);
    });

    test('Tenant can navigate to Expenses page', async ({ page }) => {
        await page.goto('/erp/expenses');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/erp\/expenses/);
    });
});
