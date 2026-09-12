# Rule: International Design Specifications (Web & Applications)

## Problem Statement
Interfaces that do not adhere to recognized international design specifications suffer from low accessibility, poor usability, high cognitive load, inconsistent visual hierarchy, and degraded performance across diverse user devices.

---

## 1. Accessibility Standards (W3C WCAG 2.2 AA)
- **Contrast Ratios**:
  - Regular body text must maintain a minimum contrast ratio of **4.5:1** against its background.
  - Large headings (18pt / 24px and above) and essential UI components (borders of active inputs, icons) must maintain a minimum contrast ratio of **3:1**.
- **Touch & Click Target Sizes**:
  - All interactive elements (buttons, icons, checkboxes, links) must have an interactive target area of at least **44×44 CSS pixels** (Apple HIG) or **48×48 dp** (Material Design).
- **Keyboard Navigation & Focus Indicators**:
  - Never remove focus styling using `outline: none` without providing an immediate, high-contrast `:focus-visible` ring.
  - Logical tab order must match the natural visual and semantic reading order.
- **Semantic Structure**:
  - Use proper landmark tags (`<header>`, `<nav>`, `<main>`, `<aside>`, `<footer>`).
  - Connect all form controls to explicit labels using `<label for="...">` or `aria-labelledby`.
  - Use `aria-live="polite"` for dynamic asynchronous alerts.

---

## 2. Spatial Grid & Visual Hierarchy (Apple HIG Standards)
- **4pt / 8pt Spatial Grid**:
  - All paddings, margins, gaps, component heights, and icon sizes must be multiples of 4 or 8.
  - Use whitespace as structure to group related items naturally, avoiding dense bordered boxes.
- **Typography Scale**:
  - Maintain a disciplined 3-level typographic hierarchy: Primary Heading, Section Subheading, and Body Text.
  - Set comfortable line heights (1.4 to 1.6 for body text) and limit paragraph line length to 60-75 characters for readability.
- **Dedicated Full-Page Workflows**:
  - Complex forms, creation flows, and data editing must use clean, dedicated full-page routes instead of cramped modals or nested sliding drawers.

---

## 3. Usability & Cognitive Ergonomics (Nielsen Norman Group Heuristics)
- **Immediate System Feedback**:
  - Visual response to user actions must register in less than **100ms** (button active state, touch compression).
  - Use skeleton screens or indeterminate loaders for operations taking between 1s and 3s; show deterministic progress bars for batch tasks lasting over 3s.
- **Error Prevention Over Recovery**:
  - Validate inputs inline as the user types or leaves a field (blur).
  - Require deliberate confirmation for destructive actions (e.g., deleting projects, resetting accounts).
  - Provide non-destructive recovery paths (undo notifications) where feasible.
- **Recognition Over Recall**:
  - Make primary actions and relevant contextual information visible directly on screen. Do not force users to remember options hidden across separate menus.

---

## 4. Motion & Performance Specifications
- **Micro-Interaction Boundaries**:
  - Keep interactive transitions between **150ms and 300ms**. Never exceed 400ms for functional navigation transitions.
  - Use natural physical easing curves (`cubic-bezier(0.4, 0, 0.2, 1)`).
- **Accessibility Motion Preference**:
  - Always respect `@media (prefers-reduced-motion: reduce)` by disabling non-essential positional animations.
- **Ergonomic Performance**:
  - Interaction to Next Paint (INP) must remain below **200ms**.
  - Cumulative Layout Shift (CLS) must remain below **0.1** by reserving image and component dimensions before load.
