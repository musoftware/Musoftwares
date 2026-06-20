---
name: frontend_styling_and_ux
description: Enforces Shadcn UI, Tailwind CSS 4.3, mobile parity, and premium UX for the Musoftwares project. Includes strict git workflow rules for agents.
---
# Frontend Styling & UX Skill

This skill provides comprehensive guidelines and strict rules for all frontend implementation in the Musoftwares ecosystem.

## Core Directives

### 1. Styling & Frameworks
- **Tailwind CSS 4.3**: All styling must be achieved using utility classes provided by Tailwind CSS 4.3.
- **Shadcn UI**: Use Radix UI primitives and Shadcn UI components for all interface elements. Do not reinvent components if a Shadcn UI counterpart exists.
- **Premium UX**: Maintain extreme UX simplicity, aesthetic consistency, and an overall premium feel across all platforms. 

### 2. Layout & Form Enforcement
- **Full-Width Pages for ERP**: All Add/Edit forms within the ERP module MUST be dedicated, full-width pages. 
  - **PROHIBITED**: The use of modals, popups, or sliding sheets for any Add/Edit forms in the ERP is strictly forbidden to ensure uncompromised data entry experiences.
- **Mobile Parity**: Ensure that all layouts degrade gracefully and maintain 100% functionality and visual coherence on mobile devices. Fast loading and fully responsive layouts are critical.

### 3. Native Browser Interactions
- **NO Native Prompts or Alerts**: The use of `alert()`, `confirm()`, or `prompt()` is strictly prohibited.
- Always use custom Shadcn alert dialogs and confirmation modals for destructive actions (e.g., deletions) or important notifications.

### 4. General Frontend Architecture
- **i18n Localization**: Zero hardcoded English strings. All text must be translatable via modular PHP arrays (do not use global JSON).
- **Multi-Currency System**: Currencies must dynamically format based on the business's or client's active currency. No hardcoded currency symbols (e.g., `$`).
- **Tri-Path Validation**: Handle the Happy Path, Edge Cases (e.g., invalid data, network drops), and Security Limits (rate limiting, permissions) on every interactive route.
- **Module Isolation**: Never mix logic between strictly separated modules (e.g., ERP vs CRM).

---

## CRITICAL: Strict Git Workflow for AI Agents

When working on features, bug fixes, or enhancements within the frontend or any other module, **all future AI agents MUST adhere strictly to the following Git workflow**:

1. **Every Feature in its Own Branch**:
   - Before starting any new feature or bugfix, you must create and checkout a new branch with a descriptive name.
   - Example: `git checkout -b feature/crm-pipeline-board` or `git checkout -b fix/erp-invoice-layout`.
2. **Commit After Every Logical Modification**:
   - Do not wait until the entire feature is complete to commit.
   - Commit your changes after completing every single logical unit of work or modification.
3. **Descriptive Commit Messages**:
   - Use clear, atomic, and conventional commit messages.
   - Example: `feat(erp): convert add client form to full-width page` or `fix(ui): replace native confirm with shadcn alert dialog`.
4. **Push and Review**:
   - Push your branch and ensure code is reviewed properly before merging. Never bypass the standard pull request process unless given an explicit emergency override.
