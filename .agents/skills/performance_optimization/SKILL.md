---
name: Performance Optimization
description: Enforces frontend performance standards for website archetypes.
---

# Performance Optimization Best Practices

When building frontend interfaces, you must prioritize fast loading times and smooth rendering.

## Core Rules

1. **Asset Optimization**: 
   - Ensure images are appropriately sized. Use modern formats like WebP or AVIF when possible.
   - For frameworks like Next.js, use the built-in `<Image>` component.
2. **Lazy Loading**: 
   - Lazy load images that are below the fold using `loading="lazy"`.
   - Lazy load non-critical components or heavy third-party scripts.
3. **Minimize Render-Blocking Resources**: 
   - Defer or async non-critical JavaScript (`<script defer src="...">`).
   - Keep critical CSS inline or ensure stylesheets are loaded efficiently.
4. **Bundle Size**: 
   - Avoid importing massive monolithic libraries if you only need one utility (e.g., use `lodash-es` or specific functions instead of the full `lodash` package).
   - Watch out for large SVG files embedded directly into the DOM; load them efficiently.
5. **Caching**: 
   - Implement proper cache headers for static assets if configuring the server.
   - Utilize framework-specific caching mechanisms (like ISR/SSG in Next.js or Laravel's view caching).
