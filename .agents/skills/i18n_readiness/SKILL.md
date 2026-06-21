---
name: Internationalization (i18n)
description: Prepares the codebase for multi-language support.
---

# Internationalization (i18n) Readiness

Websites often need to support multiple languages. The codebase must be prepared from day one.

## Core Rules

1. **No Hardcoded Strings**: Never hardcode user-facing text strings directly in the UI components (e.g., `<button>Submit</button>`).
2. **Use Translation Functions**: Always wrap strings in the framework's translation function or helper (e.g., `{{ __('Submit') }}` in Laravel, `t('submit')` in React/i18next).
3. **Structured Translation Files**: Keep translation keys organized logically (e.g., grouped by feature or page) in the respective language files (`en.json`, `ar.json`).
4. **RTL Support**: Keep RTL (Right-to-Left) languages in mind. Use logical CSS properties like `margin-inline-start` instead of `margin-left` where appropriate to support bidirectional layouts.
5. **Avoid Double-Flipping**: Never mix logical CSS properties with manual RTL overrides or directional hacks. Remember: RTL of RTL is LTR!
