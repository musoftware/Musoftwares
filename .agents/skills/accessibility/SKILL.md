---
name: Web Accessibility (a11y)
description: Enforces web accessibility standards to ensure the website is usable by everyone, including people with disabilities.
---

# Accessibility Best Practices

When building user interfaces, you must ensure they are accessible.

## Core Rules

1. **Color Contrast**: Ensure sufficient color contrast between text and background. Avoid low-contrast combinations (e.g., light gray text on a white background).
2. **Keyboard Navigation**: 
   - All interactive elements (buttons, links, form fields) must be focusable and operable using the keyboard (Tab, Enter, Space).
   - Ensure a visible focus indicator (`focus:outline` or `focus:ring`) is present for keyboard users.
3. **ARIA Attributes**: 
   - Use ARIA roles and attributes when standard HTML elements aren't sufficient.
   - Example: `<button aria-label="Close dialog">X</button>` for buttons without text content.
   - Example: `aria-expanded`, `aria-hidden`, `aria-describedby` for complex interactive components (accordions, modals).
4. **Form Labels**: Every form input must have a corresponding `<label>`. If a visible label breaks the design, use a visually hidden label (`sr-only` in Tailwind) or `aria-label`.
5. **Skip Links**: For complex pages, provide a "Skip to content" link at the top of the document for keyboard/screen reader users.
