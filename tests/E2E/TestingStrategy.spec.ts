import { test, expect } from '@playwright/test';

test.describe('Musoftwares Testing Strategy Validation', () => {
  test('should ensure the application successfully renders its core structure', async ({ page }) => {
    // Navigate to the home page to ensure Vite and Laravel are routing correctly
    const response = await page.goto('/');
    
    // The response status must be OK (less than 400), proving backend serves it
    expect(response?.ok()).toBeTruthy();

    // Verify a fundamental structure like the body or a root div is present
    const body = page.locator('body');
    await expect(body).toBeVisible();

    // If it redirects to login or onboarding, the title will still be set
    // Let's just ensure the page didn't crash and Laravel rendered an HTML document
    const title = await page.title();
    expect(title).not.toBe('');
  });
});
