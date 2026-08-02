import { test, expect } from '@playwright/test';

const E2E_PROJECT_NAME = 'E2E Test Client Project';

test.describe('Client Projects Experience', () => {
    test.beforeEach(async ({ page }) => {
        page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
        page.on('pageerror', err => console.log('BROWSER ERROR:', err.message));
        await page.goto('/login');
        await page.waitForSelector('#email');
        await page.fill('#email', 'client@musoftwares.com');
        await page.fill('#password', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard|\/projects/);
    });

    test('should load projects list, navigate to project dashboard, and interact with widgets', async ({ page }) => {
        // 1. Go to projects index
        await page.goto('/projects');
        await expect(page).toHaveTitle(/Projects/i);
        
        // Verify seeded project is in the list
        const projectLink = page.locator(`a:has-text("${E2E_PROJECT_NAME}")`).first();
        await expect(projectLink).toBeVisible({ timeout: 10000 });
        
        // Get project URL and navigate directly to discussions tab
        const href = await projectLink.getAttribute('href');
        await page.goto(href + '?tab=discussions');
        await page.waitForLoadState('networkidle');

        // Verify Chat tab text area is visible
        const chatInput = page.locator('textarea').first();
        await expect(chatInput).toBeVisible({ timeout: 10000 });

        // Support channels (right column)
        const supportWidget = page.locator('text=Support Channels').first();
        await expect(supportWidget).toBeVisible();
        await expect(page.locator('text=E2E Urgent Ticket').first()).toBeVisible();

        // Activity timeline
        const activityWidget = page.locator('text=Activity Timeline').first();
        await expect(activityWidget).toBeVisible();
    });

    test('should send a chat message and verify it appears in the thread', async ({ page }) => {
        // Navigate directly to the E2E project discussions tab
        await page.goto('/projects');
        const projectLink = page.locator(`a:has-text("${E2E_PROJECT_NAME}")`).first();
        await expect(projectLink).toBeVisible({ timeout: 10000 });
        const href = await projectLink.getAttribute('href');
        await page.goto(href + '?tab=discussions');
        await page.waitForLoadState('networkidle');

        // Type a message in the textarea
        const textarea = page.locator('textarea').first();
        await expect(textarea).toBeVisible({ timeout: 10000 });
        await textarea.fill('Hello from E2E test chat!');

        // Click Send button (submit button inside the form)
        const sendBtn = page.locator('form button[type="submit"]').first();
        await sendBtn.click();

        // Verify the message is added to the chat feed
        await expect(page.locator('text=Hello from E2E test chat!').first()).toBeVisible({ timeout: 10000 });
    });

    test('should load unified tasks aggregator page and show tasks list', async ({ page }) => {
        // Go to unified tasks aggregator
        await page.goto('/projects/tasks');
        await expect(page).toHaveTitle(/Tasks/i);

        // Check filter select exists
        const selectTrigger = page.locator('button[role="combobox"]').first();
        await expect(selectTrigger).toBeVisible();

        // E2E task should be listed in the aggregator
        await expect(page.locator('text=E2E Deliverable Task').first()).toBeVisible();
    });

    test('should allow creating a new project and activating AI', async ({ page }) => {
        await page.goto('/projects');
        
        const newProjectBtn = page.locator('a[href*="/projects/create-new"]').first();
        await newProjectBtn.click();
        await page.waitForURL(/\/projects\/create-new/);

        await page.fill('input[placeholder*="e.g., E-Commerce App"]', 'Playwright E2E AI Project');
        await page.fill('textarea[placeholder*="Describe your project"]', 'Initial details');
        await page.click('button[type="submit"]');

        await page.waitForURL(/\/projects\/\d+/);
        await expect(page.locator('h1')).toContainText('Playwright E2E AI Project');

        const activateBanner = page.locator('h2:has-text("Organize Project with AI")').first();
        await expect(activateBanner).toBeVisible();

        const activateBtn = page.locator('button:has-text("Organize Project with AI")').first();
        await expect(activateBtn).toBeVisible();
        await activateBtn.click();
        
        await expect(page.locator('text=AI Project Manager activated successfully!').first()).toBeVisible({ timeout: 10000 });
    });
});
