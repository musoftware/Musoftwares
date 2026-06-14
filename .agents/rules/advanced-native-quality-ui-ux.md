# Rule: Advanced Native-Quality UI/UX

## Problem Statement
Interfaces that feel template-generated, cluttered, visually noisy, outdated, or generic create a poor user experience and reduce trust. The UI must feel like a seamless extension of the hardware device itself. The system must feel calm, invisible, tactile, premium, adaptive, fast, and human-centered, closely resembling the quality of Apple Human Interface Guidelines, Linear, Notion, or Stripe Dashboard.

---

## Rules & Guidelines

### 1. Core UI Philosophy
- **Prioritize:** clarity, focus, responsiveness, physicality, readability, usability, hierarchy, accessibility, and consistency.
- The UI should disappear behind the user's intention.
- Every interaction must feel: immediate, tactile, fluid, intentional, and alive.

### 2. Visual Design Rules
- **Remove Unnecessary Chrome:** Prioritize content by removing excessive borders, heavy card backgrounds, nested containers, decorative separators, visual clutter, and redundant UI framing. Do NOT overuse boxed layouts, thick shadows, excessive outlines, or noisy gradients.
- **Use Whitespace as Structure:** Use spacing to group information, separate contexts, guide visual flow, and establish rhythm. Prefer layout breathing room and implicit grouping over aggressive section borders.
- **Typography Creates Hierarchy:** Use typography scale, font weight, spacing, alignment, and contrast to establish hierarchy. Do NOT rely on random colors, excessive badges, or decorative backgrounds.
- **Dynamic Type & Accessibility Scaling:** All layouts MUST elastically support accessibility text scaling, Dynamic Type, and large content modes. The interface must NEVER clip text, overlap content, break layouts, or truncate critical information.
- **Color Usage Rules:** Reserve color ONLY for interactivity, system states, feedback, alerts, and status changes. Avoid using color for decoration or visual noise. Neutral palettes should dominate.

### 3. Dark Mode & Depth
- **Proper Elevation Handling:** In Dark Mode, preserve depth using layered elevation, shift surfaces intelligently, and maintain spatial separation. Do NOT simply invert colors or flatten the interface.
- **Materials & Spatial Context:** Use translucency, layered surfaces, soft blur, and material effects to create spatial awareness without hiding background context. Effects should feel subtle and physical.

### 4. Motion & Interaction
- **Physics-Based Motion:** Animations MUST use spring physics, damping, mass, and velocity. Avoid robotic easing or linear animations.
- **Zero Perceived Latency:** All interactions must feel instantaneous. Elements must visually react immediately and acknowledge touch instantly.
- **Tactile Interaction Design:** Interactive elements should compress, highlight, shift, and animate subtly the millisecond interaction begins.
- **Scroll-Aware Interfaces:** Navigation elements should collapse, blur, fade, or minimize as the user scrolls to maximize content visibility dynamically.
- **Haptic Synchronization:** Visual transitions and haptic feedback must feel synchronized.

### 5. Accessibility Standards
- **Accessibility First:** VoiceOver, keyboard navigation, and Switch Control compatibility must be built directly into components from the start. All interactive elements must expose semantic meaning, maintain focus visibility, and support screen readers.
- **Touch Target Standards:** All interactive targets MUST be minimum 44x44 points, generously spaced, and thumb-friendly.

### 6. System Consistency
- **Visual Consistency:** Maintain consistent corner radii, stroke widths, icon proportions, spacing scales, motion timing, and elevation logic across ALL modules, components, dialogs, menus, tables, and forms.

### 7. UX Philosophy
- **Progressive Disclosure:** Hide complexity until explicitly requested. Prioritize simplicity first, advanced workflows later.
- **Forgiving Interfaces:** Destructive actions MUST require deliberate confirmation, provide recovery paths, and prevent accidental activation.
- **Useful Empty & Error States:** Empty states and errors must explain clearly, provide next actions, reduce confusion, and guide recovery.
- **Native Navigation Patterns:** Use familiar, platform-native navigation behavior.

### 8. Engineering Requirements
- **Component Architecture:** Build reusable primitives, composable systems, centralized design tokens, and scalable interaction patterns. Avoid one-off components.
- **Design System Enforcement:** Every generated UI must analyze existing patterns first, extend the current design language, and preserve ecosystem consistency.
- **Preferred Technical Stack:** React, Inertia.js, shadcn/ui, Framer Motion, centralized tokens, and reusable hooks.



---
