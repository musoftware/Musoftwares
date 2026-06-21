---
name: E2E Testing (Frontend)
description: Enforces end-to-end testing standards for critical user journeys on the frontend.
---

# End-to-End (E2E) Testing Best Practices

End-to-End tests simulate real user interactions within the browser to verify the entire stack works together.

## Core Rules

1. **Focus on Critical Paths**: E2E tests are slow and expensive. Focus on covering the most critical user journeys (e.g., User Registration, Login, Checkout Process, Core Feature Usage).
2. **Framework Choice**: Use robust E2E testing frameworks like Cypress or Playwright.
3. **Resilient Selectors**: Do not use brittle CSS classes or deep DOM structures for selecting elements in tests. Use `data-testid` attributes or ARIA roles (e.g., `cy.get('[data-testid="submit-button"]')` or `page.getByRole('button', { name: 'Submit' })`) to make tests resilient to design changes.
4. **Test Independence**: Each E2E test must run in isolation. Reset the state (database, cookies, local storage) before each test run. Do not rely on the outcome of a previous test.
