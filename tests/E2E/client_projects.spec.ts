import { test, expect } from '@playwright/test';

const E2E_PROJECT_NAME = 'E2E Test Client Project';

test.describe('Client Projects AI Workspace Experience', () => {
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

    test('should load projects list and open AI workspace chat directly', async ({ page }) => {
        await page.goto('/projects');
        await expect(page).toHaveTitle(/Projects/i);

        const projectLink = page.locator(`a:has-text("${E2E_PROJECT_NAME}")`).first();
        await expect(projectLink).toBeVisible({ timeout: 10000 });

        // Navigate to project show page — chat is now the primary interface
        const href = await projectLink.getAttribute('href');
        await page.goto(href!);
        await page.waitForLoadState('networkidle');

        // Chat input area should be visible directly
        const chatInput = page.locator('textarea').first();
        await expect(chatInput).toBeVisible({ timeout: 10000 });

        // Project Stage widget should be visible
        await expect(page.locator('text=حالة المشـــــروع').first()).toBeVisible();
    });

    test('should send a message in AI workspace chat and verify it appears', async ({ page }) => {
        await page.goto('/projects');
        const projectLink = page.locator(`a:has-text("${E2E_PROJECT_NAME}")`).first();
        await expect(projectLink).toBeVisible({ timeout: 10000 });
        const href = await projectLink.getAttribute('href');
        await page.goto(href!);
        await page.waitForLoadState('networkidle');

        const textarea = page.locator('textarea').first();
        await expect(textarea).toBeVisible({ timeout: 10000 });
        await textarea.fill('Hello AI Workspace! Add booking module to my project.');

        const sendBtn = page.locator('form button[type="submit"]').first();
        await sendBtn.click();

        // Message should appear in chat feed
        await expect(page.locator('text=Hello AI Workspace! Add booking module to my project.').first()).toBeVisible({ timeout: 10000 });
    });

    test('should load unified tasks aggregator page', async ({ page }) => {
        await page.goto('/projects/tasks');
        await expect(page).toHaveTitle(/Tasks/i);

        const selectTrigger = page.locator('button[role="combobox"]').first();
        await expect(selectTrigger).toBeVisible();

        await expect(page.locator('text=E2E Deliverable Task').first()).toBeVisible();
    });

    test('should allow creating a new project and activating AI Manager', async ({ page }) => {
        await page.goto('/projects');

        const newProjectBtn = page.locator('a[href*="/projects/create-new"]').first();
        await newProjectBtn.click();
        await page.waitForURL(/\/projects\/create-new/);

        await page.fill('input[placeholder*="e.g., E-Commerce App"]', 'Playwright E2E AI Workspace Project');
        await page.fill('textarea[placeholder*="Describe your project"]', 'Initial details for AI workspace');
        await page.click('button[type="submit"]');

        await page.waitForURL(/\/projects\/\d+/);
        await expect(page.locator('h1')).toContainText('Playwright E2E AI Workspace Project');

        // AI Manager status badge or activate button
        const activateBtn = page.locator('button:has-text("Activate AI Manager")').first();
        await expect(activateBtn).toBeVisible();
        await activateBtn.click();

        // Toast notification appears on success
        await expect(page.locator('text=AI Project Manager activated successfully!').first()).toBeVisible({ timeout: 10000 });
    });
});
