---
name: Error Handling and Logging
description: Standardizes how errors are caught, handled, displayed to the user, and logged.
---

# Error Handling & Logging Best Practices

Robust error handling is critical for both the user experience and developer debugging.

## Core Rules

1. **Frontend Graceful Degradation**: 
   - Never let the entire application crash due to an unhandled exception in one component. Use Error Boundaries (in React) or equivalent mechanisms.
   - Display user-friendly error messages (e.g., "We couldn't load this data right now. Please try again.") rather than raw technical stack traces.
2. **API Error Handling**:
   - Always wrap API calls in `try/catch` blocks or handle Promise `.catch()` rejections.
   - Differentiate between network errors (e.g., offline), 4xx errors (validation/auth), and 5xx errors (server failure).
3. **Server-Side Logging**:
   - Do not use `console.log` for critical errors on the server. Use a proper logging framework (like Laravel's Log facade or Winston in Node.js).
   - Ensure sensitive user data (passwords, tokens) is NEVER logged.
