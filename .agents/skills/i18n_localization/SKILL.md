---
name: i18n_localization
description: Guidelines for internationalization and localization. Mandates zero hardcoded strings and modular PHP arrays for translations, explicitly forbidding global JSON translation files.
---

# i18n & Localization Guidelines

This skill enforces strict internationalization (i18n) and localization constraints for the Musoftwares project. It ensures that the application is fully translatable and maintains a clean, modular translation architecture.

## 1. Zero Hardcoded Strings

- **Mandate**: NEVER hardcode English strings (or any other language) directly in the UI components, controllers, or views.
- **Scope**: This applies to all React components, Inertia pages, Laravel Blade templates, controllers, form requests, and any other user-facing text.
- **Rule**: All user-facing text must be retrieved through the designated translation system.

## 2. Modular PHP Arrays

- **Mandate**: All UI text must be translatable via modular PHP arrays.
- **Structure**: Translations must be organized logically into separate PHP files within the `lang/` directory or module-specific `lang/` directories (e.g., `lang/en/messages.php`, `Modules/ERP/lang/en/invoices.php`).
- **Forbidden**: You are explicitly **forbidden** from using global JSON translation files (e.g., `lang/en.json`).

## 3. Usage in Frontend (React/Inertia)

- Pass translated strings from the backend to the frontend using Inertia shared data or specific route props, OR use a frontend translation helper that reads from the generated/exported PHP translation arrays.
- Ensure dynamic placeholders (e.g., `:name`, `:count`) are correctly handled and replaced in both backend and frontend layers.

## 4. Multi-Currency System (Contextual Rule)

- No hardcoded currencies anywhere. The UI must dynamically format and respect the business's or client's active currency.
- Do not hardcode currency symbols like `$`, `€`, or `£`. Pass the active currency ISO code or symbol from the backend and format values using standard libraries (e.g., `Intl.NumberFormat`).

---

## 5. Strict Git Workflow (CRITICAL INSTRUCTION FOR AI AGENTS)

**CRITICAL**: Future AI agents interacting with this codebase MUST follow the strict git workflow outlined below:

1. **Feature Branches**: Every new feature, bug fix, or modification must be done in its own dedicated branch (e.g., `feature/add-invoice-translations`, `fix/login-page-typo`). Do NOT commit directly to the `main` or `master` branch.
2. **Atomic Commits**: Commit your changes after *every* meaningful modification. Do not bundle massive, unrelated changes into a single commit.
3. **Commit Messages**: Write clear, descriptive commit messages explaining *what* was changed and *why*.
