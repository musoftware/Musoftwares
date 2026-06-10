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
    await page.waitForURL(/\/(erp|booking|dashboard)/);
}

// ─────────────────────────────────────────────
// Booking Suite
// ─────────────────────────────────────────────

test.describe('Booking — E2E Tests', () => {

    test.beforeEach(async ({ page }) => {
        await loginTenant(page);
    });

    test('Booking Dashboard loads without errors', async ({ page }) => {
        await page.goto('/booking/dashboard');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).not.toContainText('Whoops!');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('Tenant can navigate to Providers page', async ({ page }) => {
        await page.goto('/booking/providers');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/booking\/providers/);
    });

    test('Tenant can navigate to Appointments page', async ({ page }) => {
        await page.goto('/booking/appointments');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/booking\/appointments/);
    });

    test('Tenant can navigate to Exceptions page', async ({ page }) => {
        await page.goto('/booking/exceptions');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/booking\/exceptions/);
    });
});
