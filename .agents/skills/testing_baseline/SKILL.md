---
name: Testing Baseline
description: Enforces a minimum standard of automated testing for business logic.
---

# Testing Baseline Best Practices

Reliable software requires automated tests to prevent regressions.

## Core Rules

1. **Unit Testing Core Logic**: 
   - All critical business logic, helper functions, and complex calculations MUST be covered by unit tests (e.g., using PHPUnit, Jest, Vitest).
   - Mock external dependencies and database connections for fast unit testing.
2. **Integration Testing**: 
   - Write integration tests for API endpoints and critical backend routes to ensure the database interactions and request/response cycle work correctly.
3. **Test Naming Convention**: Use descriptive test names that clearly explain what is being tested and the expected outcome (e.g., `test_user_cannot_register_with_existing_email`).
