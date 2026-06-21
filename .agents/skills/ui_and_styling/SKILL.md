---
name: ui-and-styling
description: Enforces the use of Shadcn UI (base-nova style) and Tailwind CSS v4, ensuring ARIA-compliant, accessible components and standard UI layout constraints. Also enforces strict git workflow rules for AI agents.
---

# UI and Styling Guidelines

This skill enforces strict guidelines for developing frontend components, layouts, and styles within the Musoftwares ecosystem. 

## 1. UI Frameworks & Styling

*   **Shadcn UI (base-nova style):** All UI components MUST be built using Shadcn UI configured with the `base-nova` style. Do not use plain HTML elements where a Shadcn equivalent exists (e.g., use `<Button>` instead of `<button>`, `<Input>` instead of `<input>`).
*   **Tailwind CSS v4:** Use Tailwind CSS v4 for all custom styling, layout, and spacing. Use `@tailwindcss/vite`, `lightningcss`, `clsx`, and `tailwind-merge` for utility class management.
*   **Custom CSS:** Minimize the use of custom CSS files. If required, rely strictly on Tailwind utility classes and theme configuration. 

## 2. Accessibility & ARIA Compliance

*   **Radix UI Primitives:** Leverage Radix UI primitives underlying Shadcn components to ensure robust accessibility.
*   **ARIA Attributes:** Ensure all interactive elements have proper `aria-` attributes (e.g., `aria-label`, `aria-expanded`, `aria-controls`) when not automatically handled by Radix/Shadcn.
*   **Keyboard Navigation:** All interactive elements must be fully navigable via keyboard (Tab, Enter, Space, Arrow keys).
*   **Screen Reader Support:** Ensure meaningful alternative text for images (`alt`) and hidden descriptive text (`sr-only` from Tailwind) where visual context cannot be perceived by screen readers.

## 3. UI Layout Constraints

*   **Responsiveness:** Use Tailwind's responsive modifiers (`sm:`, `md:`, `lg:`, `xl:`, `2xl:`) to ensure interfaces are fully responsive across all device sizes. Mobile-first design principles should be applied.
*   **Consistency:** Adhere to the standard Musoftwares layouts. For example:
    *   Authentication pages should use minimalist cards with GSAP entrance animations.
    *   Dashboards should utilize Bento-grid layouts with overview stat cards.
*   **Animations:** Use GSAP (`^3.15.0`) and Framer Motion (`^12.40.0`) purposefully (e.g., page transitions, modal entrances). Do not overwhelm the UI. Keep animations smooth and under 300ms.

## 4. CRITICAL: AI Agent Git Workflow Rules

Future AI agents MUST adhere to the following strict git workflow when contributing to the Musoftwares project:

1.  **Branch Isolation:** Every new feature, bug fix, or task MUST be developed in its own dedicated branch. Do not commit directly to the `main` or `master` branch.
    *   Branch naming convention: `feature/brief-description`, `bugfix/issue-description`, or `chore/task-name`.
2.  **Incremental Commits:** Commits MUST be made after every meaningful modification or logical step. Do not wait until the entire feature is finished to make a single massive commit.
3.  **Descriptive Commit Messages:** Commit messages must clearly describe *what* was changed and *why*. Use conventional commits format if applicable.
4.  **No Direct Pushes to Main (except this file):** Ensure that all changes are merged via Pull Requests after passing necessary checks and reviews.
