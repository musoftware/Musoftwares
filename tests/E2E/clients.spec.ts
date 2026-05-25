import { test, expect } from '@playwright/test';

test.describe('Admin Clients Page', () => {
    test.beforeEach(async ({ page }) => {
        // Assume user is already logged in or we need to login
        // For local testing on an active session, you can reuse state,
        // but for now let's just attempt to load the page and see if we get redirected to login.
        // We will try to login using a known test user if redirected.
        
        await page.goto('/login');
        
        // Check if we are on the login page by looking for the email input
        if (await page.isVisible('input[name="email"]')) {
            await page.fill('input[name="email"]', 'admin@example.com'); // Placeholder: change to actual local test admin
            await page.fill('input[name="password"]', 'password');
            await page.click('button[type="submit"]');
            await page.waitForURL('/dashboard');
        }

        // Navigate to the clients page
        await page.goto('/admin/users');
    });

    test('should open ClientActionsSheet when clicking on a client and display correct links', async ({ page }) => {
        // Wait for the clients table to load
        await page.waitForSelector('table');
        
        // Click on the first client name in the table
        // This assumes the name is clickable and opens the sheet
        const firstClientRow = page.locator('tbody tr').first();
        const clientNameLink = firstClientRow.locator('td').nth(1).locator('button, a').first();
        
        await clientNameLink.click();
        
        // Wait for the sheet to appear
        const sheet = page.locator('[role="dialog"]');
        await expect(sheet).toBeVisible();
        
        // Check if the Finance & Billing links match the old system
        await expect(sheet.getByRole('link', { name: 'New Invoice' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Receive Money' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Send Money' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Refund Money' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Invoices' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Swap Budgets' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'All Transactions' })).toBeVisible();
        
        // Check Workflow links
        await expect(sheet.getByRole('link', { name: 'Projects' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Assign Tasks' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Notes' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'User Files' })).toBeVisible();
        
        // Check Account links
        await expect(sheet.getByRole('link', { name: 'View Profile' })).toBeVisible();
        await expect(sheet.getByRole('link', { name: 'Edit Client' })).toBeVisible();
        
        // Check Account buttons
        await expect(sheet.getByRole('button', { name: 'Login As' })).toBeVisible();
        await expect(sheet.getByRole('button', { name: 'Reset Password' })).toBeVisible();
    });
});
