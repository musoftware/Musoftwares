import { test, expect } from '@playwright/test';

test.describe('Client Projects Experience', () => {
    test.beforeEach(async ({ page }) => {
        page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
        page.on('pageerror', err => console.log('BROWSER ERROR:', err.message));
        await page.goto('/login');
        await page.waitForSelector('input[name="email"]');
        await page.fill('input[name="email"]', 'client@musoftwares.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard|\/projects/);
    });

    test('should load projects list, navigate to project dashboard, and interact with widgets', async ({ page }) => {
        // 1. Go to projects index
        await page.goto('/projects');
        await expect(page).toHaveTitle(/Projects/i);
        
        // Verify seeded project is in the list
        const projectLink = page.locator('text=E2E Test Client Project').first();
        await expect(projectLink).toBeVisible();
        
        // Click and go to project dashboard details
        await projectLink.click();
        await page.waitForURL(/\/projects\/\d+/);

        // 2. Verify dashboard widgets
        // circular progress completion circle
        const progressWidget = page.locator('text=Completion Progress');
        await expect(progressWidget).toBeVisible();

        // active team members list
        const teamWidget = page.locator('text=Active Team');
        await expect(teamWidget).toBeVisible();

        // approvals center
        const approvalsWidget = page.locator('text=Awaiting Approval');
        await expect(approvalsWidget).toBeVisible();
        await expect(page.locator('text=E2E Deliverable Task')).toBeVisible();

        // support channels
        const supportWidget = page.locator('text=Support Tickets');
        await expect(supportWidget).toBeVisible();
        await expect(page.locator('text=E2E Urgent Ticket')).toBeVisible();

        // activity feed
        const activityWidget = page.locator('text=Recent Updates');
        await expect(activityWidget).toBeVisible();
    });

    test('should open project board, view deliverable details, and submit approvals / comments', async ({ page }) => {
        // Go to projects index and select the E2E project
        await page.goto('/projects');
        await page.locator('text=E2E Test Client Project').first().click();
        await page.waitForURL(/\/projects\/\d+/);

        // Click Board tab/link if any, or directly load the board
        // Click on the day board card to open details dialog
        const cardTitle = page.locator('text=E2E Deliverable Task').first();
        await cardTitle.click();

        // Wait for details Dialog/Modal to open
        const dialog = page.locator('[role="dialog"]');
        await expect(dialog).toBeVisible();

        // Verify Client Approval section is rendered inside the modal
        const approvalSection = dialog.locator('text=Client Approval Sign-off');
        await expect(approvalSection).toBeVisible();

        // Assert Approve Deliverable button is present
        const approveBtn = dialog.locator('button:has-text("Approve Deliverable")');
        await expect(approveBtn).toBeVisible();

        // Click Request Revision to toggle the feedback form
        const revisionBtn = dialog.locator('button:has-text("Request Revision")');
        await expect(revisionBtn).toBeVisible();
        await revisionBtn.click();

        // Feedback textarea should show up
        const textarea = dialog.locator('textarea[placeholder*="Explain the required revisions"]');
        await expect(textarea).toBeVisible();
        await textarea.fill('Needs to look more premium.');

        // Cancel the revision request
        const cancelBtn = dialog.locator('button:has-text("Cancel")');
        await expect(cancelBtn).toBeVisible();
        await cancelBtn.click();
        await expect(textarea).not.toBeVisible();
    });

    test('should load unified tasks aggregator page and show tasks list', async ({ page }) => {
        // Go to unified tasks aggregator
        await page.goto('/projects/tasks');
        await expect(page).toHaveTitle(/Tasks/i);

        // Check search/filter list exists
        const searchInput = page.locator('input[placeholder*="Search"]');
        await expect(searchInput).toBeVisible();

        // E2E task should be listed in the aggregator
        await expect(page.locator('text=E2E Deliverable Task').first()).toBeVisible();
    });

    test('should allow creating a new project and activating AI', async ({ page }) => {
        await page.goto('/projects');
        
        const newProjectBtn = page.locator('text=New Project').first();
        await newProjectBtn.click();
        await page.waitForURL(/\/projects\/create-new/);

        await page.fill('input[placeholder*="e.g., E-Commerce App"]', 'Playwright E2E AI Project');
        await page.fill('textarea[placeholder*="Describe your project"]', 'Initial details');
        await page.click('button[type="submit"]');

        await page.waitForURL(/\/projects\/\d+/);
        await expect(page.locator('h1')).toContainText('Playwright E2E AI Project');

        const activateBanner = page.locator('text=Organize Project with AI');
        await expect(activateBanner).toBeVisible();

        const activateBtn = page.locator('button:has-text("Organize Project with AI")');
        await expect(activateBtn).toBeVisible();
        await activateBtn.click();
        
        await expect(page.locator('text=System: AI Project Manager activated successfully!')).toBeVisible();
    });
});
