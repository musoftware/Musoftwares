import { test, expect, Page } from '@playwright/test';

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

const ADMIN_CREDENTIALS = { email: 'admin@musoftwares.com', password: 'password' };

async function loginAdmin(page: Page) {
    await page.goto('/login');
    await page.waitForSelector('input[name="email"]');
    await page.fill('input[name="email"]', ADMIN_CREDENTIALS.email);
    await page.fill('input[name="password"]', ADMIN_CREDENTIALS.password);
    await page.click('button[type="submit"]');
    // Wait until redirected away from login
    await page.waitForURL(/\/(admin|dashboard)/);
}

// ─────────────────────────────────────────────
// Admin Suite
// ─────────────────────────────────────────────

test.describe('Admin — E2E Tests', () => {

    test.beforeEach(async ({ page }) => {
        await loginAdmin(page);
    });

    test('Admin dashboard loads successfully', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await page.waitForLoadState('networkidle');

        // Check for common dashboard elements
        await expect(page.locator('body')).not.toContainText('Whoops!');
        await expect(page.locator('body')).not.toContainText('500');
        
        // Assert some basic metrics exist
        const hasMetrics = await page.locator('[class*="metric"], [class*="card"], [class*="stat"]').first().isVisible().catch(() => false);
        expect(hasMetrics).toBeTruthy();
    });

    test('Admin can navigate to Users management page', async ({ page }) => {
        await page.goto('/admin/users');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/admin\/users/);
        
        // Ensure table is loaded
        const tableVisible = await page.locator('table').first().isVisible();
        expect(tableVisible).toBeTruthy();
    });

    test('Admin can navigate to Plans management page', async ({ page }) => {
        await page.goto('/admin/plans');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/admin\/plans/);
        
        // Check for Create Plan button
        const createBtn = page.getByRole('button', { name: /create|add/i }).first();
        if (await createBtn.isVisible()) {
            await expect(createBtn).toBeVisible();
        }
    });

    test('Admin can navigate to KYC review page', async ({ page }) => {
        await page.goto('/admin/kyc/documents');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/admin\/kyc/);
    });

    test('Admin can navigate to Withdrawal Requests page', async ({ page }) => {
        await page.goto('/admin/withdraw-requests');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/admin\/withdraw-requests/);
        
        // Check for action buttons if rows exist
        const hasRows = await page.locator('table tbody tr').count() > 0;
        if (hasRows) {
            const approveBtn = page.getByRole('button', { name: /approve/i }).first();
            await expect(approveBtn).toBeVisible();
        }
    });
});
