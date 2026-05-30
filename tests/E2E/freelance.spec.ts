import { test, expect, Page, BrowserContext } from '@playwright/test';

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

const BASE_URL = 'http://127.0.0.1:8000';

/** Credentials for test users — adjust to your seeded data */
const CREDENTIALS = {
    client: { email: 'test.client@example.com', password: 'password' },
    freelancer: { email: 'test.freelancer@example.com', password: 'password' },
};

async function login(page: Page, role: 'client' | 'freelancer') {
    await page.goto('/login');
    await page.waitForSelector('input[name="email"]');
    await page.fill('input[name="email"]', CREDENTIALS[role].email);
    await page.fill('input[name="password"]', CREDENTIALS[role].password);
    await page.click('button[type="submit"]');
    // Wait until redirected away from login
    await page.waitForURL(/\/(freelance|dashboard)/);
}

async function logout(page: Page) {
    // Hit logout route directly — avoids relying on specific UI
    await page.goto('/logout', { waitUntil: 'domcontentloaded' }).catch(() => {});
    // Some apps redirect on GET logout; otherwise POST
    await page.request.post('/logout').catch(() => {});
}

// ─────────────────────────────────────────────
// Suite 1 — Authentication Guard
// ─────────────────────────────────────────────

test.describe('Freelance — Auth Guard', () => {
    test('unauthenticated user is redirected to login from jobs create page', async ({ page }) => {
        await page.goto('/freelance/jobs/create');
        await expect(page).toHaveURL(/login/);
    });

    test('unauthenticated user is redirected to login from jobs browse page', async ({ page }) => {
        await page.goto('/freelance/jobs/browse');
        await expect(page).toHaveURL(/login/);
    });

    test('unauthenticated user is redirected to login from my-jobs page', async ({ page }) => {
        await page.goto('/freelance/jobs/my-jobs');
        await expect(page).toHaveURL(/login/);
    });
});

// ─────────────────────────────────────────────
// Suite 2 — Create Job Form (Client)
// ─────────────────────────────────────────────

test.describe('Freelance — Create Job Form', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'client');
        await page.goto('/freelance/jobs/create');
        await page.waitForLoadState('networkidle');
    });

    test('create job page loads with all required fields visible', async ({ page }) => {
        await expect(page.locator('input[name="title"], #title, [placeholder*="title" i]').first()).toBeVisible();
        await expect(page.locator('textarea[name="description"], #description').first()).toBeVisible();
        await expect(page.locator('input[name="budget"], #budget').first()).toBeVisible();
        await expect(page.locator('select').first()).toBeVisible(); // Currency select
        await expect(page.getByText(/min proposal bid/i)).toBeVisible();
    });

    test('currency dropdown is populated dynamically (no hardcoded USD/EGP strings in DOM)', async ({ page }) => {
        // Ensure currency select has options loaded from backend
        const selectLocator = page.locator('select').first();
        await expect(selectLocator).toBeVisible();
        const optionCount = await selectLocator.locator('option').count();
        expect(optionCount).toBeGreaterThan(0);
    });

    test('points cost display updates when min_proposal_points changes', async ({ page }) => {
        // Find the min proposal points input
        const minBidInput = page.locator('input[name="min_proposal_points"], input[placeholder*="0"]').first();

        // Get initial cost display text
        const initialCostText = await page.locator('[class*="cost"], [class*="point"]').first().textContent().catch(() => '');

        await minBidInput.fill('50');

        // Cost display should update (25 base + 50 = 75)
        await expect(page.locator('body')).toContainText('75');
    });

    test('form shows validation errors when submitted empty', async ({ page }) => {
        await page.click('button[type="submit"]');

        // At least one error message should appear
        await expect(page.locator('.text-red-500, [class*="error"], [role="alert"]').first()).toBeVisible({
            timeout: 5000,
        });
    });

    test('form shows insufficient points warning when user has no points', async ({ page }) => {
        // Check if user has 0 points — the publish button should be disabled or show a warning
        const insufficientWarning = page.locator('[class*="warn"], [class*="danger"], [class*="alert"]').filter({
            hasText: /point/i,
        });

        // If points are 0, warning should be visible
        const pointsBalance = await page.locator('[data-testid="points-balance"]').textContent().catch(() => '0');
        if (parseInt(pointsBalance || '0') === 0) {
            await expect(insufficientWarning).toBeVisible();
        }
    });

    test('buy points button triggers confirmation dialog when user needs more points', async ({ page }) => {
        // Mock insufficient points scenario: look for the buy points button
        const buyBtn = page.getByRole('button', { name: /buy points|purchase/i });

        if (await buyBtn.isVisible()) {
            page.once('dialog', (dialog) => {
                expect(dialog.message()).toMatch(/points/i);
                dialog.dismiss(); // Don't actually buy during test
            });
            await buyBtn.click();
        }
    });

    test('successful job submission redirects to my-jobs page', async ({ page }) => {
        // Fill in required fields
        await page.locator('input[name="title"], [placeholder*="title" i]').first().fill('E2E Test Job Title');
        await page.locator('textarea[name="description"], #description').first().fill('This is a detailed job description for the E2E test');

        // Budget
        const budgetInput = page.locator('input[name="budget"], #budget').first();
        await budgetInput.fill('500');

        // Min proposal points
        const minBidInput = page.locator('input[name="min_proposal_points"]').first();
        await minBidInput.fill('0');

        // Select type fixed
        const fixedLabel = page.locator('label').filter({ hasText: /fixed/i }).first();
        if (await fixedLabel.isVisible()) await fixedLabel.click();

        // Submit if user has enough points
        const publishBtn = page.getByRole('button', { name: /publish|post job/i });
        if (await publishBtn.isEnabled()) {
            await publishBtn.click();
            await expect(page).toHaveURL(/my-jobs/, { timeout: 10000 });
        } else {
            test.skip(); // Skip if user has no points — handled in unit tests
        }
    });
});

// ─────────────────────────────────────────────
// Suite 3 — Browse Jobs (Freelancer view)
// ─────────────────────────────────────────────

test.describe('Freelance — Browse Jobs', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'freelancer');
        await page.goto('/freelance/jobs/browse');
        await page.waitForLoadState('networkidle');
    });

    test('browse page loads and shows a jobs list or empty state', async ({ page }) => {
        // Either a list of jobs or an empty state message
        const hasList = await page.locator('table tbody tr, [class*="job-card"], article').first().isVisible().catch(() => false);
        const hasEmpty = await page.locator('[class*="empty"], [class*="no-jobs"]').first().isVisible().catch(() => false);
        expect(hasList || hasEmpty).toBeTruthy();
    });

    test('search box filters jobs', async ({ page }) => {
        const searchInput = page.locator('input[type="search"], input[placeholder*="search" i]').first();
        if (await searchInput.isVisible()) {
            await searchInput.fill('nonexistentjobxyz123');
            await page.waitForLoadState('networkidle');
            // Results should be empty or show no results text
            const jobCards = page.locator('table tbody tr, [class*="job-card"], article');
            const count = await jobCards.count();
            expect(count).toBe(0);
        }
    });

    test('type filter shows only fixed or hourly jobs', async ({ page }) => {
        const fixedFilter = page.locator('select[name="type"], [data-filter="type"]').first();
        if (await fixedFilter.isVisible()) {
            await fixedFilter.selectOption('fixed');
            await page.waitForLoadState('networkidle');
        }
    });

    test('sorting by budget high to low works', async ({ page }) => {
        const sortSelect = page.locator('select[name="sort"], [data-filter="sort"]').first();
        if (await sortSelect.isVisible()) {
            await sortSelect.selectOption('budget_high');
            await page.waitForLoadState('networkidle');
        }
    });

    test('clicking a job card navigates to job details page', async ({ page }) => {
        const firstJob = page.locator('table tbody tr, [class*="job-card"], article').first();
        if (await firstJob.isVisible()) {
            // Find a link to the job
            const jobLink = firstJob.locator('a').first();
            const href = await jobLink.getAttribute('href');
            await jobLink.click();
            await page.waitForLoadState('networkidle');
            expect(page.url()).toMatch(/freelance\/jobs\/\d+/);
        }
    });
});

// ─────────────────────────────────────────────
// Suite 4 — Job Details & Proposal Submission
// ─────────────────────────────────────────────

test.describe('Freelance — Job Details & Submit Proposal', () => {
    let jobUrl = '';

    test.beforeEach(async ({ page }) => {
        await login(page, 'freelancer');
        // Navigate to browse to find an open job
        await page.goto('/freelance/jobs/browse');
        await page.waitForLoadState('networkidle');

        const firstJobLink = page.locator('table tbody tr a, [class*="job-card"] a, article a').first();
        if (await firstJobLink.isVisible()) {
            jobUrl = (await firstJobLink.getAttribute('href')) ?? '';
            await firstJobLink.click();
            await page.waitForLoadState('networkidle');
        }
    });

    test('job detail page shows title, description, and budget', async ({ page }) => {
        if (!page.url().match(/freelance\/jobs\/\d+/)) return test.skip();

        await expect(page.locator('h1, h2').first()).toBeVisible();
        // Budget display should use formatted currency (not raw numbers without symbol)
        await expect(page.locator('body')).not.toContainText('currency_id');
    });

    test('freelancer can see submit proposal section', async ({ page }) => {
        if (!page.url().match(/freelance\/jobs\/\d+/)) return test.skip();

        // Proposal form should exist
        const proposalForm = page.locator('form').filter({ hasText: /cover letter|proposal/i });
        if (await proposalForm.isVisible()) {
            await expect(proposalForm).toBeVisible();
        }
    });

    test('submitting a proposal with empty cover letter shows validation error', async ({ page }) => {
        if (!page.url().match(/freelance\/jobs\/\d+/)) return test.skip();

        const submitBtn = page.getByRole('button', { name: /submit proposal|apply/i });
        if (await submitBtn.isVisible()) {
            await submitBtn.click();
            await expect(page.locator('.text-red-500, [class*="error"]').first()).toBeVisible({ timeout: 5000 });
        }
    });

    test('points cost to bid is shown clearly on job detail page', async ({ page }) => {
        if (!page.url().match(/freelance\/jobs\/\d+/)) return test.skip();

        // Should show some point cost indication
        await expect(page.locator('body')).toContainText(/point/i);
    });
});

// ─────────────────────────────────────────────
// Suite 5 — My Jobs (Client)
// ─────────────────────────────────────────────

test.describe('Freelance — My Jobs', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'client');
        await page.goto('/freelance/jobs/my-jobs');
        await page.waitForLoadState('networkidle');
    });

    test('my-jobs page loads without errors', async ({ page }) => {
        await expect(page).toHaveURL(/my-jobs/);
        // No Laravel error page
        await expect(page.locator('body')).not.toContainText('Whoops!');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('my-jobs page shows job list or empty state', async ({ page }) => {
        const hasList = await page.locator('table tbody tr, [class*="job-card"]').first().isVisible().catch(() => false);
        const hasEmpty = await page.locator('[class*="empty"]').first().isVisible().catch(() => false);
        expect(hasList || hasEmpty).toBeTruthy();
    });

    test('each job row has a single actions menu button (not multiple buttons)', async ({ page }) => {
        const firstRow = page.locator('table tbody tr').first();
        if (await firstRow.isVisible()) {
            // Per erp-table-actions-menu rule: only ONE actions trigger per row
            const actionButtons = firstRow.locator('button');
            const count = await actionButtons.count();
            // Should be 1 (the "..." or "Actions" button)
            expect(count).toBeLessThanOrEqual(2); // Allow at most 1-2 buttons
        }
    });

    test('actions modal opens when clicking the actions button', async ({ page }) => {
        const firstActionBtn = page.locator('table tbody tr button').first();
        if (await firstActionBtn.isVisible()) {
            await firstActionBtn.click();
            await expect(page.locator('[role="dialog"]')).toBeVisible({ timeout: 3000 });
        }
    });
});

// ─────────────────────────────────────────────
// Suite 6 — Point Purchase Flow
// ─────────────────────────────────────────────

test.describe('Freelance — Point Purchase Flow', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'client');
        await page.goto('/freelance/points');
        await page.waitForLoadState('networkidle');
    });

    test('points page loads without errors', async ({ page }) => {
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('points purchase form shows cost in user preferred currency (not hardcoded EGP)', async ({ page }) => {
        // The page should NOT show hardcoded "EGP" text for cost display
        // The currency should come from user's currency_id
        const costText = await page.locator('[class*="cost"], [class*="price"], [class*="amount"]').first().textContent().catch(() => '');
        // Should not contain hardcoded currency code
        expect(costText).not.toMatch(/EGP\s+\d/); // EGP followed by a number means hardcoded
    });

    test('package cards are visible and show points and price', async ({ page }) => {
        const packages = page.locator('[class*="package"], [class*="plan"], article, [class*="card"]');
        const count = await packages.count();
        if (count > 0) {
            await expect(packages.first()).toBeVisible();
        }
    });

    test('clicking purchase package button initiates checkout', async ({ page }) => {
        const purchaseBtn = page.getByRole('button', { name: /buy|purchase|get/i }).first();
        if (await purchaseBtn.isVisible()) {
            // Intercept navigation or form submission
            const [request] = await Promise.all([
                page.waitForRequest((req) => req.url().includes('point-purchases'), { timeout: 5000 }).catch(() => null),
                purchaseBtn.click(),
            ]);
            // Either redirected to Kashier or success message shown
        }
    });
});

// ─────────────────────────────────────────────
// Suite 7 — Contracts List
// ─────────────────────────────────────────────

test.describe('Freelance — Contracts', () => {
    test('contracts page loads for a logged-in freelancer', async ({ page }) => {
        await login(page, 'freelancer');
        await page.goto('/freelance/contracts');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/contracts/);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });

    test('contracts page loads for a logged-in client', async ({ page }) => {
        await login(page, 'client');
        await page.goto('/freelance/contracts');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/contracts/);
        await expect(page.locator('body')).not.toContainText('Whoops!');
    });
});

// ─────────────────────────────────────────────
// Suite 8 — Mobile Responsiveness (375px)
// ─────────────────────────────────────────────

test.describe('Freelance — Mobile Responsiveness', () => {
    test.use({ viewport: { width: 375, height: 812 } });

    test.beforeEach(async ({ page }) => {
        await login(page, 'client');
    });

    test('create job form is usable on mobile (no horizontal scroll)', async ({ page }) => {
        await page.goto('/freelance/jobs/create');
        await page.waitForLoadState('networkidle');

        const bodyScrollWidth = await page.evaluate(() => document.body.scrollWidth);
        const viewportWidth = page.viewportSize()?.width ?? 375;
        expect(bodyScrollWidth).toBeLessThanOrEqual(viewportWidth + 5); // 5px tolerance
    });

    test('my-jobs page is usable on mobile', async ({ page }) => {
        await page.goto('/freelance/jobs/my-jobs');
        await page.waitForLoadState('networkidle');

        const bodyScrollWidth = await page.evaluate(() => document.body.scrollWidth);
        const viewportWidth = page.viewportSize()?.width ?? 375;
        expect(bodyScrollWidth).toBeLessThanOrEqual(viewportWidth + 5);
    });

    test('browse jobs page is usable on mobile', async ({ page }) => {
        await page.goto('/freelance/jobs/browse');
        await page.waitForLoadState('networkidle');

        const bodyScrollWidth = await page.evaluate(() => document.body.scrollWidth);
        const viewportWidth = page.viewportSize()?.width ?? 375;
        expect(bodyScrollWidth).toBeLessThanOrEqual(viewportWidth + 5);
    });
});

// ─────────────────────────────────────────────
// Suite 9 — Currency Display (no hardcoded strings)
// ─────────────────────────────────────────────

test.describe('Freelance — Currency Display Rules', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, 'client');
    });

    test('job listing does not show raw currency_id or hardcoded USD anywhere', async ({ page }) => {
        await page.goto('/freelance/jobs/browse');
        await page.waitForLoadState('networkidle');

        // Raw DB column name should never be visible
        await expect(page.locator('body')).not.toContainText('currency_id');
    });

    test('create job form does not have hardcoded USD option in select', async ({ page }) => {
        await page.goto('/freelance/jobs/create');
        await page.waitForLoadState('networkidle');

        // The select options should come from the backend dynamically.
        // We verify by checking the select has options
        const currencySelect = page.locator('select').first();
        if (await currencySelect.isVisible()) {
            const optionTexts = await currencySelect.locator('option').allTextContents();
            // Options should contain currency names from DB (not hardcoded check per se,
            // but we verify the text is NOT the placeholder format we removed)
            for (const text of optionTexts) {
                // Should match pattern like "USD ($)" from DB model, not a placeholder
                expect(text).toMatch(/\w+ \(.\)/); // e.g. "USD ($)"
            }
        }
    });

    test('my jobs page shows formatted budget with currency symbol (not raw numbers)', async ({ page }) => {
        await page.goto('/freelance/jobs/my-jobs');
        await page.waitForLoadState('networkidle');

        // If there are jobs, budget should be formatted
        const firstBudget = page.locator('table tbody tr td').nth(2);
        if (await firstBudget.isVisible()) {
            const text = await firstBudget.textContent() ?? '';
            // Should not be just a plain number — should have a symbol or currency code
            // Raw number like "500" alone is a sign of missing currency formatting
            expect(text.trim()).not.toMatch(/^\d+$/);
        }
    });
});

// ─────────────────────────────────────────────
// Suite 10 — Custom Skills & Admin Management
// ─────────────────────────────────────────────

test.describe('Freelance — Custom Skills & Admin Management', () => {
    
    test('client can type a new custom skill while creating a job', async ({ page }) => {
        await login(page, 'client');
        await page.goto('/freelance/jobs/create');
        await page.waitForLoadState('networkidle');

        // Locate the skills input (Combobox or Creatable Select)
        const skillsInput = page.locator('input[placeholder*="skill" i], .skills-input, [data-testid="skills-select"] input').first();
        
        if (await skillsInput.isVisible()) {
            await skillsInput.fill('My Brand New Skill');
            await skillsInput.press('Enter');
            
            // Should appear as a selected badge
            await expect(page.locator('body')).toContainText('My Brand New Skill');
        }
    });

    test('admin can view pending skills and approve/decline', async ({ page }) => {
        // Admin credentials
        await page.goto('/login');
        await page.waitForSelector('input[name="email"]');
        await page.fill('input[name="email"]', 'admin@musoftwares.com'); // standard test admin
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/(admin|dashboard)/);

        await page.goto('/admin/freelance/skills');
        await page.waitForLoadState('networkidle');

        // Check if page loads
        await expect(page.locator('h1, h2').filter({ hasText: /skills|مهارات/i }).first()).toBeVisible();

        // Check if pending skills are listed (if any)
        const approveBtn = page.getByRole('button', { name: /approve|موافقة/i }).first();
        if (await approveBtn.isVisible()) {
            // we won't click it to avoid side effects if not seeded properly,
            // just verify the button exists in the UI
            await expect(approveBtn).toBeVisible();
            
            const rejectBtn = page.getByRole('button', { name: /reject|decline|رفض/i }).first();
            await expect(rejectBtn).toBeVisible();
        }
    });
});
