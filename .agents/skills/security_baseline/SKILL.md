---
name: Web Security Baseline
description: Enforces foundational security practices for web applications.
---

# Web Security Baseline

When building web applications, you must adhere to basic security principles to prevent common vulnerabilities.

## Core Rules

1. **Input Validation & Sanitization**: 
   - Never trust user input. Validate all data on the server-side before processing it.
   - Escape output to prevent Cross-Site Scripting (XSS). If using a modern framework (Blade, React, Vue), they usually escape by default. Be extremely careful when using "raw HTML" features (e.g., `dangerouslySetInnerHTML` in React or `{!! !!}` in Laravel Blade).
2. **CSRF Protection**: Ensure all state-changing forms (POST, PUT, DELETE) include CSRF tokens. Most frameworks handle this natively, do not disable it.
3. **Authentication & Authorization**: 
   - Ensure sensitive routes and API endpoints are protected by authentication middleware.
   - Check authorization (permissions/roles) before allowing users to modify or view sensitive data.
4. **Environment Variables**: Never hardcode API keys, secrets, or database credentials in the codebase. Always use environment variables (`.env`).
5. **Secure Headers**: Set basic security headers if configuring the server response (e.g., X-Frame-Options, X-Content-Type-Options, basic CSP).
