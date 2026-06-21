---
name: Mobile Responsiveness
description: Enforces mobile-first responsive design best practices for website archetypes.
---

# Mobile Responsiveness Best Practices

When building or modifying UI components, you must ensure they look good and function properly on all screen sizes, prioritizing mobile-first design.

## Core Rules

1. **Mobile-First Approach**: Write base CSS/Tailwind classes for mobile devices first, then use media queries (e.g., `md:`, `lg:`) to adjust for larger screens.
2. **Flexible Layouts**: 
   - Use CSS Grid and Flexbox for layout structures.
   - Avoid fixed widths (e.g., `width: 800px;`) on container elements; use percentages (`width: 100%;`) or viewport units (`vw`, `vh`) or max-widths (`max-w-screen-xl`).
3. **Touch Targets**: Ensure all clickable elements (buttons, links) have a minimum touch target size of 44x44 pixels for easy tapping on mobile.
4. **Responsive Typography**: Use relative units (like `rem` or `em`) for font sizes to allow them to scale properly with device settings.
5. **Testing**: Always test or mentally verify how the UI will collapse or stack on a 375px wide screen (typical mobile).

## Tailwind Examples

- **Correct**: `<div class="flex flex-col md:flex-row w-full gap-4 p-4 text-sm md:text-base">...</div>`
- **Incorrect**: `<div class="flex w-[800px] text-[14px]">...</div>` (breaks on mobile)
